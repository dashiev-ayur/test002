<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Результат попытки уведомления о заказе в Telegram (ответ API).
 */
enum OrderNotificationDispatchStatus: string
{
    case SENT = 'sent';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';
}
