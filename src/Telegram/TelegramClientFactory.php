<?php

declare(strict_types=1);

namespace App\Telegram;

/**
 * Выбор реализации Telegram по конфигурации окружения.
 */
final readonly class TelegramClientFactory
{
    public function __construct(
        private bool $useRealApi,
        private HttpTelegramClient $httpTelegramClient,
        private MockTelegramClient $mockTelegramClient,
    ) {
    }

    public function create(): TelegramClient
    {
        return $this->useRealApi ? $this->httpTelegramClient : $this->mockTelegramClient;
    }
}
