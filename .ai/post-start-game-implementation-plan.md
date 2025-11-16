# API Endpoint Implementation Plan: POST /api/games/{gameId}/start

## 1. Przegląd punktu końcowego
Ten punkt końcowy umożliwia zalogowanemu użytkownikowi rozpoczęcie nowej sesji gry miejskiej. Po pomyślnym uruchomieniu tworzony jest rekord `UserGame`, a w odpowiedzi zwracane są szczegóły rozpoczętej gry wraz z pierwszym zadaniem do wykonania.

## 2. Szczegóły żądania
- **Metoda HTTP**: `POST`
- **Struktura URL**: `/api/games/{gameId}/start`
- **Parametry**:
  - **Wymagane**:
    - `gameId` (w ścieżce): Identyfikator UUID gry, którą użytkownik chce rozpocząć.
    - `Authorization` (nagłówek): Token uwierzytelniający Bearer (`Bearer {token}`).
  - **Opcjonalne**: Brak.
- **Request Body**: Brak.

## 3. Wykorzystywane typy
Do implementacji zostaną utworzone następujące obiekty DTO w przestrzeni nazw `App\Dto`:

- **`StartGameResponseDto`**:
  ```php
  final readonly class StartGameResponseDto
  {
      public function __construct(
          public string $userGameId,
          public string $gameId,
          public string $startedAt,
          public CurrentTaskDto $currentTask,
      ) {}
  }
  ```

- **`CurrentTaskDto`**:
  ```php
  final readonly class CurrentTaskDto
  {
      public function __construct(
          public string $id,
          public string $name,
          public string $description,
          public int $sequenceOrder,
      ) {}
  }
  ```

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (201 Created)**:
  ```json
  {
    "userGameId": "uuid",
    "gameId": "uuid",
    "startedAt": "2024-01-01T00:00:00Z",
    "currentTask": {
      "id": "uuid",
      "name": "First Task",
      "description": "Task description",
      "sequenceOrder": 1
    }
  }
  ```
- **Odpowiedzi błędów**: Zobacz sekcję "Obsługa błędów".

## 5. Przepływ danych
1.  Żądanie `POST` trafia do `GameController`.
2.  Symfony Security weryfikuje token JWT i udostępnia obiekt `User` w kontekście bezpieczeństwa.
3.  Kontroler pobiera encję `Game` na podstawie `gameId` z URL (za pomocą `ParamConverter`).
4.  Kontroler wywołuje metodę `startGameForUser(Game $game, User $user)` w serwisie `GamePlayService`.
5.  `GamePlayService` wykonuje następujące operacje w ramach transakcji bazodanowej:
    a. Sprawdza, czy gra jest dostępna (`$game->isAvailable()`). Jeśli nie, rzuca `GameUnavailableException`.
    b. Sprawdza, czy gra ma jakiekolwiek zadania. Jeśli nie, rzuca `GameHasNoTasksException`.
    c. Wykorzystuje `UserGameRepository`, aby sprawdzić, czy użytkownik nie ma już aktywnej (nieukończonej) sesji dla tej gry. Jeśli tak, rzuca `GameAlreadyStartedException`.
    d. Pobiera pierwsze zadanie dla gry z `GameTaskRepository` (z `sequenceOrder = 1`).
    e. Tworzy nową instancję encji `UserGame`, ustawiając `user`, `game` i `startedAt`.
    f. Zapisuje nową encję `UserGame` w bazie danych za pomocą `EntityManager`.
6.  Serwis zwraca nowo utworzoną encję `UserGame` do kontrolera.
7.  Kontroler mapuje dane z encji `UserGame` i powiązanego pierwszego zadania (`GameTask`) na `StartGameResponseDto`.
8.  Kontroler zwraca `JsonResponse` z DTO i statusem `201 Created`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Punkt końcowy musi być chroniony i dostępny tylko dla uwierzytelnionych użytkowników. Konfiguracja w `security.yaml` zapewni, że dostęp do ścieżki `/api/games/**` wymaga roli `ROLE_USER`.
- **Autoryzacja**: Logika biznesowa musi operować wyłącznie na obiekcie `User` pobranym z kontekstu bezpieczeństwa (`$this->getUser()`), aby zapewnić, że operacje są wykonywane w imieniu zalogowanego użytkownika.
- **Walidacja danych**: `gameId` jest automatycznie walidowane jako UUID przez `ParamConverter`. Wszelkie inne dane wejściowe (w przyszłości) muszą być walidowane.

