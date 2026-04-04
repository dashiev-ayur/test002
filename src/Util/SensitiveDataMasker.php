<?php

declare(strict_types=1);

namespace App\Util;

/**
 * Частичное маскирование секретов и идентификаторов для ответов API.
 */
final class SensitiveDataMasker
{
    public static function maskChatId(string $chatId): string
    {
        $s = trim($chatId);
        $len = mb_strlen($s, 'UTF-8');
        if ($len <= 1) {
            return str_repeat('*', max(1, $len));
        }
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        $prefix = mb_substr($s, 0, 2, 'UTF-8');
        $suffix = mb_substr($s, $len - 2, 2, 'UTF-8');
        $innerLen = $len - 4;

        return $prefix . str_repeat('*', max(1, $innerLen)) . $suffix;
    }

    public static function maskBotToken(string $botToken): string
    {
        $s = trim($botToken);
        $len = strlen($s);
        if ($len <= 4) {
            return str_repeat('*', max(4, $len));
        }

        return '****' . substr($s, -4);
    }
}
