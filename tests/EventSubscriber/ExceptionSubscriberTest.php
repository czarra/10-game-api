<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use App\Service\Exception\GameAlreadyStartedException;
use App\Service\Exception\GameUnavailableException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExceptionSubscriberTest extends TestCase
{
    private ExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new ExceptionSubscriber();
    }

    public function testItIgnoresNonApiRoutes(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/some/other/path');
        $exception = new \Exception();
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }
    
    public function testItHandlesGenericHttpExceptions(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/test');
        $exception = new NotFoundHttpException('Not Found');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);
        
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Not Found', $response->getContent());
    }

    public function testItHandlesGenericExceptionsAs500(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/test');
        $exception = new \Exception('Something broke');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);
        
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Internal Server Error', $response->getContent());
    }

    /**
     * @dataProvider customExceptionProvider
     */
    #[DataProvider('customExceptionProvider')]
    public function testItHandlesCustomApiExceptions(\Throwable $exception, int $expectedStatusCode): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/games');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();

        $this->assertNotNull($response);
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
        $this->assertStringContainsString($exception->getMessage(), $response->getContent());
    }

    public static function customExceptionProvider(): array
    {
        return [
            [new GameUnavailableException(), 400],
            [new GameAlreadyStartedException(), 409],
            // Add other custom exceptions here
        ];
    }
}
