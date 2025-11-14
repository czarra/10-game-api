# Specyfikacja Techniczna: Moduł Rejestracji i Logowania Użytkowników

## 1. Wprowadzenie

Niniejszy dokument opisuje architekturę i implementację modułu rejestracji i logowania dla użytkowników końcowych (graczy) w systemie gier miejskich. Funkcjonalność jest realizowana w ramach API REST, zgodnie z historyjkami użytkownika US-006 i US-007.

- **US-006: Rejestracja użytkownika**
- **US-007: Logowanie użytkownika**

System wykorzystuje framework Symfony 6.4 oraz pakiet `lexik/jwt-authentication-bundle` do obsługi tokenów JWT.

---

## 2. Architektura Interfejsu Użytkownika (API Endpoints)

Interfejs użytkownika dla tej funkcjonalności jest w pełni oparty na API REST. Nie ma interfejsu graficznego po stronie serwera dla graczy.

### 2.1. Nowe Endpointy

#### `POST /api/auth/register` - Rejestracja nowego użytkownika

- **Opis:** Umożliwia nowemu użytkownikowi utworzenie konta w systemie. Po pomyślnej rejestracji, zgodnie z kryteriami akceptacji, użytkownik jest automatycznie logowany, co w kontekście API oznacza zwrot tokena JWT.
- **Typ Contentu:** `application/json`
- **Ciało Żądania (Request Body):**

```json
{
  "email": "user@example.com",
  "password": "Password123"
}
```

- **Odpowiedź Sukces (HTTP 201 Created):** Zwraca token JWT oraz refresh token.

```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "def502006b33c8e51c..."
}
```

- **Odpowiedzi Błędów:**
  - **HTTP 400 Bad Request:** Błędy walidacji.
    ```json
    {
      "errors": [
        {
          "field": "email",
          "message": "Podany email jest już zajęty."
        },
        {
          "field": "password",
          "message": "Hasło musi mieć co najmniej 8 znaków, w tym jedną cyfrę."
        }
      ]
    }
    ```
  - **HTTP 500 Internal Server Error:** Ogólny błąd serwera.

### 2.2. Istniejące Endpointy do Wykorzystania

#### `POST /api/auth/login` - Logowanie użytkownika

- **Opis:** Endpoint obsługiwany w pełni przez `lexik/jwt-authentication-bundle`. Służy do uwierzytelniania użytkownika i generowania tokenów.
- **Typ Contentu:** `application/json`
- **Ciało Żądania (Request Body):**

```json
{
  "username": "user@example.com",
  "password": "Password123"
}
```
*Uwaga: Klucz `username` jest domyślnym kluczem oczekiwanym przez Symfony Security dla identyfikatora użytkownika, w tym przypadku jest to email.*

- **Odpowiedź Sukces (HTTP 200 OK):** Zwraca token JWT oraz refresh token.

```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "def502006b33c8e51c..."
}
```

- **Odpowiedzi Błędów:**
  - **HTTP 401 Unauthorized:** Błędne dane logowania.
    ```json
    {
      "code": 401,
      "message": "Invalid credentials."
    }
    ```

---

## 3. Logika Backendowa

### 3.1. Struktura Komponentów

- **`App\Controller\Api\AuthController` (nowy):**
  - Kontroler dedykowany do obsługi procesów uwierzytelniania w API.
  - Będzie zawierał metodę `register()`.
  - Zostanie oznaczony jako `final`.

- **`App\Dto\Api\RegisterRequestDto` (nowy):**
  - Obiekt transferu danych (DTO) dla żądania rejestracji.
  - Będzie zawierał pola `email` i `password`.
  - Wykorzysta atrybuty `Symfony\Component\Validator\Constraints` do walidacji danych wejściowych.

- **`App\Service\UserService` (nowy):**
  - Serwis odpowiedzialny za logikę biznesową związaną z użytkownikami.
  - Będzie zawierał metodę `createUser(RegisterRequestDto $dto): User`.
  - Metoda ta będzie odpowiedzialna za:
    - Sprawdzenie unikalności adresu email.
    - Utworzenie nowej instancji `App\Entity\User`.
    - Ustawienie roli `ROLE_USER`.
    - Haszowanie hasła za pomocą `UserPasswordHasherInterface`.
    - Zapisanie użytkownika w bazie danych poprzez `UserRepository`.
  - Zostanie oznaczony jako `final`.

- **`App\Entity\User` (istniejący):**
  - Encja Doctrine reprezentująca użytkownika. Nie wymaga modyfikacji, ponieważ już implementuje `UserInterface` i `PasswordAuthenticatedUserInterface`.

- **`App\Repository\UserRepository` (istniejący):**
  - Repozytorium dla encji `User`. Będzie używane przez `UserService` do zapisu i odczytu danych użytkownika.

### 3.2. Proces Rejestracji (`POST /api/auth/register`)

