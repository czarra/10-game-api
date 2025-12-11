# Urban Games API

[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE.md)

A backend application for creating and managing geolocation-based urban games. It features an admin panel for game management and a REST API for client applications.

## Table of Contents

- [Project Description](#project-description)
- [Tech Stack](#tech-stack)
- [Getting Started Locally](#getting-started-locally)
- [Available Scripts](#available-scripts)
- [API Endpoints](#api-endpoints)
- [Project Scope](#project-scope)
- [Project Status](#project-status)
- [License](#license)

## Project Description

This project provides the backend infrastructure for a location-based urban gaming platform. Administrators can create, manage, and monitor games and their associated tasks through a dedicated admin panel. Registered users can participate in these games, completing tasks sequentially by verifying their geographical location. The winner is the user who completes the game in the shortest amount of time.

The system is composed of two main parts:
1.  **A REST API** for client applications (e.g., mobile apps) to handle user registration, login, game participation, and location verification.
2.  **An Admin Panel** for content and user management.

## Tech Stack

### Backend
- **PHP:** >=8.3
- **Framework:** Symfony 6.4
- **Database:** PostgreSQL
- **ORM:** Doctrine

### Admin Panel
- **Bundle:** Sonata Admin

### Infrastructure
- **Containerization:** Docker
- **Hosting:** Railway.app (via Docker image)
- **CI/CD:** GitHub Actions

### Testing
- **Unit & Functional Tests:** PHPUnit
- **API Testing:** Symfony TestClient
- **Test Data Generation:** DoctrineFixtures / Foundry

## Getting Started Locally

To set up and run the project on your local machine, follow these steps.

### Prerequisites

- Docker and docker compose installed.
- A `.env` file created in the project root.

### Installation using the Development Script

The easiest way to get the project running is to use the automated development script.

1.  **Create Environment Files:**
    The application requires separate environment files for development and testing. You can create them by copying the distribution file:
    ```bash
    cp .env.dist .env.dev
    ```
    Ensure the variables in these files are correctly set for your local environment, especially the database credentials. The script relies on `.env.dev` for the main application and `.env.test` for the test suite database.

2.  **Run the Development Script:**
    Execute the `run-dev.sh` script. It will handle everything: cleaning the environment, starting Docker containers, installing dependencies, and setting up both development and test databases.
    ```bash
    ./run-dev.sh
    ```

The application should now be running. You can access it at `http://localhost:8282`.

### Generating JWT Keys

The application uses JWT for API authentication, which requires a private and public key pair for signing tokens. You need to generate these keys manually.

1.  **Create the JWT directory**:
    First, ensure the directory for the keys exists.

    ```bash
    mkdir -p config/jwt
    ```

2.  **Generate the private key**:
    Run the following command to generate the private key. You will be prompted to create a passphrase. Choose a strong one and remember it.

    ```bash
    openssl genrsa -aes128 -passout pass:YourStrongPassphrase -out config/jwt/private.pem 4096
    ```
    *Replace `YourStrongPassphrase` with your actual passphrase.*

3.  **Generate the public key**:
    Next, generate the public key from your new private key. You'll use the same passphrase.

    ```bash
    openssl rsa -passin pass:YourStrongPassphrase -pubout -in config/jwt/private.pem -out config/jwt/public.pem
    ```

4.  **Set the passphrase in your environment file**:
    Finally, open your `.env.dev` file (and any other `.env` files like `.env.test`) and set the `JWT_PASSPHRASE` variable to the passphrase you chose.

    ```dotenv
    # .env.dev
    # ...
    JWT_PASSPHRASE="YourStrongPassphrase"
    ```

> **Important**: The `config/jwt/private.pem` file is highly sensitive and should **never** be committed to version control. Make sure your `.gitignore` file includes an entry for `config/jwt/private.pem` or the entire `config/jwt/` directory.

## Manual Administrator Creation

In a situation where you don't have access to an admin account (e.g., for initial setup or recovery), you can manually create one by inserting a record directly into the database.

**This is a two-step process: first, you generate a secure password hash, and then you insert the user.**

### Step 1: Generate a Secure Password Hash

Run the following command in your terminal, replacing `YourStrongPassword` with a secure password of your choice.

```bash
docker compose exec php bin/console security:hash-password YourStrongPassword
```

The output will be a long string, which is the securely hashed password. It will look something like this:

```
Password hash for user App\Entity\User:
$2y$13$n/fvy9jQW2n.vV.hDdrk6eYk3TjYC5tvea29kkj9G5/N5vGvF.7Gq
```

**Copy this entire hash string** for the next step.

### Step 2: Insert the User into the Database

1.  Open a connection to the PostgreSQL database inside the Docker container:

    ```bash
     docker compose exec database psql -U xgamesecret -d xgame
    ```

2.  Once you are in the `psql` shell, run the following SQL command. **Remember to replace the placeholder values**:
    *   Replace the example `id` with a new UUID (you can use an online generator).
    *   Replace `'admin@example.com'` with the desired email.
    *   Replace the example `password` hash with the one you copied in Step 1.

    ```sql
    INSERT INTO "users" (id, email, roles, password, created_at, updated_at)
    VALUES (
        'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        'admin@example.com',
        '["ROLE_ADMIN"]',
        '$2y$13$n/fvy9jQW2n.vV.hDdrk6eYk3TjYC5tvea29kkj9G5/N5vGvF.7Gq', -- PASTE YOUR HASH HERE
        NOW(),
        NOW()
    );
    ```

After executing the command, a new administrator account will be created, and you can use it to log in to the admin panel at `/admin`.

## Running Tests

To run the full test suite (PHPUnit), use the following command. This will execute the tests inside the PHP container against the test database.

1. **Run all test**:
    ```bash
    docker compose exec php bin/phpunit
    ```
2. **Run unit test**:
    ```bash
    docker compose exec php bin/phpunit --testsuite Unit
    ```
3. **Run E2E test**:
    ```bash
    docker compose exec php bin/phpunit --testsuite E2E
    ```

## Available Scripts

The following scripts are available via Composer and can be run from within the `php` container (e.g., `docker compose exec php composer <script-name>`):

-   `post-install-cmd`: Runs automatically after `composer install`. It executes the `auto-scripts`.
-   `post-update-cmd`: Runs automatically after `composer update`. It executes the `auto-scripts`.

The `auto-scripts` include:
-   `cache:clear`: Clears the application cache.
-   `assets:install`: Installs web assets.

## API Endpoints

The API documentation is interactively browseable using Swagger UI, available at `/api/doc`. This documentation provides detailed information about each endpoint, including request/response schemas, parameters, and example values.

Below is a list of available REST API endpoints. All endpoints (with the exception of registration and login) require authentication using a JWT token passed in the `Authorization: Bearer <token>` header.

### Authentication

---

#### User Registration

-   **Endpoint**: `POST /api/auth/register`
-   **Description**: Creates a new user account.
-   **Security**: None
-   **Request Body**:
    ```json
    {
      "email": "user@example.com",
      "password": "yourStrongPassword123"
    }
    ```
-   **Response (201 CREATED)**:
    ```json
    {
      "token": "ey...",
      "refresh_token": "abc..."
    }
    ```
-   **Response (409 CONFLICT)**: Returned when a user with the provided email address already exists.

---

#### User Login

-   **Endpoint**: `POST /api/auth/login`
-   **Description**: Authenticates a user and returns JWT tokens.
-   **Security**: None
-   **Request Body**:
    ```json
    {
      "email": "user@example.com",
      "password": "yourStrongPassword123"
    }
    ```
-   **Response (200 OK)**:
    ```json
    {
      "token": "ey...",
      "refresh_token": "abc..."
    }
    ```
-   **Response (401 UNAUTHORIZED)**: Returned in case of invalid login credentials.

---

#### Refresh Token

-   **Endpoint**: `POST /api/token/refresh`
-   **Description**: Generates a new JWT token based on a valid refresh token.
-   **Security**: None
-   **Request Body**:
    ```json
    {
      "refresh_token": "abc..."
    }
    ```
-   **Response (200 OK)**:
    ```json
    {
      "token": "ey...",
      "refresh_token": "def..."
    }
    ```

---

#### Logout

-   **Endpoint**: `POST /api/auth/logout`
-   **Description**: Invalidates all active refresh tokens for the logged-in user.
-   **Security**: JWT token required
-   **Response (204 NO CONTENT)**

---

### Games

---

#### List of Available Games

-   **Endpoint**: `GET /api/games`
-   **Description**: Returns a paginated list of games that are active and available to start.
-   **Security**: JWT token required
-   **Query Parameters**:
    -   `page` (int, optional, default: 1): Page number.
    -   `limit` (int, optional, default: 10): Number of results per page (max. 50).
-   **Response (200 OK)**: Paginated list of `GameListItemDto` objects.

---

#### List of User's Active Games

-   **Endpoint**: `GET /api/games/active`
-   **Description**: Returns a list of games that the logged-in user has started but not yet completed.
-   **Security**: JWT token required
-   **Response (200 OK)**: List of `ActiveGameListItemDto` objects.

---

#### Active Game Details

-   **Endpoint**: `GET /api/games/{userGameId}/active`
-   **Description**: Returns detailed information about a specific user's active game, including the current task.
-   **Security**: JWT token required
-   **URL Parameters**:
    -   `userGameId` (UUID): User's game session identifier.
-   **Response (200 OK)**: `ActiveGameDetailsDto` object.

---

#### List of Completed Games

-   **Endpoint**: `GET /api/games/completed`
-   **Description**: Returns a paginated list of games completed by the logged-in user.
-   **Security**: JWT token required
-   **Query Parameters**:
    -   `page` (int, optional, default: 1)
    -   `limit` (int, optional, default: 10, max. 50)
-   **Response (200 OK)**: Paginated list of `CompletedGameListItemDto` objects.

---

#### Game Details

-   **Endpoint**: `GET /api/games/{id}`
-   **Description**: Returns public, detailed information about a game.
-   **Security**: JWT token required
-   **URL Parameters**:
    -   `id` (UUID): Game identifier.
-   **Response (200 OK)**: `GameDetailsDto` object.

---

#### Start Game

-   **Endpoint**: `POST /api/games/{gameId}/start`
-   **Description**: Starts a new game for the logged-in user.
-   **Security**: JWT token required
-   **URL Parameters**:
    -   `gameId` (UUID): Identifier of the game to start.
-   **Response (201 CREATED)**: `StartGameResponseDto` object containing the game session ID (`userGameId`) and details of the first task.

---

#### Complete Task

-   **Endpoint**: `POST /api/games/{userGameId}/tasks/{taskId}/complete`
-   **Description**: Marks a task as complete after verifying the user's location.
-   **Security**: JWT token required
-   **URL Parameters**:
    -   `userGameId` (UUID): Game session identifier.
    -   `taskId` (UUID): Identifier of the completed task.
-   **Request Body**:
    ```json
    {
      "latitude": 52.2297,
      "longitude": 21.0122
    }
    ```
-   **Response (200 OK)**:
    -   If the game is not completed, returns a `TaskCompletionResponseDto` object with details of the next task.
    -   If it was the last task, returns a `GameCompletionResponseDto` object with a game summary.
-   **Response (409 CONFLICT)**: Returned when, for example, the location is invalid or the task is not the user's current task.

---

## Project Scope

### Key Features
- **Admin Panel:** Secure admin panel for full CRUD management of games and tasks, including soft deletes.
- **User Management:** User registration and login system (email/password).
- **Secure API:** REST API secured with JWT and refresh tokens.
- **Geolocation:** Server-side location verification using the Haversine algorithm.
- **Game Logic:** Time tracking for game completion and validation of task sequence.
- **Pagination:** All API list endpoints are paginated.
- **Logging:** Structured JSON logging for diagnostics.

### Boundaries (Out of Scope for MVP)
- No client-facing mobile application is included.
- No data import/export functionality.
- No social features like sharing results.
- Players participate individually (no teams).
- No ability to pause a game.
- Basic anti-cheat measures; no advanced fraud detection.

## Project Status

**MVP in Production**

The project is currently in the MVP (Minimum Viable Product) phase and has been deployed to a production environment. It is actively maintained and open for further feature development.

## License

This project is released under a **Proprietary License**. 

