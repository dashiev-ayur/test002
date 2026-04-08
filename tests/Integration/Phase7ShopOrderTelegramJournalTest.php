<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Enum\TelegramSendStatus;
use App\Entity\Shop;
use App\Entity\ShopOrder;
use App\Enum\OrderNotificationDispatchStatus;
use App\Repository\TelegramSendLogRepository;
use App\Service\ShopOrderCreationService;
use App\Service\TelegramIntegrationUpsertService;
use App\Telegram\MockTelegramClient;
use App\Telegram\TelegramSendException;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Фаза 7 ТЗ: журнал отправки, идемпотентность по (shop_id, order_id), сбой клиента при сохранённом заказе.
 *
 * Требуется БД тестового окружения и миграции (см. Phase3ApplicationServicesTest).
 */
final class Phase7ShopOrderTelegramJournalTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $conn = $this->entityManager->getConnection();
        if ($conn->isTransactionActive()) {
            $conn->rollBack();
        }
        parent::tearDown();
    }

    public function testJournalRecordsSentWhenIntegrationEnabledAndMockSendSucceeds(): void
    {
        $shopId = $this->createShop('phase7-tz-sent');
        $this->enableTelegram($shopId);

        $mock = static::getContainer()->get(MockTelegramClient::class);
        $mock->resetRecordedCalls();

        $orderService = static::getContainer()->get(ShopOrderCreationService::class);
        $result = $orderService->createAndNotify($shopId, 'A-1005', '2490.00', 'Анна');

        self::assertSame(OrderNotificationDispatchStatus::SENT, $result->notificationStatus);
        $orderId = $result->order->getId();
        self::assertNotNull($orderId);

        self::assertCount(1, $mock->getRecordedCalls());

        $logRepo = static::getContainer()->get(TelegramSendLogRepository::class);
        $log = $logRepo->findOneByShopIdAndOrderId($shopId, $orderId);
        self::assertNotNull($log);
        self::assertSame(TelegramSendStatus::SENT, $log->getStatus());
        self::assertNull($log->getError());
    }

    public function testSecondDispatchForSameOrderDoesNotCallClientAgainAndDoesNotDuplicateJournal(): void
    {
        $shopId = $this->createShop('phase7-tz-idempotent');
        $this->enableTelegram($shopId);

        $mock = static::getContainer()->get(MockTelegramClient::class);
        $mock->resetRecordedCalls();

        $orderService = static::getContainer()->get(ShopOrderCreationService::class);
        $result = $orderService->createAndNotify($shopId, 'B-1', '100.00', null);

        self::assertSame(OrderNotificationDispatchStatus::SENT, $result->notificationStatus);
        $order = $result->order;
        $orderId = $order->getId();
        self::assertNotNull($orderId);

        self::assertCount(1, $mock->getRecordedCalls());

        $logRepo = static::getContainer()->get(TelegramSendLogRepository::class);
        $logBefore = $logRepo->findOneByShopIdAndOrderId($shopId, $orderId);
        self::assertNotNull($logBefore);

        $status = $this->invokeDispatchTelegramIfNeeded($orderService, $shopId, $order);
        self::assertSame(OrderNotificationDispatchStatus::SKIPPED, $status);

        self::assertCount(1, $mock->getRecordedCalls());
        $logAfter = $logRepo->findOneByShopIdAndOrderId($shopId, $orderId);
        self::assertSame($logBefore->getId(), $logAfter->getId());
    }

    public function testClientFailureWritesFailedJournalRowAndOrderRemainsInDatabase(): void
    {
        $shopId = $this->createShop('phase7-tz-fail');
        $this->enableTelegram($shopId);

        $mock = static::getContainer()->get(MockTelegramClient::class);
        $mock->resetRecordedCalls();
        $mock->setExceptionOnNextSend(new TelegramSendException('telegram api simulated failure'));

        $orderService = static::getContainer()->get(ShopOrderCreationService::class);
        $result = $orderService->createAndNotify($shopId, 'C-9', '50.00', 'Иван');

        self::assertSame(OrderNotificationDispatchStatus::FAILED, $result->notificationStatus);
        $orderId = $result->order->getId();
        self::assertNotNull($orderId);

        self::assertCount(1, $mock->getRecordedCalls());

        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(ShopOrder::class, $orderId);
        self::assertInstanceOf(ShopOrder::class, $reloaded);
        self::assertSame('C-9', $reloaded->getNumber());

        $logRepo = static::getContainer()->get(TelegramSendLogRepository::class);
        $log = $logRepo->findOneByShopIdAndOrderId($shopId, $orderId);
        self::assertNotNull($log);
        self::assertSame(TelegramSendStatus::FAILED, $log->getStatus());
        self::assertSame('telegram api simulated failure', $log->getError());
    }

    private function createShop(string $name): int
    {
        $shop = new Shop();
        $shop->setName($name);
        $this->entityManager->persist($shop);
        $this->entityManager->flush();

        return (int) $shop->getId();
    }

    private function enableTelegram(int $shopId): void
    {
        $upsert = static::getContainer()->get(TelegramIntegrationUpsertService::class);
        $upsert->upsert($shopId, 'test:phase7-bot-token', '987654321', true);
    }

    private function invokeDispatchTelegramIfNeeded(
        ShopOrderCreationService $service,
        int $shopId,
        ShopOrder $order,
    ): OrderNotificationDispatchStatus {
        $method = new ReflectionMethod(ShopOrderCreationService::class, 'dispatchTelegramIfNeeded');
        $method->setAccessible(true);

        return $method->invoke($service, $shopId, $order);
    }
}
