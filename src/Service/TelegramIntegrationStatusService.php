<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Enum\TelegramSendStatus;
use App\Exception\ShopNotFoundException;
use App\Repository\ShopRepository;
use App\Repository\TelegramIntegrationRepository;
use App\Repository\TelegramSendLogRepository;
use App\Util\SensitiveDataMasker;

final readonly class TelegramIntegrationStatusService
{
    public function __construct(
        private ShopRepository $shopRepository,
        private TelegramIntegrationRepository $telegramIntegrationRepository,
        private TelegramSendLogRepository $telegramSendLogRepository,
    ) {
    }

    /**
     * Агрегаты для GET …/telegram/status (частичное маскирование chatId).
     *
     * @return array{
     *   enabled: bool,
     *   chatId: string|null,
     *   lastSentAt: \DateTimeImmutable|null,
     *   sentCount: int,
     *   failedCount: int,
     * }
     */
    public function getStatus(int $shopId): array
    {
        if ($this->shopRepository->find($shopId) === null) {
            throw new ShopNotFoundException(sprintf('Магазин с id %d не найден.', $shopId));
        }

        $integration = $this->telegramIntegrationRepository->findOneByShopId($shopId);
        $enabled = $integration !== null && $integration->isEnabled();
        $chatId = null;
        if ($integration !== null) {
            $chatId = SensitiveDataMasker::maskChatId($integration->getChatId());
        }

        $since = new \DateTimeImmutable('-7 days');

        return [
            'enabled' => $enabled,
            'chatId' => $chatId,
            'lastSentAt' => $this->telegramSendLogRepository->findLatestSuccessfulSentAt($shopId),
            'sentCount' => $this->telegramSendLogRepository->countByShopAndStatusSince(
                $shopId,
                TelegramSendStatus::SENT,
                $since,
            ),
            'failedCount' => $this->telegramSendLogRepository->countByShopAndStatusSince(
                $shopId,
                TelegramSendStatus::FAILED,
                $since,
            ),
        ];
    }
}
