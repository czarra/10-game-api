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

- Docker and Docker Compose installed.
- A `.env` file created in the project root.

### Installation

1.  **Create Environment File:**
    It is recommended to create a `.env` file in the project root. You can copy an existing `.env.dist` file if available.
    ```bash
    cp .env.dist .env
    ```
    Update the variables in the `.env` file with your local configuration, especially the database credentials (`DOCKER_POSTGRES_USER`, `DOCKER_POSTGRES_PASSWORD`, `DOCKER_POSTGRES_DB`).

2.  **Build and Start Containers:**
    Use Docker Compose to build the images and start the services in the background.
    ```bash
    docker-compose up -d --build
    ```

3.  **Install Dependencies:**
    Access the PHP container and install the Composer dependencies.
    ```bash
    docker-compose exec php composer install
    ```

4.  **Database Setup:**
    Run the database migrations to set up the required tables.
    ```bash
    docker-compose exec php bin/console doctrine:migrations:migrate
    ```
5.  **Fixtures load:**
    Create an initial admin and user.
    ```bash
    docker-compose exec php bin/console doctrine:fixtures:load --no-interaction 
    ```

The application should now be running. You can access it at `http://localhost:8282`.

## Available Scripts

The following scripts are available via Composer and can be run from within the `php` container (e.g., `docker-compose exec php composer <script-name>`):

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