## 7. Obsługa błędów
Dedykowany `ExceptionListener` będzie przechwytywał wyjątki z warstwy serwisowej i mapował je na odpowiednie odpowiedzi HTTP.

| Kod statusu | Wyjątek (propozycja) | Opis |
|---|---|---|
| `400 Bad Request` | `GameUnavailableException` | Gra nie jest dostępna (`is_available = false`). |
| `400 Bad Request` | `GameHasNoTasksException` | Gra nie zawiera żadnych zadań. |
| `401 Unauthorized` | (Obsługiwane przez Symfony) | Brak lub nieprawidłowy token JWT. |
| `404 Not Found` | `NotFoundHttpException` | Gra o podanym `gameId` nie istnieje. |
| `409 Conflict` | `GameAlreadyStartedException` | Użytkownik ma już aktywną sesję w tej grze. |
| `500 Internal Server Error` | Dowolny inny `\Throwable` | Wewnętrzny błąd serwera (np. błąd bazy danych). |

## 8. Rozważania dotyczące wydajności
- Zapytania do bazy danych (sprawdzanie aktywnej gry, pobieranie pierwszego zadania) powinny być zoptymalizowane i umieszczone w dedykowanych metodach w repozytoriach (`UserGameRepository`, `GameTaskRepository`).
- Należy unikać nadmiarowych zapytań i stosować `lazy loading` tam, gdzie to możliwe, jednak w tym przypadku większość potrzebnych danych powinna być pobrana za pomocą jednego lub dwóch precyzyjnych zapytań.

## 9. Etapy wdrożenia
1.  **Utworzenie DTO**: Zaimplementuj klasy `StartGameResponseDto` i `CurrentTaskDto` w katalogu `src/Dto/`.
2.  **Aktualizacja serwisu**:
    - Dodaj nową metodę publiczną `startGameForUser(Game $game, UserInterface $user): UserGame` do `GamePlayService`.
    - Wewnątrz metody zaimplementuj logikę walidacji (dostępność gry, brak aktywnej sesji, istnienie zadań).
    - Zdefiniuj i rzucaj dedykowane wyjątki dla każdego scenariusza błędu.
    - Dodaj logikę tworzenia i zapisywania nowej encji `UserGame`.
3.  **Aktualizacja repozytoriów**:
    - W `UserGameRepository` utwórz metodę `findActiveGameForUser(User $user, Game $game): ?UserGame`, która sprawdzi, czy istnieje `UserGame` z `completed_at IS NULL`.
    - W `GameTaskRepository` utwórz metodę `findFirstTaskForGame(Game $game): ?GameTask` do pobrania zadania z `sequenceOrder = 1`.
4.  **Aktualizacja kontrolera**:
    - W `GameController` utwórz nową metodę `startGame(Game $game)`.
    - Zabezpiecz metodę atrybutem `#[IsGranted('ROLE_USER')]`.
    - Dodaj atrybut trasy: `#[Route('/api/games/{gameId}/start', name: 'api_user_game_start', methods: ['POST'])]`.
    - Wstrzyknij `GamePlayService` i wywołaj nową metodę.
    - Zmapuj wynik na `StartGameResponseDto`.
    - Zwróć `JsonResponse` z kodem `201`.
5.  **Obsługa wyjątków**: Zaktualizuj `ExceptionListener`, aby obsługiwał nowe, dedykowane wyjątki i zwracał odpowiednie kody statusu oraz komunikaty błędów w formacie JSON.
6.  **Testy**:
    - Utwórz testy funkcjonalne dla `GameController`, które pokryją:
        - Scenariusz pomyślny (status 201 i poprawna struktura odpowiedzi).
        - Scenariusze błędów (400, 401, 404, 409).
    - Utwórz testy jednostkowe dla `GamePlayService`, mockując repozytoria i sprawdzając logikę biznesową.
