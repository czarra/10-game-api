# API Endpoint Implementation Plan: GET /api/games/completed

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest dostarczenie zalogowanemu użytkownikowi listy jego ukończonych gier. Wyniki będą paginowane, a każdy element listy będzie zawierał kluczowe informacje o danej rozgrywce, takie jak czas jej trwania i liczba zadań.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/games/completed`
- **Parametry Query**:
  - **Opcjonalne**:
    - `page` (int): Numer strony wyników. Domyślnie: `1`. Musi być `> 0`.
    - `limit` (int): Liczba wyników na stronę. Domyślnie: `10`. Musi być w zakresie `1-50`.
- **Nagłówki**:
  - `Authorization`: `Bearer {token}` - Wymagany do uwierzytelnienia użytkownika.

## 3. Wykorzystywane typy

### DTO
- **`CompletedGameListItemDto`** (`src/Dto/CompletedGameListItemDto.php`): Nowy obiekt transferu danych do reprezentacji pojedynczej ukończonej gry w odpowiedzi.
  ```php
  final readonly class CompletedGameListItemDto
  {
      public function __construct(
          public string $userGameId,
          public string $gameId,
          public string $gameName,
          public string $startedAt,
          public string $completedAt,
          public int $completionTime, // in seconds
          public int $totalTasks
      ) {}
  }
  ```
- **`PaginationDto`**: Generyczny obiekt DTO do przechowywania informacji o paginacji.
  ```php
  final readonly class PaginationDto
  {
      public function __construct(
          public int $page,
          public int $limit,
          public int $total,
          public int $pages
      ) {}
  }
  ```

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (200 OK)**:
  ```json
  {
    "data": [
      {
        "userGameId": "uuid",
        "gameId": "uuid",
        "gameName": "Game Name",
        "startedAt": "2024-01-01T00:00:00Z",
        "completedAt": "2024-01-01T02:30:00Z",
        "completionTime": 9000,
        "totalTasks": 5
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 25,
      "pages": 3
    }
  }
  ```

## 5. Przepływ danych
1.  Żądanie `GET` trafia do nowej metody w `GameController`.
2.  Kontroler waliduje parametry `page` i `limit`. W przypadku błędu zwraca `400 Bad Request`.
3.  Kontroler pobiera obiekt zalogowanego użytkownika z kontekstu bezpieczeństwa Symfony.
4.  Kontroler wywołuje metodę `GameQueryService->findCompletedForUser($user, $page, $limit)`.
5.  `GameQueryService` wywołuje nową metodę w `UserGameRepository`, np. `findCompletedByUserPaginated(User $user, int $page, int $limit)`.
6.  Metoda w `UserGameRepository` tworzy zapytanie `QueryBuilder` do encji `UserGame`:
    - Filtruje wyniki po `user_id` zalogowanego użytkownika.
    - Filtruje wyniki, gdzie `completedAt` jest `NOT NULL`.
    - Sortuje wyniki od najnowszych (`completedAt` DESC).
    - Wykonuje `JOIN` z encją `Game`, aby pobrać `game.name`.
    - Wykonuje `LEFT JOIN` z `game.gameTasks`, aby zliczyć `totalTasks`.
    - Oblicza `completionTime` używając `EXTRACT(EPOCH FROM (ug.completedAt - ug.startedAt))`.
    - Używa `Doctrine\ORM\Tools\Pagination\Paginator` do obsługi paginacji.
7.  `GameQueryService` otrzymuje paginowane wyniki z repozytorium.
8.  Serwis iteruje po wynikach (encjach `UserGame`) i mapuje je na obiekty `CompletedGameListItemDto`.
9.  Serwis konstruuje i zwraca obiekt zawierający listę DTOs oraz dane paginacyjne.
10. Kontroler serializuje zwrócony obiekt do formatu JSON i zwraca odpowiedź `200 OK`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Endpoint musi być zabezpieczony w `config/packages/security.yaml` w ramach `api` firewall, wymagając `IS_AUTHENTICATED_FULLY`.
- **Autoryzacja**: Logika w `UserGameRepository` musi bezwzględnie filtrować wyniki na podstawie ID zalogowanego użytkownika, aby zapobiec dostępowi do danych innych użytkowników.

## 7. Obsługa błędów
- **`400 Bad Request`**: Zwracany, gdy parametry `page` lub `limit` są nieprawidłowe (np. nie są liczbami, `page <= 0`, `limit <= 0` lub `limit > 50`).
- **`401 Unauthorized`**: Zwracany przez framework Symfony, gdy brakuje tokenu JWT lub jest on nieprawidłowy.
- **`500 Internal Server Error`**: Zwracany w przypadku nieoczekiwanych problemów po stronie serwera (np. błąd bazy danych). Błąd powinien być zalogowany.

## 8. Rozważania dotyczące wydajności
- **Indeksowanie**: Należy upewnić się, że kolumny `user_id` i `completed_at` w tabeli `user_games` są zaindeksowane, aby przyspieszyć filtrowanie i sortowanie.
- **Paginator Doctrine**: `Paginator` wykonuje dodatkowe zapytanie `COUNT`, co jest akceptowalne dla tego przypadku użycia. Przy bardzo dużych zbiorach danych można by rozważyć alternatywne strategie paginacji.
- **Liczba zapytań**: Zapytanie powinno być tak skonstruowane, aby zminimalizować liczbę zapytań do bazy danych (problem N+1) poprzez odpowiednie `JOIN` i `SELECT`.

## 9. Etapy wdrożenia
1.  **Utworzenie DTO**: Stwórz plik `src/Dto/CompletedGameListItemDto.php` zgodnie z definicją w sekcji 3.
2.  **Aktualizacja Repozytorium**: Dodaj metodę `findCompletedByUserPaginated(User $user, int $page, int $limit)` do klasy `UserGameRepository`. Zaimplementuj logikę zapytania za pomocą `QueryBuilder` i `Paginator`.
3.  **Aktualizacja Serwisu**: Dodaj metodę `findCompletedForUser(UserInterface $user, int $page, int $limit)` do `GameQueryService`. Zaimplementuj logikę wywołania repozytorium i mapowania wyników na DTO.
4.  **Utworzenie Trasy i Kontrolera**:
    - W `src/Controller/GameController.php` dodaj nową metodę obsługującą trasę `GET /api/games/completed`.
    - Zaimplementuj nową metodę w `GameController`. Metoda powinna:
        - Być zabezpieczona (`#[IsGranted('IS_AUTHENTICATED_FULLY')]`).
        - Walidować parametry `page` i `limit`.
        - Pobierać użytkownika.
        - Wywoływać `GameQueryService`.
        - Zwracać `JsonResponse`.
5.  **Testy**: Napisz test funkcjonalny dla nowego punktu końcowego w `tests/Controller/GameControllerTest.php`. Test powinien obejmować:
    - Przypadek pomyślny z domyślną paginacją.
    - Przypadek z niestandardowymi parametrami `page` i `limit`.
    - Sprawdzenie poprawności struktury odpowiedzi i typów danych.
    - Test błędu walidacji dla `limit > 50`.
    - Test próby dostępu bez uwierzytelnienia (oczekiwany kod 401).
