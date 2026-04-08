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

    private ?TelegramSendException $exceptionOnNextSend = null;

    /**
     * Следующий вызов sendMessage выбросит это исключение (однократно). Для тестов сбоя доставки.
     */
    public function setExceptionOnNextSend(?TelegramSendException $exception): void
    {
        $this->exceptionOnNextSend = $exception;
    }

    public function sendMessage(string $botToken, string $chatId, string $text): void
    {
        if ($this->exceptionOnNextSend !== null) {
            $toThrow = $this->exceptionOnNextSend;
            $this->exceptionOnNextSend = null;
            $this->recordedCalls[] = [
                'botToken' => $botToken,
                'chatId' => $chatId,
                'text' => $text,
            ];
            throw $toThrow;
        }

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
        $this->exceptionOnNextSend = null;
    }
}
