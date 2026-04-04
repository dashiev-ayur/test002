<?php

declare(strict_types=1);

namespace App\Telegram;

/**
 * Отправка сообщений в Telegram Bot API (инфраструктурный контракт).
 */
interface TelegramClient
{
    /**
     * @throws TelegramSendException при ошибке HTTP или ответа Bot API (ok: false)
     */
    public function sendMessage(string $botToken, string $chatId, string $text): void;
}
