# Plan schematu bazy danych PostgreSQL

## 1. Lista tabel

### `users`

Tabela przechowująca dane użytkowników aplikacji.

| Nazwa kolumny | Typ danych   | Ograniczenia                           | Opis                                           |
|---------------|--------------|----------------------------------------|------------------------------------------------|
| `id`          | UUID         | PRIMARY KEY, DEFAULT gen_random_uuid() | Unikalny identyfikator użytkownika.            |
| `email`       | VARCHAR(255) | UNIQUE, NOT NULL                       | Adres email użytkownika, używany do logowania. |
| `password`    | VARCHAR(255) | NOT NULL                               | Zahaszowane hasło użytkownika.                 |
| `roles`       | JSON         | NOT NULL                               | Role użytkownika w systemie (np. `ROLE_USER`). |
| `created_at`  | TIMESTAMPTZ  | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data i czas utworzenia konta.                  |
| `updated_at`  | TIMESTAMPTZ  | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data i czas ostatniej modyfikacji.             |

### `games`

Tabela przechowująca informacje o grach miejskich.

| Nazwa kolumny  | Typ danych   | Ograniczenia                           | Opis                                     |
|----------------|--------------|----------------------------------------|------------------------------------------|
| `id`           | UUID         | PRIMARY KEY, DEFAULT gen_random_uuid() | Unikalny identyfikator gry.              |
| `name`         | VARCHAR(255) | UNIQUE, NOT NULL                       | Nazwa gry.                               |
| `description`  | TEXT         | NOT NULL                               | Opis gry.                                |
| `is_available` | BOOLEAN      | NOT NULL, DEFAULT FALSE                | Status dostępności gry dla użytkowników. |
| `created_at`   | TIMESTAMPTZ  | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data i czas utworzenia gry.              |
| `updated_at`   | TIMESTAMPTZ  | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data i czas ostatniej modyfikacji.       |
| `deleted_at`   | TIMESTAMPTZ  | NULL                                   | Data i czas usunięcia (soft delete).     |

### `tasks`

Tabela przechowująca informacje o zadaniach, które mogą być częścią gier.

| Nazwa kolumny      | Typ danych     | Ograniczenia                           | Opis                                        |
|--------------------|----------------|----------------------------------------|---------------------------------------------|
| `id`               | UUID           | PRIMARY KEY, DEFAULT gen_random_uuid() | Unikalny identyfikator zadania.             |
| `name`             | VARCHAR(255)   | NOT NULL                               | Nazwa zadania.                              |
| `description`      | TEXT           | NOT NULL                               | Opis zadania.                               |
| `latitude`         | DECIMAL(10, 7) | NOT NULL                               | Szerokość geograficzna lokalizacji zadania. |
| `longitude`        | DECIMAL(10, 7) | NOT NULL                               | Długość geograficzna lokalizacji zadania.   |
| `allowed_distance` | INTEGER        | NOT NULL                               | Dopuszczalna odległość od celu w metrach.   |
| `created_at`       | TIMESTAMPTZ    | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data i czas utworzenia zadania.             |
| `updated_at`       | TIMESTAMPTZ    | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data i czas ostatniej modyfikacji.          |
| `deleted_at`       | TIMESTAMPTZ    | NULL                                   | Data i czas usunięcia (soft delete).        |

### `game_tasks`

Tabela pośrednicząca (pivot) dla relacji wiele-do-wielu między grami a zadaniami, przechowująca kolejność zadań w grze.

| Nazwa kolumny    | Typ danych  | Ograniczenia                           | Opis                                         |
|------------------|-------------|----------------------------------------|----------------------------------------------|
| `id`             | UUID        | PRIMARY KEY, DEFAULT gen_random_uuid() | Unikalny identyfikator rekordu relacji.      |
| `game_id`        | UUID        | NOT NULL, FOREIGN KEY (games.id)       | Identyfikator gry.                           |
| `task_id`        | UUID        | NOT NULL, FOREIGN KEY (tasks.id)       | Identyfikator zadania.                       |
| `sequence_order` | INTEGER     | NOT NULL                               | Numer porządkowy zadania w danej grze.       |
| `deleted_at`     | TIMESTAMPTZ | NULL                                   | Data i czas usunięcia relacji (soft delete). |

### `user_games`

Tabela śledząca postęp użytkownika w danej grze.

| Nazwa kolumny  | Typ danych  | Ograniczenia                           | Opis                                            |
|----------------|-------------|----------------------------------------|-------------------------------------------------|
| `id`           | UUID        | PRIMARY KEY, DEFAULT gen_random_uuid() | Unikalny identyfikator sesji gry użytkownika.   |
| `user_id`      | UUID        | NOT NULL, FOREIGN KEY (users.id)       | Identyfikator użytkownika.                      |
| `game_id`      | UUID        | NOT NULL, FOREIGN KEY (games.id)       | Identyfikator gry.                              |
| `started_at`   | TIMESTAMPTZ | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Czas rozpoczęcia gry przez użytkownika.         |
| `completed_at` | TIMESTAMPTZ | NULL                                   | Czas ukończenia gry. `NULL` oznacza grę w toku. |

### `user_game_tasks`

Tabela śledząca wykonanie poszczególnych zadań przez użytkownika w ramach sesji gry.

| Nazwa kolumny  | Typ danych  | Ograniczenia                           | Opis                                    |
|----------------|-------------|----------------------------------------|-----------------------------------------|
| `id`           | UUID        | PRIMARY KEY, DEFAULT gen_random_uuid() | Unikalny identyfikator rekordu.         |
| `user_game_id` | UUID        | NOT NULL, FOREIGN KEY (user_games.id)  | Identyfikator sesji gry użytkownika.    |
| `game_task_id` | UUID        | NOT NULL, FOREIGN KEY (game_tasks.id)  | Identyfikator zadania w kontekście gry. |
| `completed_at` | TIMESTAMPTZ | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Czas zaliczenia zadania.                |

