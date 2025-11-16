# API Endpoint Implementation Plan: POST /api/games/{userGameId}/tasks/{taskId}/complete

## 1. Przegląd punktu końcowego
Ten punkt końcowy umożliwia użytkownikowi ukończenie określonego zadania w ramach aktywnej gry miejskiej. System weryfikuje lokalizację użytkownika oraz poprawność kolejności wykonywanego zadania. Po pomyślnym ukończeniu zadania, API zwraca informację o następnym zadaniu lub statusie ukończenia całej gry.

## 2. Szczegóły żądania
- **Metoda HTTP**: `POST`
- **Struktura URL**: `/api/games/{userGameId}/tasks/{taskId}/complete`
- **Parametry ścieżki**:
  - **Wymagane**:
    - `userGameId` (UUID): Identyfikator aktywnej sesji gry użytkownika (`UserGame`).
    - `taskId` (UUID): Identyfikator zadania (`Task`), które użytkownik próbuje ukończyć.
- **Nagłówki**:
  - **Wymagane**:
    - `Authorization: Bearer {token}`: Token uwierzytelniający JWT.
- **Request Body**:
  ```json
  {
    "latitude": 52.229675,
    "longitude": 21.012230
  }
  ```

## 3. Wykorzystywane typy
- **Request DTO**:
  - `App\Dto\TaskCompletionRequestDto`:
    - `public readonly float $latitude`
    - `public readonly float $longitude`
- **Response DTOs**:
  - `App\Dto\TaskCompletionResponseDto`:
    - `public readonly bool $completed`
    - `public readonly ?NextTaskDto $nextTask`
    - `public readonly bool $gameCompleted`
  - `App\Dto\NextTaskDto`:
    - `public readonly Uuid $id`
    - `public readonly string $name`
    - `public readonly string $description`
    - `public readonly int $sequenceOrder`

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (200 OK)**:
  ```json
  {
    "completed": true,
    "nextTask": {
      "id": "uuid-of-next-task",
      "name": "Next Task Name",
      "description": "Description of the next task.",
      "sequenceOrder": 2
    },
    "gameCompleted": false
  }
  ```
  lub jeśli gra została ukończona:
  ```json
  {
    "completed": true,
    "nextTask": null,
    "gameCompleted": true
  }
  ```
- **Odpowiedzi błędów**:
  - `400 Bad Request`: Nieprawidłowe dane wejściowe (np. brak `latitude`/`longitude`).
  - `401 Unauthorized`: Nieprawidłowy lub brakujący token JWT.
  - `403 Forbidden`: Próba dostępu do nie swojej gry.
  - `404 Not Found`: Nie znaleziono zasobu (`UserGame` lub `Task`).
  - `409 Conflict`: Błąd logiki biznesowej (np. zła lokalizacja, zła kolejność zadań).
  - `500 Internal Server Error`: Błędy serwera.

## 5. Przepływ danych
1.  Żądanie `POST` trafia do `GameController::completeTask()`.
2.  System bezpieczeństwa Symfony weryfikuje token JWT i uwierzytelnia użytkownika.
3.  Kontroler deserializuje ciało żądania do `TaskCompletionRequestDto` i waliduje je.
4.  Kontroler pobiera encję `UserGame` i weryfikuje uprawnienia użytkownika do niej.
5.  Kontroler wywołuje metodę `GamePlayService::completeTask()` przekazując zalogowanego użytkownika, `UserGame`, `taskId` oraz DTO z danymi.
6.  `GamePlayService` wykonuje główną logikę:
    a. Sprawdza, czy gra nie jest już ukończona.
    b. Weryfikuje, czy zadanie o podanym `taskId` jest następnym w kolejności do wykonania.
    c. Sprawdza, czy zadanie nie zostało już ukończone.
    d. Używa `GeolocationService` do weryfikacji, czy odległość od celu jest w dozwolonym zakresie.
    e. Jeśli walidacja przejdzie pomyślnie, tworzy nową encję `UserGameTask` i zapisuje ją w bazie danych.
    f. Sprawdza, czy to było ostatnie zadanie. Jeśli tak, aktualizuje `UserGame`, ustawiając `completedAt`.
    g. Przygotowuje i zwraca `TaskCompletionResponseDto`.
