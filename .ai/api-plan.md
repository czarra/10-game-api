# REST API Plan

## 1. Resources
- **Users** - `users` table
- **Games** - `games` table
- **Tasks** - `tasks` table
- **GameTasks** - `game_tasks` table
- **UserGames** - `user_games` table
- **UserGameTasks** - `user_game_tasks` table
- **Auth** - Authentication operations (login, register, refresh tokens)

## 2. Endpoints

### Authentication Endpoints

#### POST /api/auth/register
- **Description**: Register a new user
- **Request Body**:
```json
{
  "username": "user@example.com",
  "password": "Password123!"
}
```
- **Response** (201):
```json
{
  "token": "jwt_access_token",
  "refresh_token": "refresh_token"
}
```
- **Error Responses**: 400 (validation), 409 (email exists)

#### POST /api/auth/login
- **Description**: User login
- **Request Body**:
```json
{
  "username": "user@example.com",
  "password": "Password123!"
}
```
- **Response** (200):
```json
{
  "token": "jwt_access_token",
  "refresh_token": "refresh_token",
  "expires_in": 3600
}
```
- **Error Responses**: 401 (invalid credentials)

#### POST /api/auth/refresh
- **Description**: Refresh access token
- **Request Body**:
```json
{
  "refresh_token": "refresh_token"
}
```
- **Response**: Same as login
- **Error Responses**: 401 (invalid refresh token)

#### POST /api/auth/logout
- **Description**: Logout user and invalidate refresh token
- **Headers**: Authorization: Bearer {token}
- **Response**: 204 No Content

### Games Endpoints

#### GET /api/games
- **Description**: Get paginated list of available games
- **Query Parameters**:
    - `page` (default: 1)
    - `limit` (default: 10, max: 50)
