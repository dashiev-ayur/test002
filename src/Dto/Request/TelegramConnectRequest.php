<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class TelegramConnectRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Поле botToken обязательно.')]
        public string $botToken = '',
        #[Assert\NotBlank(message: 'Поле chatId обязательно.')]
        public string $chatId = '',
        #[Assert\NotNull(message: 'Поле enabled обязательно.')]
        public ?bool $enabled = null,
    ) {
    }
}