7.  Kontroler serializuje DTO odpowiedzi do formatu JSON i zwraca odpowiedź `200 OK`.
8.  W przypadku błędu walidacji lub logiki biznesowej, rzucany jest odpowiedni wyjątek, który jest mapowany na kod błędu HTTP (4xx/5xx) przez listener wyjątków.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Endpoint musi być chroniony i dostępny tylko dla zalogowanych użytkowników. Zostanie to zrealizowane za pomocą `security.yaml` i systemu JWT.
- **Autoryzacja**: Kluczowe jest zapewnienie, że użytkownik może modyfikować tylko własną sesję gry. W kontrolerze, przed wywołaniem serwisu, należy sprawdzić, czy `UserGame` należy do zalogowanego użytkownika. Można to zrobić za pomocą `#[Security("is_granted('EDIT', userGame)")]` na metodzie kontrolera, co wymaga implementacji odpowiedniego `Voter`a, lub przez ręczne sprawdzenie w kodzie.

## 7. Rozważania dotyczące wydajności
- Zapytania do bazy danych w `GamePlayService` powinny być zoptymalizowane. Należy pobrać wszystkie powiązane dane (np. `UserGame` z kolekcją `UserGameTask`) za pomocą jednego zapytania z `JOIN`, aby uniknąć problemu N+1.
- Obliczenia geolokalizacyjne powinny być wydajne. Użycie formuły Haversine jest standardowym i akceptowalnym podejściem.

## 8. Etapy wdrożenia
1.  **Utworzenie DTOs**:
    - Zdefiniować klasy `TaskCompletionRequestDto`, `TaskCompletionResponseDto` i `NextTaskDto` w katalogu `src/Dto/`.
    - Dodać adnotacje walidacyjne (`NotNull`, `Type`) do `TaskCompletionRequestDto`.
2.  **Utworzenie wyjątków niestandardowych**:
    - Zdefiniować klasy wyjątków w `src/Service/` lub dedykowanym katalogu `src/Exception/`: `WrongLocationException`, `InvalidTaskSequenceException`, `TaskAlreadyCompletedException`.
3.  **Aktualizacja `GameController`**:
    - Dodać nową metodę `completeTask(Request $request, UserGame $userGame, Task $task)`.
    - Zabezpieczyć metodę za pomocą atrybutu `#[Route]` oraz `#[Security]`.
    - Zaimplementować deserializację, walidację DTO i wywołanie `GamePlayService`.
4.  **Rozbudowa `GamePlayService`**:
    - Dodać nową publiczną metodę `completeTask(User $user, UserGame $userGame, Task $task, TaskCompletionRequestDto $requestDto): TaskCompletionResponseDto`.
    - Zaimplementować całą logikę biznesową opisaną w sekcji "Przepływ danych".
5.  **Rozbudowa `GeolocationService`**:
    - Upewnić się, że serwis posiada metodę do obliczania odległości między dwoma punktami geograficznymi w metrach.
6.  **Konfiguracja obsługi wyjątków**:
    - Stworzyć lub zaktualizować `ExceptionListener`, aby mapował niestandardowe wyjątki na odpowiednie kody statusu HTTP (409 Conflict).
7.  **Napisanie testów**:
    - Dodać testy funkcjonalne dla nowego punktu końcowego w `tests/Controller/`, które pokryją scenariusz sukcesu oraz wszystkie przypadki błędów (400, 403, 404, 409).
    - Dodać testy jednostkowe dla nowej logiki w `GamePlayService`.
