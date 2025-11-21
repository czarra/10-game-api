# Plan Testów Projektu "Geo-Game Backend"

## 1. Wprowadzenie i cele testowania

Celem niniejszego planu jest zdefiniowanie strategii zapewnienia jakości (QA) dla backendowej części aplikacji opartej na geolokalizacji. System oparty jest na frameworku Symfony 6.4, wykorzystuje bazę PostgreSQL oraz panel administracyjny Sonata Admin.

Głównym celem testów jest weryfikacja poprawności logiki biznesowej gry, bezpieczeństwa API, integralności danych oraz stabilności panelu administracyjnego. Szczególny nacisk zostanie położony na newralgiczne mechanizmy: walidację geolokalizacji, sekwencyjność zadań oraz zarządzanie sesjami gier użytkowników.

## 2. Zakres testów

Zakres testów obejmuje:
*   **API REST (Endpoints):** Weryfikacja wszystkich endpointów zdefiniowanych w `GameController`, `AuthController` oraz `AdminLoginController`.
*   **Logika Biznesowa (Services):** Testowanie usług, w szczególności `GamePlayService`, `GeolocationService` oraz `RegistrationService`.
*   **Warstwa Danych (Entities/Repositories):** Poprawność mapowania Doctrine, działanie SoftDeletes (`Gedmo`), unikalność danych i ograniczenia bazy PostgreSQL.
*   **Panel Administratora (Sonata Admin):** Zarządzanie grami, zadaniami i użytkownikami, weryfikacja poprawności formularzy i filtrów.
*   **Bezpieczeństwo:** Uwierzytelnianie JWT, odświeżanie tokenów, kontrola dostępu (Role-Based Access Control).

**Z zakresu wyłączone są:**
*   Testy interfejsu mobilnego (aplikacja kliencka).
*   Testy wydajnościowe pod ekstremalnym obciążeniem (chyba że w fazie późniejszej zostanie to wymagane).

## 3. Typy testów do przeprowadzenia

Ze względu na architekturę projektu (Symfony + Doctrine), strategia testów opierać się będzie na piramidzie testów:

1.  **Testy Jednostkowe (Unit Tests):**
    *   Skupione na izolowanych klasach i metodach.
    *   Główny cel: `GeolocationService` (matematyka), DTOs, Custom Validators (np. `AtLeastThreeTasksValidator`).
2.  **Testy Integracyjne (Integration Tests):**
    *   Weryfikacja współpracy między usługami a bazą danych.
    *   Główny cel: `GamePlayService` (interakcje z DB), Repozytoria (`GameRepository`, `UserGameRepository`), Event Subscribers (`TaskSoftDeleteSubscriber`).
3.  **Testy Funkcjonalne API (API Functional Tests):**
    *   Testowanie endpointów HTTP jako czarna skrzynka (wejście JSON -> wyjście JSON/Kod HTTP).
    *   Symulacja pełnych ścieżek użytkownika (Rejestracja -> Start Gry -> Ukończenie Zadania).
4.  **Testy End-to-End (E2E) dla Admina:**
    *   Podstawowe scenariusze przeklikiwania panelu Sonata Admin (logowanie admina, CRUD gier).

## 4. Scenariusze testowe dla kluczowych funkcjonalności

### A. Moduł Uwierzytelniania i Bezpieczeństwa (Auth)
| ID | Nazwa scenariusza | Opis / Kroki | Oczekiwany rezultat |
|:---|:---|:---|:---|
| AUTH-01 | Rejestracja użytkownika (Happy Path) | POST `/api/auth/register` z unikalnym emailem i poprawnym hasłem. | Kod 201, zwrócenie tokena JWT i refresh tokena. |
| AUTH-02 | Rejestracja duplikatu email | Próba rejestracji na istniejący email. | Kod 409 Conflict, komunikat błędu z `EmailAlreadyExistsException`. |
| AUTH-03 | Walidacja hasła | Próba rejestracji z hasłem < 8 znaków lub bez cyfry. | Kod 422/400, błędy walidacji DTO. |
| AUTH-04 | Logowanie i Refresh Token | Uzyskanie tokena, odczekanie (mock czasu) i użycie refresh tokena. | Nowa para tokenów JWT jest generowana. |
| AUTH-05 | Dostęp bez autoryzacji | Próba dostępu do `/api/games` bez nagłówka Authorization. | Kod 401 Unauthorized. |

### B. Logika Gry (GamePlay & API)
| ID | Nazwa scenariusza | Opis / Kroki | Oczekiwany rezultat |
|:---|:---|:---|:---|
| GAME-01 | Rozpoczęcie gry | POST `/api/games/{id}/start` dla dostępnej gry. | Kod 201, utworzenie rekordu `UserGame`, zwrot pierwszego zadania. |
| GAME-02 | Rozpoczęcie niedostępnej gry | Próba startu gry z `isAvailable=false` lub < 3 zadaniami. | Kod 400/409, obsługa wyjątku `GameUnavailableException`. |
| GAME-03 | Ponowny start aktywnej gry | Próba startu gry, w którą użytkownik już gra. | Kod 409, wyjątek `GameAlreadyStartedException`. |
| GAME-04 | Ukończenie zadania (Lokalizacja OK) | POST `/api/user-game/.../complete` z koordynatami w promieniu `allowedDistance`. | Kod 200, `completed: true`, zwraca następne zadanie (DTO). |
| GAME-05 | Ukończenie zadania (Błędna lokalizacja) | Próba ukończenia zadania z koordynatami poza zasięgiem. | Kod 409, wyjątek `WrongLocationException`. |
| GAME-06 | Naruszenie sekwencji zadań | Próba ukończenia zadania nr 3, gdy zadanie nr 2 nie jest ukończone. | Kod 409, wyjątek `InvalidTaskSequenceException`. |
| GAME-07 | Ukończenie gry | Ukończenie ostatniego zadania w sekwencji. | Kod 200, `gameCompleted: true`, ustawienie `completedAt` w bazie. |
| GAME-08 | Pobranie detali gry (UUID) | GET `/api/games/{id}` z błędnym formatem UUID. | Kod 400, walidator UUID odrzuca żądanie. |

