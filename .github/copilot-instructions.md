# AI Rules 

## CODING_PRACTICES

### Guidelines for SUPPORT_LEVEL

#### SUPPORT_BEGINNER

- When running in agent mode, execute up to 3 actions at a time and ask for approval or course correction afterwards.
- Write code with clear variable names and include explanatory comments for non-obvious logic. Avoid shorthand syntax and complex patterns.
- Provide full implementations rather than partial snippets. Include import statements, required dependencies, and initialization code.
- Add defensive coding patterns and clear error handling. Include validation for user inputs and explicit type checking.
- Suggest simpler solutions first, then offer more optimized versions with explanations of the trade-offs.
- Briefly explain why certain approaches are used and link to relevant documentation or learning resources.
- When suggesting fixes for errors, explain the root cause and how the solution addresses it to build understanding. Ask for confirmation before proceeding.
- Offer introducing basic test cases that demonstrate how the code works and common edge cases to consider.

## SYMFONY 6.4 & Doctrine Development Rules

### Key Principles

- Write concise, technical responses with accurate PHP examples
- Follow Symfony and Doctrine best practices and conventions
- Use object-oriented programming with focus on SOLID principles
- Prefer iteration and modularization over duplication
- Use descriptive variable and method names in camelCase
- Favor dependency injection and service containers

### PHP/Symfony Standards

- Always use declare(strict_types=1) in all PHP files
- Use constructor property promotion for dependency injection
- Apply final by default for classes unless explicitly designed for extension
- Use private visibility unless required otherwise
- Prefer readonly properties for immutable data
- Use PHP 8.2+ features (enums, readonly classes, etc.) when appropriate
- Follow PSR-12 coding standards

### Project Structure & Conventions

- Controllers in src/Controller/, suffixed with Controller
- Services in src/Service/, suffixed with Service
- Entities in src/Entity/, suffixed with Entity
- Repositories in src/Repository/, suffixed with Repository
- Follow Symfony's modular directory structure
- Use namespaces that match directory structure
- Group related functionality in dedicated directories
- Separate domain logic from infrastructure concerns

### Doctrine ORM Practices

- Use attributes for entity mapping instead of annotations
- Implement repository classes for complex queries
- Use lifecycle callbacks for entity events
- Define proper column types and constraints
- Use DTOs for data transfer between layers
- Implement proper database transactions
- Use migrations for database schema changes

### Repository Pattern

- Extend ServiceEntityRepository for custom repositories
- Keep complex query logic in repository classes
- Use QueryBuilder for dynamic queries
- Implement specific finder methods rather than generic ones
- Use parameter binding to prevent SQL injection

### Service Layer

- Use dependency injection through constructors
- Keep services focused and single-responsibility
- Use interface segregation for service contracts
- Implement proper logging in services
- Handle exceptions appropriately in service layer

### Event System

- Use event subscribers for cross-cutting concerns
- Implement domain events for business logic
- Keep event handlers small and focused
- Use proper event naming conventions

### Validation

- Use Symfony validator component
- Define constraints using attributes
- Create custom validators when needed
- Validate at both form and service layers

### Security

- Use Symfony's security components
- Implement proper password hashing
- Implement role-based access control
- Validate and sanitize all user inputs

### Testing

- Write unit tests for services and domain logic
- Use PHPUnit for testing framework
- Mock dependencies in unit tests
- Write functional tests for controllers
- Test database interactions with fixtures

### Key Conventions
1. Strict typing in all PHP files
2. Constructor Property Promotion for dependency injection
3. Attributes instead of annotations
4. Repository pattern via Doctrine EntityRepository
5. Transaction handling for database operations
6. Event system for distributed logic
7. Form components for validation and data processing
8. Dependency Injection through constructors
9. Service autowiring and configuration
10. Doctrine Migrations for database versioning