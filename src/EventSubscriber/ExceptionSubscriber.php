<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\InvalidTaskSequenceException;
use App\Exception\TaskAlreadyCompletedException;
use App\Exception\WrongLocationException;
use App\Service\Exception\GameAlreadyStartedException;
use App\Service\Exception\GameHasNoTasksException;
use App\Service\Exception\GameUnavailableException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // We only want to handle exceptions for the API
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $response = match (true) {
            $exception instanceof GameUnavailableException => new JsonResponse(['error' => $exception->getMessage()], 400),
            $exception instanceof GameHasNoTasksException => new JsonResponse(['error' => $exception->getMessage()], 400),
            $exception instanceof GameAlreadyStartedException,
            $exception instanceof InvalidTaskSequenceException,
            $exception instanceof TaskAlreadyCompletedException,
            $exception instanceof WrongLocationException => new JsonResponse(['error' => $exception->getMessage()], 409),
            $exception instanceof HttpExceptionInterface => new JsonResponse(['error' => $exception->getMessage()], $exception->getStatusCode()),
            default => new JsonResponse(['error' => 'Internal Server Error'], 500),
        };

        $event->setResponse($response);
    }
}