### C. Panel Administratora (Sonata Admin)
| ID | Nazwa scenariusza | Opis / Kroki | Oczekiwany rezultat |
|:---|:---|:---|:---|
| ADM-01 | Tworzenie gry z zadaniami | Utworzenie Gry, dodanie zadań przez `CollectionType` (Sonata). | Gra zapisana, relacje `GameTask` poprawne, kolejność (`sequenceOrder`) zachowana. |
| ADM-02 | Walidator "AtLeastThreeTasks" | Próba zapisu gry z flagą `isAvailable=true` mającej 2 zadania. | Błąd walidacji formularza, gra nie jest zapisana jako dostępna. |
| ADM-03 | Unikalność sekwencji | Próba dodania dwóch zadań z tym samym `sequenceOrder` w jednej grze. | `Callback` walidacji w encji `Game` blokuje zapis. |
| ADM-04 | Soft Delete Zadania | Usunięcie zadania (`Task`) w panelu admina. | Rekord ma ustawione `deletedAt`. Powiązane `GameTask` również otrzymują `deletedAt` (Subscriber). |

## 5. Środowisko testowe

*   **Infrastruktura:** Docker (kontenery `php`, `postgres`).
*   **Baza danych testowa:** Oddzielna baza PostgreSQL (tworzona przy uruchomieniu testów), czyszczona po każdym teście (transactional rollback) lub przy użyciu narzędzi takich jak `DAMADoctrineTestBundle`.
*   **Konfiguracja PHP:** `APP_ENV=test`.

## 6. Narzędzia do testowania

1.  **PHPUnit:** Główny framework testowy.
2.  **Symfony TestClient:** Do testów funkcjonalnych API (`WebTestCase`).
3.  **Foundry / DoctrineFixtures:** Do generowania danych testowych (Factory dla User, Game, Task).
4.  **PHPStan / Psalm:** Statyczna analiza kodu (wykrywanie błędów typów).
5.  **PHP-CS-Fixer:** Weryfikacja standardów kodowania.
6.  **GitHub Actions:** Automatyzacja uruchamiania testów przy każdym Push/PR.

## 7. Harmonogram testów

Proces testowania jest zintegrowany z cyklem CI/CD:

1.  **Testy lokalne:** Deweloper uruchamia testy jednostkowe przed commitem.
2.  **Continuous Integration (GitHub Actions):**
    *   Budowa kontenera.
    *   Analiza statyczna (PHPStan).
    *   Uruchomienie pełnego zestawu testów PHPUnit (Unit + Functional).
    *   Blokada merge'owania Pull Requesta w przypadku błędu.
3.  **Testy akceptacyjne (Manualne/Beta):** Przed wdrożeniem na produkcję, QA weryfikuje kluczowe ścieżki na środowisku stagingowym (odzwierciedlającym prod).

## 8. Kryteria akceptacji testów

Aby uznać wersję oprogramowania za gotową do wydania:
*   100% testów jednostkowych i integracyjnych musi przechodzić pomyślnie (status Green).
*   Pokrycie kodu (Code Coverage) dla kluczowych usług (`GamePlayService`, walidatory) wynosi minimum 90%.
*   Brak błędów krytycznych (Critical/Blocker) w Jira/Issue Tracker.
*   Analiza statyczna (PHPStan) na poziomie 7 lub wyższym nie zgłasza błędów.
*   Wszystkie migracje bazy danych wykonują się bezbłędnie na czystej bazie oraz na kopii bazy produkcyjnej.

## 9. Role i odpowiedzialności

*   **Developer:**
    *   Pisanie testów jednostkowych dla nowego kodu.
    *   Utrzymanie zgodności ze standardami kodowania.
    *   Naprawa błędów wykrytych przez CI.
*   **Inżynier QA (Ty):**
    *   Tworzenie i utrzymanie planu testów.
    *   Pisanie złożonych testów funkcjonalnych i integracyjnych (brzegowe przypadki użycia).
    *   Weryfikacja poprawności działania panelu admina.
    *   Analiza zgłoszeń błędów z produkcji.
*   **Tech Lead:**
    *   Review testów podczas Code Review.
    *   Zatwierdzanie kryteriów jakościowych.

## 10. Procedury raportowania błędów

W przypadku wykrycia błędu należy utworzyć zgłoszenie (Issue) zawierające:
1.  **Tytuł:** Zwięzły opis problemu.
2.  **Środowisko:** (np. Local Docker, Staging).
3.  **Kroki do reprodukcji:** Dokładna lista kroków lub payload JSON (dla API).
4.  **Oczekiwany rezultat:** Co powinno się stać.
5.  **Rzeczywisty rezultat:** Co się stało (wraz ze stack trace lub zrzutem ekranu).
6.  **Priorytet:** (Low, Medium, High, Critical).

Dla błędów API, obowiązkowe jest dołączenie `Request Body` oraz `Response Body`.