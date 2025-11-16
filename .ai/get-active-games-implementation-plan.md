# API Endpoint Implementation Plan: GET /api/games/active

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest dostarczenie zalogowanemu użytkownikowi listy wszystkich gier, które rozpoczął, ale których jeszcze nie ukończył. Odpowiedź zawiera kluczowe informacje o postępie w każdej grze, w tym liczbę ukończonych zadań oraz szczegóły następnego zadania do wykonania.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/games/active`
- **Parametry**:
  - **Wymagane**: Brak.
  - **Opcjonalne**: Brak.
- **Nagłówki**:
  - `Authorization`: `Bearer {token}` (wymagany)
- **Request Body**: Brak.

## 3. Wykorzystywane typy
Do implementacji niezbędne będą następujące klasy DTO (Data Transfer Object) w przestrzeni nazw `App\Dto`:

1.  **`ActiveGameListItemDto`**: Reprezentuje pojedynczy element na liście aktywnych gier.
    ```php
    final readonly class ActiveGameListItemDto
    {
        public function __construct(
            public string $userGameId,
            public string $gameId,
            public string $gameName,
            public string $description,
            public string $startedAt,
            public int $completedTasks,
            public int $totalTasks,
            public ?CurrentTaskDto $currentTask,
        ) {}
    }
    ```

2.  **`CurrentTaskDto`**: Reprezentuje następne zadanie do wykonania.
    ```php
    final readonly class CurrentTaskDto
    {
        public function __construct(
            public string $id, // ID z encji GameTask
            public string $name,
            public string $description,
            public int $sequenceOrder,
        ) {}
    }
    ```

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (200 OK)**:
  ```json
  {
    "data": [
      {
        "userGameId": "a1b2c3d4-e5f6-g7h8-i9j0-k1l2m3n4o5p6",
        "gameId": "b2c3d4e5-f6g7-h8i9-j0k1-l2m3n4o5p6q7",
        "gameName": "Przygoda w Centrum Miasta",
        "description": "Przygoda w Centrum Miasta",
        "startedAt": "2024-01-01T12:00:00+00:00",
        "completedTasks": 2,
        "totalTasks": 5,
        "currentTask": {
          "id": "c3d4e5f6-g7h8-i9j0-k1l2-m3n4o5p6q7r8",
          "name": "Znajdź ukryty mural",
          "description": "Znajdź ukryty mural",
          "sequenceOrder": 3
        }
      }
    ]
  }
  ```
- **Pusta odpowiedź (200 OK)**: Jeśli użytkownik nie ma aktywnych gier, `data` będzie pustą tablicą.
  ```json
  {
    "data": []
  }
  ```

## 5. Przepływ danych
1.  Żądanie `GET /api/games/active` trafia do `GameController`.
2.  Symfony Security Middleware weryfikuje token JWT. Jeśli jest nieprawidłowy, zwraca `401 Unauthorized`.
3.  Kontroler pobiera obiekt zalogowanego użytkownika (`User`) z kontekstu bezpieczeństwa.
4.  Kontroler wywołuje metodę `findActiveGamesForUser(UserInterface $user)` w serwisie `GameQueryService`.
5.  `GameQueryService` deleguje zadanie do `UserGameRepository`, wywołując nową, dedykowaną metodę (np. `findActiveGamesDetailsByUser()`).
6.  Metoda w repozytorium konstruuje zapytanie DQL lub QueryBuilder, które:
    - Wybiera `UserGame` dla danego `user_id`, gdzie `completedAt` jest `NULL`.
    - Używa `LEFT JOIN` do połączenia z `Game`, aby pobrać `gameName`.
    - Używa podzapytań lub agregacji do zliczenia:
        - Całkowitej liczby zadań dla gry (`totalTasks`) z `GameTask`.
        - Ukończonych zadań przez użytkownika (`completedTasks`) z `UserGameTask`.
    - Identyfikuje następne zadanie (`currentTask`) poprzez znalezienie `GameTask` o najniższym `sequenceOrder`, którego `id` nie znajduje się wśród ukończonych `UserGameTask` dla danej `UserGame`.
7.  Repozytorium zwraca surowe dane zoptymalizowane do mapowania.
8.  `GameQueryService` mapuje wyniki na tablicę obiektów `ActiveGameListItemDto`.
9.  Kontroler otrzymuje tablicę DTO, serializuje ją do formatu JSON i zwraca w obiekcie `JsonResponse` z kodem `200 OK`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Endpoint musi być chroniony i dostępny tylko dla użytkowników z rolą `ROLE_USER`. Zostanie to zdefiniowane w atrybucie `#[Security]` nad metodą kontrolera.
- **Autoryzacja**: Wszystkie zapytania do bazy danych muszą być ściśle powiązane z ID zalogowanego użytkownika. Należy unikać przekazywania ID użytkownika jako parametru i zawsze polegać na obiekcie `User` z kontekstu bezpieczeństwa.

## 7. Obsługa błędów
- **`401 Unauthorized`**: Zwracane automatycznie przez framework, gdy token jest nieprawidłowy, wygasł lub go brakuje.
- **`500 Internal Server Error`**: W przypadku nieoczekiwanych problemów (np. błąd połączenia z bazą danych, błąd w logice zapytania DQL), standardowy listener wyjątków Symfony przechwyci błąd, zaloguje go i zwróci odpowiedź 500.

## 8. Rozważania dotyczące wydajności
- **Problem N+1**: Kluczowe jest uniknięcie wykonywania zapytań w pętli dla każdej aktywnej gry. Należy zaimplementować jedną, złożoną kwerendę w `UserGameRepository`, która pobierze wszystkie niezbędne dane za jednym razem.
- **Indeksy**: Należy upewnić się, że kolumny używane w warunkach `WHERE` i `JOIN` (`user_id`, `game_id`, `completedAt`) są odpowiednio zindeksowane w bazie danych.

## 9. Etapy wdrożenia
1.  **Utworzenie DTO**: Zaimplementować klasy `ActiveGameListItemDto` i `CurrentTaskDto` w katalogu `src/Dto/`.
2.  **Aktualizacja Repozytorium**: Dodać nową metodę `findActiveGamesDetailsByUser(UserInterface $user): array` w `src/Repository/UserGameRepository.php`. Metoda ta powinna zawierać zoptymalizowane zapytanie DQL/QueryBuilder do pobrania wszystkich potrzebnych danych.
3.  **Aktualizacja Serwisu**: Dodać metodę `findActiveGamesForUser(UserInterface $user): array` w `src/Service/GameQueryService.php`. Metoda ta będzie wywoływać repozytorium i mapować wyniki na obiekty DTO.
4.  **Utworzenie Metody w Kontrolerze**:
    - Dodać nową metodę w `src/Controller/GameController.php`.
    - Zdefiniować dla niej trasę `GET /api/games/active` za pomocą atrybutu `#[Route]`.
    - Zabezpieczyć metodę za pomocą atrybutu `#[Security("is_granted('ROLE_USER')")]`.
    - Wstrzyknąć `GameQueryService` i wywołać jego metodę.
    - Zwrócić `JsonResponse` z danymi i statusem 200.
5.  **Testy**:
    - Napisać test funkcjonalny w `tests/Controller/`, który zweryfikuje:
        - Poprawność struktury i danych dla użytkownika z aktywnymi grami.
        - Zwrócenie pustej tablicy dla użytkownika bez aktywnych gier.
        - Zwrócenie błędu `401` dla niezalogowanego użytkownika.
        - Poprawność obliczeń `completedTasks`, `totalTasks` i `currentTask`.
