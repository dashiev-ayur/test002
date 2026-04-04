<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TelegramIntegration;
use App\Exception\InvalidTelegramIntegrationDataException;
use App\Exception\ShopNotFoundException;
use App\Repository\ShopRepository;
use App\Repository\TelegramIntegrationRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TelegramIntegrationUpsertService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShopRepository $shopRepository,
        private TelegramIntegrationRepository $telegramIntegrationRepository,
    ) {
    }

    public function upsert(int $shopId, string $botToken, string $chatId, bool $enabled): TelegramIntegration
    {
        $botToken = trim($botToken);
        $chatId = trim($chatId);

        if ($botToken === '' || $chatId === '') {
            throw new InvalidTelegramIntegrationDataException('Поля botToken и chatId не могут быть пустыми.');
        }

        $shop = $this->shopRepository->find($shopId);
        if ($shop === null) {
            throw new ShopNotFoundException(sprintf('Магазин с id %d не найден.', $shopId));
        }

        $integration = $this->telegramIntegrationRepository->findOneByShopId($shopId);
        if ($integration === null) {
            $integration = new TelegramIntegration();
            $integration->setShop($shop);
            $this->entityManager->persist($integration);
        }

        $integration->setBotToken($botToken);
        $integration->setChatId($chatId);
        $integration->setEnabled($enabled);

        $this->entityManager->flush();

        return $integration;
    }
}
