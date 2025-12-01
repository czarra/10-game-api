<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

final class AdminAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private readonly LogoutUrlGenerator $logoutUrlGenerator,
        private readonly RequestStack $requestStack,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?RedirectResponse
    {
        // 1. Sprawdzamy, czy żądanie dotyczy ścieżki administracyjnej
        if (str_starts_with($request->getPathInfo(), '/admin')) {
            // 2. Upewniamy się, że użytkownik jest zalogowany (nie jest anonimowy)
            if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                // Dodajemy komunikat "flash", który zostanie wyświetlony na następnej stronie
                $this->requestStack->getSession()->getFlashBag()->add('error', 'Nie masz wystarczających uprawnień, aby uzyskać dostęp do panelu administratora.');

                // Generujemy URL do wylogowania
                $logoutUrl = $this->logoutUrlGenerator->getLogoutPath();

                // Cel przekierowania po wylogowaniu - strona logowania do admina
                $targetUrl = '/admin/login';

                // Tworzymy finalny URL wylogowania z parametrem _target.
                $finalLogoutUrl = $logoutUrl . '?_target=' . urlencode($targetUrl);

                // Zwracamy odpowiedź przekierowania. Mechanizm wylogowywania Symfony
                // zajmie się unieważnieniem sesji i tokenu, ale zachowa komunikaty flash.
                return new RedirectResponse($finalLogoutUrl);
            }
        }

        // Jeśli żądanie nie dotyczy ścieżki admina,
        // lub użytkownik nie był zalogowany, zwracamy null, aby Symfony kontynuowało
        // domyślną obsługę błędu 403 (tj. wyświetliło stronę błędu 403).
        return null;
    }
}