- **Headers**: Authorization: Bearer {token}
- **Response** (200):
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Game Name",
      "description": "Game description",
      "tasksCount": 5
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 100,
    "pages": 10
  }
}
```

#### GET /api/games/{id}
- **Description**: Get game details
- **Headers**: Authorization: Bearer {token}
- **Response** (200):
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

### User Games Endpoints

#### POST /api/games/{gameId}/start
- **Description**: Start a game for the current user
- **Headers**: Authorization: Bearer {token}
- **Response** (201):
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
- **Error Responses**: 404 (game not found), 409 (already active), 400 (game unavailable)

#### POST /api/games/{userGameId}/tasks/{taskId}/complete
- **Description**: Complete a task in user's active game
- **Headers**: Authorization: Bearer {token}
- **Request Body**:
```json
{
  "latitude": 52.229675,
  "longitude": 21.012230
}
```
- **Response** (200):
```json
{
  "completed": true,
  "nextTask": {
    "id": "uuid",
    "name": "Next Task",
    "description": "Task description",
    "sequenceOrder": 2
  },
  "gameCompleted": false
}
```
- **Error Responses**: 400 (wrong location), 409 (wrong task sequence), 404 (not found)

#### GET /api/games/active
- **Description**: Get user's active games
- **Headers**: Authorization: Bearer {token}
- **Response** (200):
```json
{
  "data": [
    {
      "userGameId": "uuid",
      "gameId": "uuid",
      "gameName": "Game Name",
      "startedAt": "2024-01-01T00:00:00Z",
      "completedTasks": 2,
      "totalTasks": 5,
      "currentTask": {
        "id": "uuid",
        "name": "Current Task",
        "sequenceOrder": 3
      }
    }
  ]
}
```

#### GET /api/games/{userGameId}/active
- **Description**: Get details for a single active game session.
- **Headers**: Authorization: Bearer {token}
- **Response** (200):
```json
{
  "userGameId": "uuid",
  "gameId": "uuid",
  "gameName": "Game Name",
  "startedAt": "2024-01-01T00:00:00Z",
  "completedTasks": 2,
  "totalTasks": 5,
  "currentTask": {
    "id": "uuid",
    "name": "Current Task",
    "sequenceOrder": 3
  }
}
```
- **Error Responses**: 404 (Not Found - if `userGameId` does not correspond to an active game for the current user)

#### GET /api/games/completed
- **Description**: Get user's completed games with pagination
- **Headers**: Authorization: Bearer {token}
- **Query Parameters**:
    - `page` (default: 1)
    - `limit` (default: 10, max: 50)
- **Response** (200):
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

### Panel Administracyjny (Sonata Admin)

Panel administracyjny jest realizowany za pomocą `SonataAdminBundle` i zapewnia interfejs webowy do zarządzania kluczowymi zasobami aplikacji. Dostęp do panelu jest ograniczony do użytkowników z rolą `ROLE_ADMIN`. Panel nie udostępnia publicznego API REST, lecz serwuje strony HTML z formularzami i listami danych.

Główne sekcje panelu administracyjnego:

#### Zarządzanie Grami i Zadaniami
- **Opis**: Umożliwia administratorom pełne zarządzanie (CRUD) grami (`Game`) i zadaniami (`Task`). Administratorzy mogą tworzyć nowe gry, definiować ich właściwości (nazwa, opis, dostępność) oraz zarządzać powiązanymi z nimi zadaniami.
- **Funkcjonalności**:
    - Tworzenie, edycja i listowanie gier.
    - **Oznaczanie gier jako usuniętych (soft delete)**. Usunięte gry nie będą widoczne dla użytkowników, ale pozostaną w bazie danych do celów archiwalnych.
    - Tworzenie, edycja i listowanie zadań.
    - **Oznaczanie zadań jako usuniętych (soft delete)**.
    - W ramach edycji gry, możliwość dodawania, usuwania i zmiany kolejności zadań w grze.
- **Główne ścieżki**:
    - `/admin/app/game/list` - Lista gier.
    - `/admin/app/game/create` - Formularz tworzenia nowej gry.
    - `/admin/app/game/{id}/edit` - Edycja gry i jej zadań.
    - `/admin/app/game/{id}/delete` - Akcja oznaczania gry jako usuniętej.
    - `/admin/app/task/list` - Lista wszystkich zadań.
    - `/admin/app/task/create` - Formularz tworzenia nowego zadania.

#### Statystyki Ukończonych Gier
- **Opis**: Sekcja tylko do odczytu, prezentująca statystyki dotyczące gier ukończonych przez użytkowników. Pozwala na monitorowanie aktywności i popularności gier.
- **Funkcjonalności**:
    - Wyświetlanie listy ukończonych gier (`UserGame`) z informacjami o użytkowniku, nazwie gry, datach rozpoczęcia i zakończenia oraz czasie trwania.
    - Filtrowanie wyników po grze i użytkowniku.
    - Sortowanie wyników po dowolnej kolumnie.
    - Paginacja wyników.
- **Główna ścieżka**:
    - `/admin/app/usergame/list` - Lista ukończonych gier.

## 3. Authentication and Authorization

### JWT Authentication
- Access tokens expire after 1 hour
- Refresh tokens stored in `user_tokens` table with expiration
- All endpoints except `/api/auth/*` require JWT token
- Token in Authorization header: `Bearer {token}`

### Role-Based Authorization
- **ROLE_USER**: Can access games, user games, and tasks
- **ROLE_ADMIN**: Additional access to admin endpoints
- Roles stored in `users.roles` JSON field

### RLS Integration
- API sets session variables for RLS:
    - `app.current_user_id` from JWT subject
    - `app.current_user_role` from JWT roles
- Protected tables (`user_games`, `user_game_tasks`) use RLS policies

## 4. Validation and Business Logic

### User Validation
- Email format and uniqueness
- Password: min 8 chars, 1 digit, 1 special character
- Role assignment on registration

### Game Validation
- Name uniqueness
- Minimum 3 tasks required before activation (`is_available = true`)
- Soft delete integrity

### Task Validation
- Coordinate validation (latitude: -90 to 90, longitude: -180 to 180)
- Allowed distance positive integer

### Game-Task Relationship
- Unique sequence order per game (enforced by database index)
- Task can be in multiple games

### User Game Logic
- One active game session per user per game (enforced by unique index)
- Task sequence validation during completion
- Automatic game completion when all tasks completed
- Time calculation from `started_at` to `completed_at`

### Location Validation
- Haversine formula for distance calculation
- Task completion requires location within `allowed_distance`
- Error response with distance information when validation fails

### Pagination
- Default page: 1
- Default limit: 10
- Maximum limit: 50
- Consistent pagination metadata across list endpoints

### Error Responses
Standard error format:
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": [
      {
        "field": "email",
        "message": "Email must be valid"
      }
    ]
  }
}
```

Common error codes:
- `VALIDATION_ERROR`: 400
- `AUTHENTICATION_FAILED`: 401
- `PERMISSION_DENIED`: 403
- `RESOURCE_NOT_FOUND`: 404
- `CONFLICT`: 409 (wrong task sequence, already active game)
- `LOCATION_VALIDATION_FAILED`: 400 (distance too large)