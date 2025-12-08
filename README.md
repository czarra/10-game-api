# Urban Games API

[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE.md)

A backend application for creating and managing geolocation-based urban games. It features an admin panel for game management and a REST API for client applications.

## Table of Contents

- [Project Description](#project-description)
- [Tech Stack](#tech-stack)
- [Getting Started Locally](#getting-started-locally)
- [Available Scripts](#available-scripts)
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

```bash
docker compose exec php bin/phpunit
```

## Available Scripts

The following scripts are available via Composer and can be run from within the `php` container (e.g., `docker compose exec php composer <script-name>`):

-   `post-install-cmd`: Runs automatically after `composer install`. It executes the `auto-scripts`.
-   `post-update-cmd`: Runs automatically after `composer update`. It executes the `auto-scripts`.

The `auto-scripts` include:
-   `cache:clear`: Clears the application cache.
-   `assets:install`: Installs web assets.

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

**In Development**

This project is currently in the development phase. The core features are being built as per the Product Requirements Document (PRD).

## License

This project is released under a **Proprietary License**. 

