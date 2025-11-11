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
  "email": "user@example.com",
  "password": "Password123!"
}
```
- **Response** (201):
```json
{
  "id": "uuid",
  "email": "user@example.com",
  "roles": ["ROLE_USER"],
  "createdAt": "2024-01-01T00:00:00Z"
}
```
- **Error Responses**: 400 (validation), 409 (email exists)

#### POST /api/auth/login
- **Description**: User login
- **Request Body**:
```json
{
  "email": "user@example.com",
  "password": "Password123!"
}
```
- **Response** (200):
```json
{
  "token": "jwt_access_token",
  "refresh_token": "refresh_token",
  "expires_in": 3600,
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "roles": ["ROLE_USER"]
  }
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

#### POST /api/user/games/{gameId}/start
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

#### POST /api/user/games/{userGameId}/tasks/{taskId}/complete
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

#### GET /api/user/games/active
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

#### GET /api/user/games/completed
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

### Admin Endpoints (Require ADMIN role)

#### GET /api/admin/games
- **Description**: Get all games (including unavailable) for admin
- **Headers**: Authorization: Bearer {token}
- **Query Parameters**:
    - `page` (default: 1)
    - `limit` (default: 10, max: 50)
    - `available` (boolean filter)
- **Response**: Similar to games list but with admin fields

#### POST /api/admin/games
- **Description**: Create a new game
- **Headers**: Authorization: Bearer {token}
- **Request Body**:
```json
{
  "name": "New Game",
  "description": "Game description",
  "isAvailable": false
}
```
- **Response**: 201 with game details:
```json
{
  "id": "uuid",
  "name": "Game Name",
  "description": "Game description"
}
```

#### PUT /api/admin/games/{id}
- **Description**: Update a game
- **Headers**: Authorization: Bearer {token}
- **Request Body**: Partial game data
- **Response**: 200 with updated game

#### DELETE /api/admin/games/{id}
- **Description**: Soft delete a game
- **Headers**: Authorization: Bearer {token}
- **Response**: 204 No Content

#### POST /api/admin/games/{gameId}/tasks
- **Description**: Add task to game
- **Headers**: Authorization: Bearer {token}
- **Request Body**:
```json
{
  "taskId": "uuid",
  "sequenceOrder": 1,
  "latitude": 52.229675,
  "longitude": 21.012230,
  "allowedDistance": 50
}
```
- **Response**: 201 with game task details

#### DELETE /api/admin/games/{gameId}/tasks/{gameTaskId}
- **Description**: Remove task from game (soft delete)
- **Headers**: Authorization: Bearer {token}
- **Response**: 204 No Content

#### GET /api/admin/stats/completions
- **Description**: Get game completion statistics
- **Headers**: Authorization: Bearer {token}
- **Query Parameters**:
    - `gameId` (filter by game)
    - `fromDate`, `toDate` (date range)
    - `page` (default: 1)
    - `limit` (default: 10, max: 50)
- **Response** (200):
```json
{
  "data": [
    {
      "userEmail": "user@example.com",
      "gameName": "Game Name",
      "completedAt": "2024-01-01T02:30:00Z",
      "completionTime": 9000
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