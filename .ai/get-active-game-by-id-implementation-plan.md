# API Endpoint Implementation Plan: GET /api/games/{userGameId}/active

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest dostarczenie szczegółowych informacji o pojedynczej, aktywnej sesji gry dla aktualnie zalogowanego użytkownika. Endpoint pozwala na pobranie statusu postępu, w tym liczby ukończonych zadań i szczegółów następnego zadania do wykonania.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/games/{userGameId}/active`
- **Parametry**:
  - **Wymagane**:
    - `userGameId` (w ścieżce): Identyfikator UUID sesji gry użytkownika (`UserGame.id`).
  - **Opcjonalne**: Brak.
- **Nagłówki**:
  - `Authorization`: `Bearer {token}` (wymagany do uwierzytelnienia).
- **Request Body**: Brak.

## 3. Wykorzystywane typy
Do implementacji niezbędne będą następujące klasy DTO (Data Transfer Object) w przestrzeni nazw `App\Dto`:

1.  **`GameMoreDetailsDto` (Nowy)**: Reprezentuje pełną strukturę odpowiedzi dla pojedynczej gry.
    ```php
    declare(strict_types=1);

    namespace App\Dto;

    final readonly class GameMoreDetailsDto
    {
        public function __construct(
            public string $userGameId,
            public string $gameId,
            public string $gameName,
            public string $startedAt,
            public int $completedTasks,
            public int $totalTasks,
            public ?CurrentTaskDto $currentTask,
        ) {}
    }
    ```

2.  **`CurrentTaskDto` (Istniejący)**: Reprezentuje następne zadanie do wykonania. Ta klasa jest już zaimplementowana i zostanie ponownie wykorzystana.
    ```php
    declare(strict_types=1);

    namespace App\Dto;

    final readonly class CurrentTaskDto
    {
        public function __construct(
            public string $id, // ID z encji GameTask
            public string $name,
            public int $sequenceOrder,
        ) {}
    }
    ```

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (200 OK)**: Zwraca obiekt JSON ze szczegółami aktywnej gry.
  ```json
  {
    "data": {
      "userGameId": "a1b2c3d4-e5f6-g7h8-i9j0-k1l2m3n4o5p6",
      "gameId": "b2c3d4e5-f6g7-h8i9-j0k1-l2m3n4o5p6q7",
      "gameName": "Przygoda w Centrum Miasta",
      "startedAt": "2024-01-01T12:00:00+00:00",
      "completedTasks": 2,
      "totalTasks": 5,
      "currentTask": {
        "id": "c3d4e5f6-g7h8-i9j0-k1l2-m3n4o5p6q7r8",
        "name": "Znajdź ukryty mural",
        "sequenceOrder": 3
      }
    }
  }
  ```
- **Odpowiedź błędu (404 Not Found)**: Zwracana, gdy gra nie istnieje, nie należy do użytkownika lub nie jest już aktywna.
  ```json
  {
    "message": "Active game not found."
  }
  ```

## 5. Przepływ danych
1.  Żądanie `GET /api/games/{userGameId}/active` trafia do `GameController`.
2.  Firewall Symfony weryfikuje token JWT. W przypadku błędu zwraca `401 Unauthorized`.
3.  Kontroler pobiera obiekt zalogowanego użytkownika (`User`) z kontekstu bezpieczeństwa.
4.  Kontroler wywołuje nową metodę `findActiveGameById(string $userGameId, UserInterface $user)` w serwisie `GameQueryService`.
5.  `GameQueryService` wywołuje nową, dedykowaną metodę w `UserGameRepository`, np. `findActiveGameDetails(string $userGameId, UserInterface $user)`.
6.  Metoda w repozytorium konstruuje zapytanie DQL lub QueryBuilder, które pobiera `UserGame` na podstawie `id` (`userGameId`) oraz `user_id`, a także sprawdza, czy `completedAt` jest `NULL`. Zapytanie to za pomocą `JOIN` i podzapytań agreguje wszystkie niezbędne dane (nazwa gry, liczba zadań, postęp, następne zadanie) w ramach jednego zapytania do bazy danych.
7.  Jeśli repozytorium nie znajdzie pasującego rekordu, zwraca `null`.
8.  `GameQueryService` sprawdza wynik z repozytorium. Jeśli jest `null`, rzuca wyjątek `NotFoundHttpException`, co spowoduje zwrócenie odpowiedzi `404 Not Found`.
9.  Jeśli dane zostały znalezione, serwis mapuje je na obiekt `GameMoreDetailsDto` (wraz z zagnieżdżonym `CurrentTaskDto`).
10. Kontroler otrzymuje obiekt DTO, serializuje go i zwraca w `JsonResponse` z kodem `200 OK`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Endpoint musi być dostępny wyłącznie dla uwierzytelnionych użytkowników. Zostanie to zrealizowane za pomocą atrybutu `#[Security("is_granted('ROLE_USER')")]` nad metodą w kontrolerze.
- **Autoryzacja**: Kluczowe jest zapobieganie wyciekowi danych między użytkownikami (IDOR). Zapytanie w `UserGameRepository` musi bezwzględnie zawierać warunek filtrujący po ID zalogowanego użytkownika (`... WHERE ug.id = :userGameId AND ug.user = :user`). Próba dostępu do nieautoryzowanego zasobu musi skutkować błędem `404 Not Found`.

