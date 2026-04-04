<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Минимальный CORS для разработки (отдельный origin у фронта).
 */
final class CorsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $allowOrigin,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 512],
            KernelEvents::RESPONSE => ['onKernelResponse', -512],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isApiPath($request)) {
            return;
        }

        if ($request->getMethod() !== Request::METHOD_OPTIONS) {
            return;
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);
        $this->applyHeaders($request, $response);
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isApiPath($request)) {
            return;
        }

        $this->applyHeaders($request, $event->getResponse());
    }

    private function isApiPath(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/shops/');
    }

    private function applyHeaders(Request $request, Response $response): void
    {
        $origin = $this->allowOrigin;
        if ($origin === '*' && $request->headers->has('Origin')) {
            $requestOrigin = (string) $request->headers->get('Origin');
            if ($requestOrigin !== '') {
                $origin = $requestOrigin;
            }
        }

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set(
            'Access-Control-Allow-Headers',
            'Content-Type, Authorization',
        );
        $response->headers->set('Access-Control-Max-Age', '3600');
    }
}
