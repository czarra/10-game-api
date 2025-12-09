# Project Onboarding: Urban Games API

## Welcome

Welcome to the Urban Games API project! This document provides a summary to help you get up to speed with the codebase, architecture, and current development focus. The project is a backend application for creating and managing geolocation-based urban games, featuring an admin panel and a REST API for client applications.

## Project Overview & Structure

The application is built with **PHP 8.3** on the **Symfony 6.4** framework. It uses Docker for containerization, making the local setup straightforward.

The project follows a standard Symfony structure:
-   `src/`: Contains all the application's PHP code (Entities, Controllers, Services).
-   `config/`: Holds all configuration files, including routes and service definitions.
-   `migrations/`: Contains Doctrine database migrations.
-   `tests/`: PHPUnit tests for the application.
-   `public/`: The web root, with the front controller `index.php`.
-   `.ai/`: Contains project-related documentation and plans.
-   `README.md`: The main source for setup and project overview.

## Core Modules

### `src/Controller`

-   **Role:** Handles incoming HTTP requests and calls appropriate services. It's the main entry point for the API.
-   **Key Files/Areas:**
    -   `AuthController.php`: Manages user registration, login, and token management.
    -   `GameController.php`: Exposes all endpoints related to game interaction (listing games, starting, completing tasks).
-   **Recent Focus:** No major recent changes, mostly stable.

### `src/Service`

-   **Role:** Contains the core business logic of the application, keeping controllers thin.
-   **Key Files/Areas:**
    -   `GamePlayService.php`: Orchestrates the logic for starting a game and completing tasks. This is a critical service.
    -   `GameQueryService.php`: Provides methods for fetching game data for various API endpoints.
    -   `GeolocationService.php`: Handles the server-side validation of a user's geographical location.
    -   `RegistrationService.php`: Manages the new user registration logic.
-   **Recent Focus:** The logic within these services is central to the application and is a key area for understanding the project.

### `src/Entity`

-   **Role:** Defines the database schema through Doctrine ORM entities. These classes represent the core domain objects.
-   **Key Files/Areas:**
    -   `User.php`: Represents a registered user.
    -   `Game.php`: Represents a game created by an admin.
    -   `Task.php`: Represents a single task within a game.
    -   `UserGame.php`: A pivot entity tracking a user's participation and progress in a specific game.
-   **Recent Focus:** The entity structure is well-defined and appears stable. Recent migrations have focused on adding more data based on this schema.

### Admin Panel (Sonata Admin)

-   **Role:** Provides a web-based UI for administrators to manage games, tasks, and users without directly interacting with the database.
-   **Key Files/Areas:** `src/Admin/` directory contains the admin class definitions.
-   **Recent Focus:** The admin panel is a core feature for content management.

## Key Contributors

-   **Radoslaw Michałkiewicz:** Appears to be the primary developer, with recent commits focused on documentation, CI/CD, and database seeding.

## Overall Takeaways & Recent Focus

-   The project is a classic Symfony API with a clear, layered architecture.
-   There is a strong focus on code quality, with defined standards (PSR-12, strict types) and a testing suite.
-   Recent development has shifted towards documentation, database seeding, and preparing for deployment, which suggests the core feature set is approaching completion.
-   The application is heavily reliant on the Doctrine ORM and its entity relationships.

## Potential Complexity/Areas to Note

-   **Game Logic in `GamePlayService`:** This service contains the state machine for user progression through a game. Understanding the validation rules and entity state changes here is crucial.
-   **Geolocation Verification:** The `GeolocationService` uses the Haversine formula for distance calculation. Precision and potential edge cases in location validation might be a sensitive area.
-   **Doctrine Relationships:** The many-to-many and one-to-many relationships centered around the `UserGame` entity are complex. Be mindful of how data is queried and persisted to avoid performance issues.
-   **Authentication Flow:** The JWT and refresh token mechanism can be complex to debug. Pay attention to the configuration in `config/packages/lexik_jwt_authentication.yaml`.

## Questions for the Team

1.  What is the current status of the client-side application? Is there a prototype to test against?
2.  Are there any known performance bottlenecks, especially in the game query services?
3.  What is the strategy for handling cheating or location spoofing beyond the basic server-side checks?
4.  How is the Sonata Admin panel typically customized or extended? Are there examples in the codebase?
5.  What are the highest priority features or bug fixes on the roadmap for the next sprint?
6.  Could you walk me through the complete lifecycle of a `UserGame` from creation to completion?
7.  What parts of the codebase are considered legacy or are slated for future refactoring?

## Next Steps

1.  Follow the `README.md` to set up the local development environment using the `run-dev.sh` script.
2.  Once set up, access the API documentation at `http://localhost:8282/api/doc` and familiarize yourself with the available endpoints.
3.  Manually create a test user, register, and log in using the API to get a feel for the authentication flow.
4.  Try to start and complete a game using the API endpoints to understand the game flow.
5.  Review the unit and functional tests in the `tests/` directory to see how the application logic is being verified.

## Development Environment Setup

The primary method for setting up the local environment is via the provided script.

1.  **Create Environment File:** Copy the distribution file:
    ```bash
    cp .env.dist .env.dev
    ```
2.  **Run Setup Script:** This script builds and starts Docker containers, installs dependencies, and runs database migrations for both the development and test environments.
    ```bash
    ./run-dev.sh
    ```
The application will be accessible at `http://localhost:8282`, and the admin panel at `http://localhost:8282/admin`.

## Helpful Resources

-   **API Documentation (local):** `http://localhost:8282/api/doc`
-   **Admin Panel (local):** `http://localhost:8282/admin`
-   **Symfony 6.4 Documentation:** [https://symfony.com/doc/6.4/index.html](https://symfony.com/doc/6.4/index.html)
-   **Doctrine ORM Documentation:** [https://www.doctrine-project.org/projects/orm.html](https://www.doctrine-project.org/projects/orm.html)
-   **Sonata Admin Documentation:** [https://docs.sonata-project.org/projects/SonataAdminBundle/en/4.x/](https://docs.sonata-project.org/projects/SonataAdminBundle/en/4.x/)