### `user_tokens`

Tabela do zarządzania tokenami odświeżającymi (refresh tokens) dla JWT.

| Nazwa kolumny   | Typ danych   | Ograniczenia                     | Opis                                                |
|-----------------|--------------|----------------------------------|-----------------------------------------------------|
| `id`            | INT          | PRIMARY KEY                      | Unikalny identyfikator tokenu.                      |
| `username`      | VARCHAR(255) | NOT NULL, FOREIGN KEY (users.id) | Identyfikator użytkownika, do którego należy token. |
| `refresh_token` | VARCHAR(128) | UNIQUE, NOT NULL                 | Wartość tokenu odświeżającego.                      |
| `valid`         | TIMESTAMPTZ  | NOT NULL                         | Data i czas wygaśnięcia tokenu.                     |

## 2. Relacje między tabelami

- **`users` 1-do-wielu `user_games`**: Jeden użytkownik może brać udział w wielu grach.
- **`users` 1-do-wielu `user_tokens`**: Jeden użytkownik może mieć wiele tokenów odświeżających.
- **`games` 1-do-wielu `user_games`**: Jedna gra może być rozgrywana przez wielu użytkowników.
- **`games` wiele-do-wielu `tasks` (przez `game_tasks`)**: Jedna gra może składać się z wielu zadań, a jedno zadanie
  może być częścią wielu gier. Tabela `game_tasks` definiuje tę relację i przechowuje kolejność zadań (
  `sequence_order`).
- **`user_games` 1-do-wielu `user_game_tasks`**: Jedna sesja gry użytkownika składa się z wielu zaliczonych zadań.
- **`game_tasks` 1-do-wielu `user_game_tasks`**: Jeden rekord `game_task` (konkretne zadanie w konkretnej grze) może być
  zaliczony przez wielu graczy w ramach ich sesji.

## 3. Indeksy

### Indeksy standardowe

- Klucze podstawowe (PK) i obce (FK) są domyślnie indeksowane.
- Indeksy `UNIQUE` na `users(email)`, `games(name)`, `user_tokens(token)`.

### Indeksy częściowe i złożone

- **`game_tasks`**: Unikalny indeks częściowy zapewniający unikalność `sequence_order` w obrębie jednej gry dla
  nieusuniętych zadań.
  ```sql
  CREATE UNIQUE INDEX game_tasks_game_id_sequence_order_unique
  ON game_tasks (game_id, sequence_order)
  WHERE deleted_at IS NULL;
  ```
- **`user_games`**: Unikalny indeks częściowy blokujący możliwość rozpoczęcia tej samej gry wielokrotnie przez tego
  samego użytkownika, jeśli nie została ona jeszcze ukończona.
  ```sql
  CREATE UNIQUE INDEX user_games_user_id_game_id_unique_active
  ON user_games (user_id, game_id);
  ```
- **`user_game_tasks`**: Indeks złożony w celu optymalizacji zapytań o postęp zadań w grze.
  ```sql
  CREATE INDEX user_game_tasks_user_game_id_game_task_id_idx
  ON user_game_tasks (user_game_id, game_task_id);
  ```

## 4. Zasady PostgreSQL (Row-Level Security)

Zgodnie z wymaganiami, RLS zostanie włączone dla tabel zawierających dane wrażliwe użytkowników, aby zapewnić izolację
danych.

- **Tabela `user_games`**:
  ```sql
  ALTER TABLE user_games ENABLE ROW LEVEL SECURITY;
  CREATE POLICY user_games_isolation_policy ON user_games
  FOR ALL
  USING (
    user_id = current_setting('app.current_user_id')::UUID
    OR
    current_setting('app.current_user_role') = 'ADMIN'
  );
  ```
- **Tabela `user_game_tasks`**:
  ```sql
  ALTER TABLE user_game_tasks ENABLE ROW LEVEL SECURITY;
  CREATE POLICY user_game_tasks_isolation_policy ON user_game_tasks
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_games ug
      WHERE ug.id = user_game_id AND ug.user_id = current_setting('app.current_user_id')::UUID
    )
    OR
    current_setting('app.current_user_role') = 'ADMIN'
  );
  ```

## 5. Dodatkowe uwagi

- **Typ `UUID`**: Użycie `UUID` jako kluczy głównych pomaga ukryć wewnętrzne identyfikatory i ułatwia synchronizację
  danych w systemach rozproszonych.
- **Soft Delete**: Kolumny `deleted_at` w tabelach `games`, `tasks` i `game_tasks` implementują mechanizm "miękkiego
  usuwania", co pozwala na zachowanie integralności danych historycznych.
- **Współrzędne geograficzne**: Typ `DECIMAL(10, 7)` zapewnia wystarczającą precyzję dla współrzędnych geograficznych,
  minimalizując błędy zaokrągleń.
- **RLS i `app.current_user_id`**: Implementacja RLS zakłada, że aplikacja będzie ustawiać zmienną sesyjną
  `app.current_user_id` dla każdego zapytania, co jest standardową praktyką przy korzystaniu z tego mechanizmu.
- **Walidacja liczby zadań**: Zgodnie z ustaleniami, walidacja blokująca aktywację gry z mniej niż 3 zadaniami (
  `is_available = true`) będzie realizowana po stronie logiki aplikacji, a nie na poziomie bazy danych.