## 7. Obsługa błędów
- **`401 Unauthorized`**: Zwracane automatycznie przez framework, gdy token JWT jest nieprawidłowy, wygasł lub go brakuje.
- **`404 Not Found`**: Zwracane w następujących przypadkach:
    - `UserGame` o podanym `userGameId` nie istnieje.
    - `UserGame` istnieje, ale nie należy do zalogowanego użytkownika.
    - `UserGame` należy do użytkownika, ale została już ukończona (`completedAt IS NOT NULL`).
- **`500 Internal Server Error`**: W przypadku nieoczekiwanych problemów (np. błąd połączenia z bazą danych), standardowy listener wyjątków Symfony przechwyci błąd, zaloguje go i zwróci odpowiedź `500`.

## 8. Rozważania dotyczące wydajności
- **Unikanie problemu N+1**: Zapytanie w `UserGameRepository` musi być zaprojektowane tak, aby pobrać wszystkie wymagane informacje (dane gry, łączna liczba zadań, liczba ukończonych zadań, dane następnego zadania) za jednym razem. Należy unikać dodatkowych zapytań w serwisie lub pętlach.
- **Indeksy bazodanowe**: Należy upewnić się, że kolumny `user_games.id` i `user_games.user_id` są zindeksowane, co jest domyślnym zachowaniem dla klucza głównego i obcego.

## 9. Etapy wdrożenia
1.  **Utworzenie DTO**: Zaimplementować klasę `GameMoreDetailsDto` w katalogu `src/Dto/`.
2.  **Aktualizacja Repozytorium**: Dodać nową metodę `findActiveGameDetails(string $userGameId, UserInterface $user): ?array` w `src/Repository/UserGameRepository.php`. Metoda ta powinna zawierać zoptymalizowane zapytanie DQL/QueryBuilder.
3.  **Aktualizacja Serwisu**: Dodać metodę `findActiveGameById(string $userGameId, UserInterface $user): GameMoreDetailsDto` w `src/Service/GameQueryService.php`. Metoda będzie wywoływać repozytorium, obsługiwać `NotFoundHttpException` i mapować wyniki na DTO. Należy upewnić się, że nie modyfikuje ona istniejącej logiki `findActiveGamesForUser`.
4.  **Utworzenie Metody w Kontrolerze**:
    - Dodać nową metodę w `src/Controller/GameController.php`.
    - Zdefiniować dla niej trasę `GET /api/games/{userGameId}/active` z atrybutem `#[Route]` i walidacją `uuid` dla parametru.
    - Zabezpieczyć metodę za pomocą atrybutu `#[Security("is_granted('ROLE_USER')")]`.
    - Wstrzyknąć `GameQueryService` i wywołać jego nową metodę.
    - Zwrócić `JsonResponse` z danymi i statusem 200.
5.  **Testy**:
    - Napisać test funkcjonalny w `tests/Controller/`, który zweryfikuje:
        - Poprawność struktury i danych dla istniejącej, aktywnej gry.
        - Zwrócenie błędu `404` dla nieistniejącego `userGameId`.
        - Zwrócenie błędu `404` przy próbie dostępu do gry innego użytkownika.
        - Zwrócenie błędu `404` dla gry, która została już ukończona.
        - Zwrócenie błędu `401` dla niezalogowanego użytkownika.
        - Poprawność obliczeń `completedTasks`, `totalTasks` i `currentTask`.
