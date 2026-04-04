<?php

declare(strict_types=1);

namespace App\Entity\Enum;

/**
 * Статус попытки отправки в Telegram (журнал).
 */
enum TelegramSendStatus: string
{
    case SENT = 'SENT';
    case FAILED = 'FAILED';
}
