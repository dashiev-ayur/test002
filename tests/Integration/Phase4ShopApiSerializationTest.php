<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Dto\Request\CreateShopOrderRequest;
use App\Dto\Request\TelegramConnectRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Проверка, что тела запросов фазы 4 десериализуются (без БД).
 */
final class Phase4ShopApiSerializationTest extends KernelTestCase
{
    public function testDeserializeConnectAndOrderPayloads(): void
    {
        self::bootKernel();

        $serializer = static::getContainer()->get(SerializerInterface::class);

        $connect = $serializer->deserialize(
            '{"botToken":"t","chatId":"c","enabled":true}',
            TelegramConnectRequest::class,
            'json',
        );
        self::assertInstanceOf(TelegramConnectRequest::class, $connect);
        self::assertSame('t', $connect->botToken);
        self::assertTrue($connect->enabled);

        $order = $serializer->deserialize(
            '{"number":"A-1","total":2490,"customerName":"Ann"}',
            CreateShopOrderRequest::class,
            'json',
        );
        self::assertInstanceOf(CreateShopOrderRequest::class, $order);
        self::assertIsFloat($order->total);
        self::assertEqualsWithDelta(2490.0, $order->total, 0.000001);
    }
}
