<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\CreateShopOrderRequest;
use App\Dto\Request\TelegramConnectRequest;
use App\Exception\InvalidTelegramIntegrationDataException;
use App\Exception\ShopNotFoundException;
use App\Service\ShopOrderCreationService;
use App\Service\TelegramIntegrationStatusService;
use App\Service\TelegramIntegrationUpsertService;
use App\Util\SensitiveDataMasker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ShopApiController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly TelegramIntegrationUpsertService $telegramIntegrationUpsertService,
        private readonly ShopOrderCreationService $shopOrderCreationService,
        private readonly TelegramIntegrationStatusService $telegramIntegrationStatusService,
    ) {
    }

    #[Route('/shops/{shopId<\d+>}/telegram/connect', name: 'shop_telegram_connect', methods: ['POST'])]
    public function connect(int $shopId, Request $request): JsonResponse
    {
        $dto = $this->decodeRequestBody($request, TelegramConnectRequest::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            return $this->jsonValidationErrorResponse($violations);
        }

        try {
            $integration = $this->telegramIntegrationUpsertService->upsert(
                $shopId,
                $dto->botToken,
                $dto->chatId,
                (bool) $dto->enabled,
            );
        } catch (ShopNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (InvalidTelegramIntegrationDataException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json([
            'id' => $integration->getId(),
            'enabled' => $integration->isEnabled(),
            'botToken' => SensitiveDataMasker::maskBotToken($integration->getBotToken()),
            'chatId' => SensitiveDataMasker::maskChatId($integration->getChatId()),
            'createdAt' => $integration->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $integration->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/shops/{shopId<\d+>}/orders', name: 'shop_orders_create', methods: ['POST'])]
    public function createOrder(int $shopId, Request $request): JsonResponse
    {
        $dto = $this->decodeRequestBody($request, CreateShopOrderRequest::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            return $this->jsonValidationErrorResponse($violations);
        }

        $number = trim($dto->number);
        $totalStr = $this->normalizeTotalInput($dto->total);
        $customerName = $dto->customerName !== null ? trim($dto->customerName) : null;
        if ($customerName === '') {
            $customerName = null;
        }

        try {
            $result = $this->shopOrderCreationService->createAndNotify(
                $shopId,
                $number,
                $totalStr,
                $customerName,
            );
        } catch (ShopNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        $order = $result->order;

        return $this->json([
            'order' => [
                'id' => $order->getId(),
                'number' => $order->getNumber(),
                'total' => $order->getTotal(),
                'customerName' => $order->getCustomerName(),
                'createdAt' => $order->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ],
            'notificationStatus' => $result->notificationStatus->value,
        ], Response::HTTP_CREATED);
    }

    #[Route('/shops/{shopId<\d+>}/telegram/status', name: 'shop_telegram_status', methods: ['GET'])]
    public function telegramStatus(int $shopId): JsonResponse
    {
        try {
            $status = $this->telegramIntegrationStatusService->getStatus($shopId);
        } catch (ShopNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        /** @var \DateTimeImmutable|null $lastSentAt */
        $lastSentAt = $status['lastSentAt'];

        return $this->json([
            'enabled' => $status['enabled'],
            'chatId' => $status['chatId'],
            'lastSentAt' => $lastSentAt?->format(\DateTimeInterface::ATOM),
            'sentCount' => $status['sentCount'],
            'failedCount' => $status['failedCount'],
        ]);
    }

    private function decodeRequestBody(Request $request, string $dtoClass): TelegramConnectRequest|CreateShopOrderRequest|JsonResponse
    {
        $content = $request->getContent();
        if (trim($content) === '') {
            return $this->json(['error' => 'Тело запроса пустое.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            /** @var TelegramConnectRequest|CreateShopOrderRequest $dto */
            $dto = $this->serializer->deserialize($content, $dtoClass, 'json');
        } catch (\Throwable) {
            return $this->json(['error' => 'Некорректный JSON.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$dto instanceof TelegramConnectRequest && !$dto instanceof CreateShopOrderRequest) {
            return $this->json(['error' => 'Некорректное тело запроса.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($dto instanceof TelegramConnectRequest) {
            $dto->botToken = trim($dto->botToken);
            $dto->chatId = trim($dto->chatId);
        }

        if ($dto instanceof CreateShopOrderRequest) {
            $dto->number = trim($dto->number);
            if (\is_string($dto->total)) {
                $dto->total = trim($dto->total);
            }
            if ($dto->customerName !== null) {
                $dto->customerName = trim($dto->customerName);
            }
        }

        return $dto;
    }

    private function normalizeTotalInput(string|int|float|null $total): string
    {
        if ($total === null) {
            return '0';
        }
        if (\is_string($total)) {
            return $total;
        }

        return (string) $total;
    }

    private function jsonValidationErrorResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $path = (string) $violation->getPropertyPath();
            $errors[$path][] = (string) $violation->getMessage();
        }

        return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
