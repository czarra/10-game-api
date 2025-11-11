<analysis>
1. **Podsumowanie kluczowych punktów specyfikacji API**:
   - Endpoint: GET /api/games/{id}
   - Cel: Pobranie szczegółów gry wraz z jej zadaniami
   - Wymagana autoryzacja przez token Bearer
   - Struktura odpowiedzi zawiera UUID gry, nazwę, opis i listę zadań z kolejnością

2. **Wymagane i opcjonalne parametry**:
    - Wymagane:
        - Path parameter: {id} (UUID gry)
        - Header: Authorization: Bearer {token}
    - Opcjonalne: brak

3. **Niezbędne typy DTO i Command Modele**:
    - GameDetailsDto - dla odpowiedzi 200
    - TaskDetailsDto - dla elementów tablicy tasks
    - Nie wymaga Command Models (tylko odczyt)

4. **Logika serwisu**:
    - Nowy serwis: GameQueryService
    - Wykorzysta istniejące repozytoria: GameRepository, TaskRepository
    - Logika autoryzacji przez Security Symfony
    - Mapowanie encji na DTO

5. **Walidacja danych wejściowych**:
    - Walidacja UUID w parametrze ścieżki
    - Sprawdzenie istnieje gry i jej dostępności (is_available)
    - Weryfikacja tokena JWT przez Security Symfony

6. **Rejestrowanie błędów**:
    - Użycie LoggerInterface do logowania błędów
    - Rejestracja nieautoryzowanych prób dostępu
    - Logowanie błędów bazy danych

7. **Zagrożenia bezpieczeństwa**:
    - Nieautoryzowany dostęp do gier
    - UUID enumeration poprzez próby nieprawidłowych UUID
    - Ekspozycja danych przez brak weryfikacji dostępności gry

8. **Scenariusze błędów i kody stanu**:
    - 400: Nieprawidłowy format UUID
    - 401: Brak/Błędny token
    - 404: Gra nie istnieje lub niedostępna
    - 500: Błąd bazy danych/inny wewnętrzny
      </analysis>

# API Endpoint Implementation Plan: GET /api/games/{id}

## 1. Przegląd punktu końcowego
Endpoint służy do pobierania szczegółowych informacji o konkretnej grze miejskiej, w tym jej listy zadań z zachowaniem kolejności. Wymaga autoryzacji użytkownika przez token JWT.

## 2. Szczegóły żądania
- **Metoda HTTP**: GET
- **Struktura URL**: `/api/games/{id}`
- **Parametry**:
    - Wymagane:
        - `id` (UUID gry) w ścieżce URL
        - `Authorization: Bearer {token}` w nagłówku
    - Opcjonalne: brak
- **Request Body**: brak

## 3. Szczegóły odpowiedzi
- **Status 200**:
```json
{
  "id": "uuid",
  "name": "Game Name",
  "description": "Game description",
  "tasks": [
    {
      "id": "uuid",
      "name": "Task Name",
      "description": "Task description",
      "sequenceOrder": 1
    }
  ]
}
```
- **Status 400**: Nieprawidłowy format UUID
- **Status 401**: Brak autoryzacji
- **Status 404**: Gra nie znaleziona lub niedostępna

## 4. Przepływ danych
1. Kontroler przyjmuje żądanie i waliduje parametr UUID
2. System Security Symfony weryfikuje token JWT
3. GameQueryService pobiera dane gry z GameRepository
4. Repository wykonuje zapytanie Doctrine z JOIN na game_tasks i tasks
5. Dane są mapowane na GameDetailsDto i TaskDetailsDto
6. Zwrot odpowiedzi w formacie JSON

**Zapytanie SQL**:
```sql
SELECT g.id, g.name, g.description, 
       t.id as task_id, t.name as task_name, t.description as task_description, 
       gt.sequence_order
FROM games g
LEFT JOIN game_tasks gt ON g.id = gt.game_id AND gt.deleted_at IS NULL
LEFT JOIN tasks t ON gt.task_id = t.id AND t.deleted_at IS NULL
WHERE g.id = :gameId 
  AND g.is_available = true 
  AND g.deleted_at IS NULL
ORDER BY gt.sequence_order ASC
```

