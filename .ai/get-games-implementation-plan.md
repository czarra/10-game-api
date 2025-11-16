# API Endpoint Implementation Plan: GET /api/games

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest dostarczenie paginowanej listy gier miejskich, które są aktualnie dostępne dla graczy. Odpowiedź będzie zawierać kluczowe informacje o każdej grze oraz liczbę zadań, które się na nią składają. Dostęp jest ograniczony do uwierzytelnionych użytkowników.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/games`
- **Parametry**:
  - **Opcjonalne (Query)**:
    - `page` (integer, default: 1): Numer strony wyników.
    - `limit` (integer, default: 10, max: 50): Liczba wyników na stronie.
- **Nagłówki**:
  - **Wymagane**:
    - `Authorization: Bearer {token}`: Token uwierzytelniający JWT.

## 3. Wykorzystywane typy
- **DTO**:
  - **`GameListItemDto`**: Obiekt transferu danych dla pojedynczej gry na liście.
    ```php
    final class GameListItemDto
    {
        public function __construct(
            public readonly Uuid $id,
            public readonly string $name,
            public readonly string $description,
            public readonly int $tasksCount,
        ) {}
    }
    ```
- **Struktura odpowiedzi**:
  - Odpowiedź będzie opakowana w standardowy format JSON z polami `data` i `pagination`.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": "a1b2c3d4-e5f6-7890-1234-567890abcdef",
        "name": "Przygoda w sercu miasta",
        "description": "Odkryj tajemnice starówki.",
        "tasksCount": 12
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 50,
      "pages": 5
    }
  }
  ```

## 5. Przepływ danych
1.  Żądanie `GET /api/games` trafia do `GameController`.
2.  Firewall `api` (Symfony Security) przechwytuje żądanie i weryfikuje token JWT.
3.  Metoda w kontrolerze waliduje parametry `page` i `limit` z obiektu `Request`.
4.  Kontroler wywołuje metodę `findAvailableGames(int $page, int $limit)` z serwisu `GameQueryService`.
5.  `GameQueryService` oblicza `offset` na podstawie strony i limitu, a następnie wywołuje metodę `createAvailableGamesPaginator(int $offset, int $limit)` z `GameRepository`.
6.  `GameRepository` tworzy zapytanie DQL, które:
    - Wybiera encje `Game`.
    - Zlicza powiązane `GameTask` dla każdej gry (`COUNT(gt.id)`).
    - Filtruje wyniki, aby pokazać tylko gry z `is_available = true`.
    - Używa `Doctrine\ORM\Tools\Pagination\Paginator` do obsługi paginacji.
7.  `GameQueryService` otrzymuje paginator, iteruje po wynikach i mapuje każdą encję `Game` wraz z liczbą zadań na obiekt `GameListItemDto`.
8.  Serwis konstruuje i zwraca tablicę zawierającą listę DTOs oraz dane paginacyjne (`page`, `limit`, `total`, `pages`).
9.  Kontroler otrzymuje dane z serwisu i zwraca `JsonResponse` z kodem statusu 200.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Trasa musi być chroniona przez firewall `api` w `config/packages/security.yaml`, wymagając ważnego tokenu JWT.
- **Autoryzacja**: Dostęp do zasobu jest przyznawany każdemu uwierzytelnionemu użytkownikowi (rola `ROLE_USER`).
- **Walidacja danych wejściowych**: Parametry `page` i `limit` muszą być rygorystycznie walidowane w kontrolerze, aby zapobiec błędom i potencjalnym atakom (np. przez podanie bardzo dużej wartości `limit`).

## 7. Obsługa błędów
- **`400 Bad Request`**: Zwracany, gdy `page` lub `limit` są nieprawidłowe (np. nieliczbowe, ujemne, `limit > 50`). Odpowiedź powinna zawierać komunikat o błędzie.
- **`401 Unauthorized`**: Zwracany przez komponent Security, gdy token JWT jest nieobecny, nieprawidłowy lub wygasł.
- **`500 Internal Server Error`**: Zwracany w przypadku nieoczekiwanych problemów po stronie serwera (np. błąd połączenia z bazą danych). Błąd powinien być logowany.

## 8. Rozważania dotyczące wydajności
- **Paginacja**: Użycie `Doctrine\ORM\Tools\Pagination\Paginator` jest kluczowe, ponieważ wykonuje on dwa zapytania SQL: jedno do zliczenia wszystkich wyników i drugie do pobrania danych dla bieżącej strony, co jest wydajne.
- **Zapytanie SQL**: Zapytanie DQL powinno być zoptymalizowane, aby zliczać zadania (`tasksCount`) w jednym zapytaniu z `GROUP BY`, zamiast polegać na lazy loading, co mogłoby prowadzić do problemu N+1.
- **Indeksy**: Należy upewnić się, że kolumna `is_available` w tabeli `games` jest zindeksowana, aby przyspieszyć filtrowanie.

## 9. Etapy wdrożenia
1.  **Utworzenie DTO**: Zdefiniuj klasę `GameListItemDto` w katalogu `src/Dto/`.
2.  **Aktualizacja Repozytorium**: W `src/Repository/GameRepository.php` dodaj publiczną metodę `createAvailableGamesPaginator(int $offset, int $limit): Paginator`. Zapytanie DQL wewnątrz powinno wyglądać mniej więcej tak:
    ```dql
    SELECT g, COUNT(gt.id) as tasksCount
    FROM App\Entity\Game g
    LEFT JOIN g.gameTasks gt
    WHERE g.isAvailable = true AND g.deletedAt IS NULL AND gt.deletedAt IS NULL
    GROUP BY g.id
    ORDER BY g.createdAt DESC
    ```
3.  **Aktualizacja Serwisu**: W `src/Service/GameQueryService.php` utwórz publiczną metodę `findAvailableGames(int $page, int $limit): array`. Metoda ta będzie korzystać z repozytorium, przetwarzać wyniki z paginatora na `GameListItemDto` i zwracać tablicę z danymi i metadanymi paginacji.
4.  **Utworzenie Metody w Kontrolerze**: W `src/Controller/GameController.php` dodaj nową metodę obsługującą trasę `/api/games`.
5.  **Dodanie Trasy i Walidacji**:
    - Użyj atrybutu `#[Route('/api/games', name: 'api_game_list', methods: ['GET'])]`.
    - W metodzie kontrolera dodaj walidację dla `page` i `limit` przy użyciu komponentu `Validator` lub asercji.
6.  **Połączenie Warstw**: W kontrolerze wstrzyknij `GameQueryService`, wywołaj nową metodę i zwróć `JsonResponse` z wynikiem.
7.  **Testy Funkcjonalne**: Utwórz nowy plik testowy `tests/Controller/GameControllerTest.php` (lub zaktualizuj istniejący). Dodaj testy sprawdzające:
    - Poprawne działanie dla żądania z domyślnymi parametrami (200 OK).
    - Poprawne działanie paginacji (zmiana `page` i `limit`).
    - Obsługę błędów dla nieprawidłowych parametrów (400 Bad Request).
    - Odrzucenie żądania bez tokenu (401 Unauthorized).
    - Sprawdzenie struktury odpowiedzi JSON.
