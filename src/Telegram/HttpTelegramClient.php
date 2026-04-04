<?php

declare(strict_types=1);

namespace App\Telegram;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final readonly class HttpTelegramClient implements TelegramClient
{
    private const string BASE_URL = 'https://api.telegram.org';

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function sendMessage(string $botToken, string $chatId, string $text): void
    {
        $url = sprintf('%s/bot%s/sendMessage', self::BASE_URL, $botToken);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $text,
                ],
                'timeout' => 15,
            ]);
        } catch (TransportExceptionInterface $e) {
            throw new TelegramSendException('Telegram request failed: '.$e->getMessage(), 0, $e);
        }

        $statusCode = $response->getStatusCode();

        try {
            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            throw new TelegramSendException('Invalid response from Telegram API.', 0, $e);
        }

        if ($statusCode !== 200 || !($data['ok'] ?? false)) {
            $description = is_string($data['description'] ?? null)
                ? $data['description']
                : 'Telegram API error';

            throw new TelegramSendException($description);
        }
    }
}
