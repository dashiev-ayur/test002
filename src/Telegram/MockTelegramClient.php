<?php

declare(strict_types=1);

namespace App\Telegram;

/**
 * Режим без сети: успешная «отправка», вызовы можно просмотреть в тестах.
 */
final class MockTelegramClient implements TelegramClient
{
    /** @var list<array{botToken: string, chatId: string, text: string}> */
    private array $recordedCalls = [];

    public function sendMessage(string $botToken, string $chatId, string $text): void
    {
        $this->recordedCalls[] = [
            'botToken' => $botToken,
            'chatId' => $chatId,
            'text' => $text,
        ];
    }

    /**
     * @return list<array{botToken: string, chatId: string, text: string}>
     */
    public function getRecordedCalls(): array
    {
        return $this->recordedCalls;
    }

    public function resetRecordedCalls(): void
    {
        $this->recordedCalls = [];
    }
}
