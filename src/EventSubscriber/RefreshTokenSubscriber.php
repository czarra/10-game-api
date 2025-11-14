<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Gesdinet\JWTRefreshTokenBundle\Event\RefreshEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class RefreshTokenSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly int $tokenTTL
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RefreshEvent::class => 'onRefreshTokenIssued',
        ];
    }

    public function onRefreshTokenIssued(RefreshEvent $event): void
    {
        $data = [
            'token' => $event->getJWTString(),
            'refresh_token' => $event->getRefreshToken()->getRefreshToken(),
            'expires_in' => $this->tokenTTL,
        ];

        $event->setResponse(new JsonResponse($data));
    }
}
