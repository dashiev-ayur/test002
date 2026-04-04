<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Shop;
use App\Enum\OrderNotificationDispatchStatus;
use App\Exception\InvalidTelegramIntegrationDataException;
use App\Exception\ShopNotFoundException;
use App\Service\ShopOrderCreationService;
use App\Service\TelegramIntegrationUpsertService;
use App\Telegram\MockTelegramClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Проверка фазы 3 без HTTP: сервисы upsert и создание заказа + мок Telegram.
 *
 * Подготовка БД (по умолчанию Symfony добавляет суффикс `_test` к имени базы из `DATABASE_URL`):
 *
 *   APP_ENV=test php bin/console doctrine:database:create --if-not-exists
 *   APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction
 *
 * В тестах `TELEGRAM_USE_REAL_API=false` (см. `.env.test`) — используется MockTelegramClient.
 */
final class Phase3ApplicationServicesTest extends KernelTestCase
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

    public function testUpsertRequiresNonEmptyFields(): void
    {
        $shop = new Shop();
        $shop->setName('t-shop-validation');
        $this->entityManager->persist($shop);
        $this->entityManager->flush();
        $shopId = (int) $shop->getId();

        $upsert = static::getContainer()->get(TelegramIntegrationUpsertService::class);

        $this->expectException(InvalidTelegramIntegrationDataException::class);
        $upsert->upsert($shopId, '  ', 'chat', true);
    }

    public function testUpsertThrowsWhenShopMissing(): void
    {
        $upsert = static::getContainer()->get(TelegramIntegrationUpsertService::class);

        $this->expectException(ShopNotFoundException::class);
        $upsert->upsert(999_999_999, 'token', 'chat', true);
    }

    public function testCreateOrderSendsTelegramWhenIntegrationEnabled(): void
    {
        $shop = new Shop();
        $shop->setName('t-shop-notify');
        $this->entityManager->persist($shop);
        $this->entityManager->flush();
        $shopId = (int) $shop->getId();

        $upsert = static::getContainer()->get(TelegramIntegrationUpsertService::class);
        $upsert->upsert($shopId, 'test:bot-token', '987654', true);

        $mock = static::getContainer()->get(MockTelegramClient::class);
        $mock->resetRecordedCalls();

        $orderService = static::getContainer()->get(ShopOrderCreationService::class);
        $result = $orderService->createAndNotify($shopId, 'A-1005', '2490', 'Анна');

        self::assertSame(OrderNotificationDispatchStatus::SENT, $result->notificationStatus);
        self::assertNotNull($result->order->getId());

        $calls = $mock->getRecordedCalls();
        self::assertCount(1, $calls);
        self::assertSame('test:bot-token', $calls[0]['botToken']);
        self::assertSame('987654', $calls[0]['chatId']);
        self::assertStringContainsString('A-1005', $calls[0]['text']);
        self::assertStringContainsString('2490', $calls[0]['text']);
        self::assertStringContainsString('Анна', $calls[0]['text']);
    }

    public function testCreateOrderSkipsWhenIntegrationDisabled(): void
    {
        $shop = new Shop();
        $shop->setName('t-shop-off');
        $this->entityManager->persist($shop);
        $this->entityManager->flush();
        $shopId = (int) $shop->getId();

        $upsert = static::getContainer()->get(TelegramIntegrationUpsertService::class);
        $upsert->upsert($shopId, 'test:bot-token', '987654', false);

        $mock = static::getContainer()->get(MockTelegramClient::class);
        $mock->resetRecordedCalls();

        $orderService = static::getContainer()->get(ShopOrderCreationService::class);
        $result = $orderService->createAndNotify($shopId, 'X-1', '10', null);

        self::assertSame(OrderNotificationDispatchStatus::SKIPPED, $result->notificationStatus);
        self::assertSame([], $mock->getRecordedCalls());
    }
}
