<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Enum\TelegramSendStatus;
use App\Entity\ShopOrder;
use App\Enum\OrderNotificationDispatchStatus;
use App\Exception\ShopNotFoundException;
use App\Repository\ShopRepository;
use App\Repository\TelegramIntegrationRepository;
use App\Repository\TelegramSendLogRepository;
use App\Telegram\TelegramClient;
use App\Telegram\TelegramSendException;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ShopOrderCreationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShopRepository $shopRepository,
        private TelegramIntegrationRepository $telegramIntegrationRepository,
        private TelegramSendLogRepository $telegramSendLogRepository,
        private TelegramClient $telegramClient,
    ) {
    }

    public function createAndNotify(
        int $shopId,
        string $number,
        string $total,
        ?string $customerName,
    ): CreatedShopOrderResult {
        $shop = $this->shopRepository->find($shopId);
        if ($shop === null) {
            throw new ShopNotFoundException(sprintf('Магазин с id %d не найден.', $shopId));
        }

        $order = new ShopOrder();
        $order->setShop($shop);
        $order->setNumber($number);
        $order->setTotal($this->normalizeTotal($total));
        $order->setCustomerName($this->normalizeCustomerName($customerName));

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $notificationStatus = $this->dispatchTelegramIfNeeded($shopId, $order);

        return new CreatedShopOrderResult($order, $notificationStatus);
    }

    private function dispatchTelegramIfNeeded(int $shopId, ShopOrder $order): OrderNotificationDispatchStatus
    {
        $orderId = $order->getId();
        if ($orderId === null) {
            return OrderNotificationDispatchStatus::SKIPPED;
        }

        $integration = $this->telegramIntegrationRepository->findOneByShopId($shopId);
        if ($integration === null || !$integration->isEnabled()) {
            return OrderNotificationDispatchStatus::SKIPPED;
        }

        if ($this->telegramSendLogRepository->findOneByShopIdAndOrderId($shopId, $orderId) !== null) {
            return OrderNotificationDispatchStatus::SKIPPED;
        }

        $messageText = $this->formatNotificationText($order);

        try {
            $this->telegramClient->sendMessage(
                $integration->getBotToken(),
                $integration->getChatId(),
                $messageText,
            );

            return $this->tryPersistSendLog($order, TelegramSendStatus::SENT, $messageText, null)
                ? OrderNotificationDispatchStatus::SENT
                : OrderNotificationDispatchStatus::SKIPPED;
        } catch (TelegramSendException $e) {
            return $this->tryPersistSendLog($order, TelegramSendStatus::FAILED, $messageText, $e->getMessage())
                ? OrderNotificationDispatchStatus::FAILED
                : OrderNotificationDispatchStatus::SKIPPED;
        }
    }

    private function tryPersistSendLog(
        ShopOrder $order,
        TelegramSendStatus $status,
        string $messageText,
        ?string $error,
    ): bool {
        $shop = $order->getShop();
        if ($shop === null || $shop->getId() === null || $order->getId() === null) {
            return false;
        }

        $sentAt = new \DateTimeImmutable();

        $affected = (int) $this->entityManager->getConnection()->executeStatement(
            <<<'SQL'
                INSERT INTO telegram_send_log (message, status, error, sent_at, shop_id, order_id)
                VALUES (:message, :status, :error, :sentAt, :shopId, :orderId)
                ON CONFLICT (shop_id, order_id) DO NOTHING
                SQL,
            [
                'message' => $messageText,
                'status' => $status->value,
                'error' => $error,
                'sentAt' => $sentAt->format('c'),
                'shopId' => $shop->getId(),
                'orderId' => $order->getId(),
            ],
            [
                'message' => ParameterType::STRING,
                'status' => ParameterType::STRING,
                'error' => $error === null ? ParameterType::NULL : ParameterType::STRING,
                'sentAt' => ParameterType::STRING,
                'shopId' => ParameterType::INTEGER,
                'orderId' => ParameterType::INTEGER,
            ],
        );

        return $affected > 0;
    }

    private function formatNotificationText(ShopOrder $order): string
    {
        $totalRaw = $order->getTotal();
        $totalDisplay = '' !== $totalRaw ? rtrim(rtrim($totalRaw, '0'), '.') : '0';
        if ($totalDisplay === '') {
            $totalDisplay = '0';
        }

        $customer = $order->getCustomerName();
        if ($customer === null || $customer === '') {
            $customer = 'не указан';
        }

        return sprintf(
            'Новый заказ %s на сумму %s ₽, клиент %s',
            $order->getNumber(),
            $totalDisplay,
            $customer,
        );
    }

    private function normalizeTotal(string $total): string
    {
        return number_format((float) $total, 2, '.', '');
    }

    private function normalizeCustomerName(?string $customerName): ?string
    {
        if ($customerName === null) {
            return null;
        }

        $trimmed = trim($customerName);

        return $trimmed === '' ? null : $trimmed;
    }
}
