<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ShopOrder;
use App\Enum\OrderNotificationDispatchStatus;

final readonly class CreatedShopOrderResult
{
    public function __construct(
        public ShopOrder $order,
        public OrderNotificationDispatchStatus $notificationStatus,
    ) {
    }
}
