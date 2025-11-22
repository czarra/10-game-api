<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\AuthenticationSuccessListener;
use PHPUnit\Framework\TestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationSuccessListenerTest extends TestCase
{
    public function testOnAuthenticationSuccessAddsExpiresIn(): void
    {
        $tokenTtl = 3600;
        $subscriber = new AuthenticationSuccessListener($tokenTtl);

        $initialData = ['token' => 'some_jwt_token'];
        $event = new AuthenticationSuccessEvent($initialData, $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class), new Response());

        $subscriber->onAuthenticationSuccess($event);

        $data = $event->getData();
        $this->assertArrayHasKey('expires_in', $data);
        $this->assertEquals($tokenTtl, $data['expires_in']);
        $this->assertArrayHasKey('token', $data);
    }
}
