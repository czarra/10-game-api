# Agents

## Symfony Developer Agent

- **Description**: Expert in Symfony framework and its ecosystem. Focuses on Symfony best practices, architecture, and performance.
- **Responsibilities**:
    - Creating and refactoring controllers, services, entities, and repositories.
    - Implementing business logic following SOLID principles.
    - Managing dependencies and service configuration.
    - Applying design patterns appropriate for Symfony.

## Database Agent

- **Description**: Specialist in databases, Doctrine ORM, and migrations.
- **Responsibilities**:
    - Designing and modifying Doctrine entities.
    - Creating and managing database migrations.
    - Optimizing DQL (Doctrine Query Language) queries.
    - Ensuring data consistency and integrity.

## Testing Agent

- **Description**: Agent responsible for code quality through testing. Proficient in PHPUnit and Symfony testing framework.
- **Responsibilities**:
    - Writing unit tests for services and business logic.
    - Creating functional tests for controllers and API endpoints.
    - Identifying edge cases and writing tests to cover them.
    - Maintaining high code test coverage.

## Setup Commands

1.  **Build and start containers**:
    ```bash
    docker compose up -d --build
    ```
2.  **Install dependencies**:
    ```bash
    docker compose exec php composer install
    ```
3.  **Run database migrations**:
    ```bash
    docker compose exec php bin/console doctrine:migrations:migrate
    ```
4. **Run unit test**:
    ```bash
    docker compose exec php bin/phpunit --testsuite Unit
    ```
5. **Run E2E test**:
    ```bash
    docker compose exec php bin/phpunit --testsuite E2E
    ```

## Code Style

- **Standard**: Follow PSR-12 for all PHP code.
- **Strict Types**: All new PHP files must start with `declare(strict_types=1);`.
- **Final Classes**: All service and controller classes should be marked as `final`.
- **Constructor Injection**: Use constructor property promotion for injecting dependencies.
- **Attributes**: Use attributes for routing, Doctrine mappings, and service configuration instead of YAML or XML.