1.  Żądanie trafia do `AuthController::register()`.
2.  Ciało żądania jest deserializowane do obiektu `RegisterRequestDto`.
3.  Walidator Symfony sprawdza poprawność danych w DTO:
    - `email`: `Assert\NotBlank`, `Assert\Email`.
    - `password`: `Assert\NotBlank`, `Assert\Length(min=8)`, `Assert\Regex(pattern="/\d/", message="Hasło musi zawierać co najmniej jedną cyfrę.")`.
4.  Jeśli walidacja nie powiedzie się, zwracany jest błąd 400 z listą błędów.
5.  Kontroler wywołuje `UserService::createUser()` z poprawnym DTO.
6.  `UserService` tworzy nowego użytkownika, haszuje hasło i zapisuje go w bazie danych. Unikalność emaila jest obsługiwana przez ograniczenie `UNIQUE` w bazie danych, a serwis powinien przechwytywać `Doctrine\DBAL\Exception\UniqueConstraintViolationException` i zwracać odpowiedni błąd biznesowy.
7.  Po pomyślnym utworzeniu użytkownika, kontroler ręcznie generuje dla niego token JWT za pomocą serwisu `lexik_jwt_authentication.jwt_manager`.
8.  Token JWT i refresh token są zwracane w odpowiedzi z kodem 201.

### 3.3. Proces Logowania (`POST /api/auth/login`)

Proces jest w całości zarządzany przez Symfony Security i `lexik/jwt-authentication-bundle` zgodnie z konfiguracją w `config/packages/security.yaml` w sekcji `firewalls.login`. Nie wymaga to implementacji dodatkowej logiki w kontrolerze.

---

## 4. System Autentykacji

### 4.1. Konfiguracja Zabezpieczeń (`security.yaml`)

Konfiguracja `security.yaml` jest już przygotowana do obsługi logowania JWT. Należy upewnić się, że firewall `api` obejmuje wszystkie chronione endpointy, a firewall `login` oraz nowy endpoint `/api/auth/register` są publicznie dostępne.

```yaml
# config/packages/security.yaml
security:
    # ... (istniejąca konfiguracja)
    firewalls:
        # ...
        register: # Nowy firewall dla rejestracji
            pattern: ^/api/auth/register
            stateless: true
            security: false # lub methods: [POST]
        login:
            pattern:  ^/api/auth/login
            stateless: true
            json_login:
                check_path: /api/auth/login
                # ... (istniejąca konfiguracja)
        api:
            pattern:   ^/api
            stateless: true
            jwt: ~

    access_control:
        # ...
        - { path: ^/api/auth/register, roles: PUBLIC_ACCESS }
        - { path: ^/api/auth/login, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```
*Alternatywnie, zamiast nowego firewalla `register`, można dodać `/api/register` do `access_control` jako `PUBLIC_ACCESS` i upewnić się, że pasuje do wzorca firewalla `api`, ale jest wykluczony z wymogu JWT.*

### 4.2. Zarządzanie Tokenami

- **Token JWT:** Krótkożyjący token dostępowy, używany do autoryzacji żądań do API. Czas życia tokena powinien być skonfigurowany w `config/packages/lexik_jwt_authentication.yaml` (np. 1 godzina).
- **Refresh Token:** Długożyjący token używany do uzyskiwania nowego tokena JWT bez konieczności ponownego logowania. Pakiet `gesdinet/jwt-refresh-token-bundle` jest rekomendowany do implementacji tego mechanizmu. Jego instalacja i konfiguracja będą konieczne. Tokeny odświeżające będą przechowywane w tabeli `user_tokens` (`refresh_tokens` w domyślnej konfiguracji `gesdinet`), zgodnie z dostarczonym schematem bazy danych.

### 4.3. Odzyskiwanie Hasła (Poza zakresem MVP, ale do rozważenia)

Funkcjonalność odzyskiwania hasła nie jest częścią tego zadania, ale architektura powinna umożliwiać jej dodanie w przyszłości. Zazwyczaj obejmuje to:
1.  Endpoint `POST /api/forgot-password` przyjmujący email.
2.  Generowanie unikalnego, krótkożyjącego tokenu resetowania hasła.
3.  Wysyłkę emaila z linkiem do resetowania hasła.
4.  Endpoint `POST /api/reset-password` przyjmujący token i nowe hasło.

---

## 5. Podsumowanie Kluczowych Zmian i Nowych Komponentów

- **Nowy Kontroler:** `src/Controller/Api/AuthController.php`
- **Nowy DTO:** `src/Dto/Api/RegisterRequestDto.php`
- **Nowy Serwis:** `src/Service/UserService.php`
- **Nowa zależność (rekomendowana):** `gesdinet/jwt-refresh-token-bundle` do obsługi refresh tokenów.
- **Aktualizacja konfiguracji:** `config/packages/security.yaml` w celu dodania endpointu rejestracji do publicznie dostępnych.
- **Nowe trasy:** Zdefiniowane za pomocą atrybutów w `AuthController`.
- **Nowe testy:** Funkcjonalne dla endpointu `/api/auth/register` oraz testy jednostkowe dla `UserService`.
