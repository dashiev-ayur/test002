<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateShopOrderRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Поле number обязательно.')]
        public string $number = '',
        #[Assert\NotBlank(message: 'Поле total обязательно.')]
        #[Assert\Type(type: ['int', 'float', 'string'], message: 'Поле total должно быть числом.')]
        public string|int|float|null $total = null,
        public ?string $customerName = null,
    ) {
    }
}