## 5. Względy bezpieczeństwa
- **Uwierzytelnianie**: Token JWT weryfikowany przez Symfony Security
- **Autoryzacja**: Sprawdzenie czy użytkownik ma rolę pozwalającą na dostęp
- **Walidacja**:
    - Walidacja UUID parametru ścieżki
    - Sprawdzenie czy gra jest dostępna (is_available = true)
    - Weryfikacja czy gra nie została usunięta (soft delete)
- **RLS**: Automatyczna izolacja danych przez polityki PostgreSQL

## 6. Obsługa błędów
| Błąd | Kod statusu | Komunikat |
|------|-------------|-----------|
| Nieprawidłowy format UUID | 400 | "Invalid game ID format" |
| Brak tokena autoryzacji | 401 | "Authentication required" |
| Nieprawidłowy/wygasły token | 401 | "Invalid or expired token" |
| Gra nie znaleziona | 404 | "Game not found" |
| Gra niedostępna | 404 | "Game is not available" |
| Błąd bazy danych | 500 | "Internal server error" |

## 7. Rozważania dotyczące wydajności
- **Indeksowanie**: Wykorzystanie istniejących indeksów na games(id, is_available), game_tasks(game_id)
- **Lazy Loading**: Unikanie N+1 przez użycie JOIN w repozytorium
- **Cache**: Rozważenie cache'owania odpowiedzi dla często odpytywanych gier
- **Paginacja**: Nie wymagana (liczba zadań jest ograniczona)

## 8. Etapy wdrożenia

### Krok 1: Utworzenie DTO
```php
// src/Dto/GameDetailsDto.php
final class GameDetailsDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        /** @var TaskDetailsDto[] */
        public readonly array $tasks
    ) {}
}

// src/Dto/TaskDetailsDto.php
final class TaskDetailsDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly int $sequenceOrder
    ) {}
}
```

### Krok 2: Implementacja repozytorium
```php
// src/Repository/GameRepository.php
final class GameRepository extends ServiceEntityRepository
{
    public function findAvailableGame(string $id): ?Game
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.gameTasks', 'gt')
            ->leftJoin('gt.task', 't')
            ->addSelect('gt', 't')
            ->where('g.id = :id')
            ->andWhere('g.isAvailable = true')
            ->andWhere('g.deletedAt IS NULL')
            ->andWhere('gt.deletedAt IS NULL')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
```

### Krok 3: Utworzenie serwisu
```php
// src/Service/GameQueryService.php
final class GameQueryService
{
    public function __construct(
        private GameRepository $gameRepository,
        private LoggerInterface $logger
    ) {}

    public function getGameDetails(string $gameId): ?GameDetailsDto
    {
        try {
            $game = $this->gameRepository->findAvailableGame($gameId);
            
            if (!$game) {
                return null;
            }

            $tasks = [];
            foreach ($game->getGameTasks() as $gameTask) {
                $task = $gameTask->getTask();
                $tasks[] = new TaskDetailsDto(
                    $task->getId(),
                    $task->getName(),
                    $task->getDescription(),
                    $gameTask->getSequenceOrder()
                );
            }

            return new GameDetailsDto(
                $game->getId(),
                $game->getName(),
                $game->getDescription(),
                $tasks
            );
        } catch (ORMException $e) {
            $this->logger->error('Database error fetching game', ['gameId' => $gameId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

### Krok 4: Implementacja kontrolera
```php
// src/Controller/GameController.php
#[Route('/api/games')]
final class GameController extends AbstractController
{
    #[Route('/{id}', name: 'api_games_get', methods: ['GET'])]
    public function getGame(
        string $id,
        GameQueryService $gameQueryService,
        UuidValidator $uuidValidator
    ): JsonResponse {
        // Walidacja UUID
        if (!$uuidValidator->validate($id)) {
            return new JsonResponse(['error' => 'Invalid game ID format'], 400);
        }

        $gameDetails = $gameQueryService->getGameDetails($id);
        
        if (!$gameDetails) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        return $this->json($gameDetails);
    }
}
```

### Krok 5: Konfiguracja bezpieczeństwa
```yaml
# config/packages/security.yaml
security:
    access_control:
        - path: ^/api/games
          roles: ROLE_USER
```

### Krok 6: Testy
```php
// tests/Controller/GameControllerTest.php
final class GameControllerTest extends WebTestCase
{
    public function testGetGameSuccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/games/{valid-uuid}', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer valid-token'
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonStructure([
            'id', 'name', 'description', 'tasks' => [
                ['id', 'name', 'description', 'sequenceOrder']
            ]
        ]);
    }
}
```