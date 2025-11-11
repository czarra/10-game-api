# Implementation Plan: Admin Panel (Game and Task Management)

## 1. Instalacja wymaganych paczek
Pomiń krok 1, ponieważ Sonata Admin Bundle został już zainstalowany.
W pierwszej kolejności należy zainstalować Sonata Admin Bundle wraz z jego zależnościami dla Doctrine ORM, Twig i formularzy.

```bash
composer require sonata-project/admin-bundle sonata-project/doctrine-orm-admin-bundle
```

## 2. Przegląd panelu
Panel administracyjny zapewni interfejs użytkownika do zarządzania kluczowymi danymi aplikacji: grami (`Game`) i zadaniami (`Task`). Umożliwi administratorom wykonywanie operacji CRUD (Create, Read, Update, Delete) na tych zasobach, a także zarządzanie relacjami między nimi, w tym definiowanie kolejności zadań w grze.

## 3. Szczegóły żądania (Routing i Formularze)
Sonata Admin automatycznie wygeneruje następujące ścieżki URL:
- `/admin/dashboard` - Główny pulpit panelu.
- `/admin/app/game/list` - Lista gier (Metoda: GET).
- `/admin/app/game/create` - Formularz tworzenia nowej gry (Metoda: GET/POST).
- `/admin/app/game/{id}/edit` - Formularz edycji istniejącej gry (Metoda: GET/POST).
- `/admin/app/game/{id}/delete` - Usunięcie gry (Metoda: POST).
- `/admin/app/task/list` - Lista zadań (Metoda: GET).
- `/admin/app/task/create` - Formularz tworzenia nowego zadania (Metoda: GET/POST).
- `/admin/app/task/{id}/edit` - Formularz edycji istniejącego zadania (Metoda: GET/POST).
- `/admin/app/task/{id}/delete` - Usunięcie zadania (Metoda: POST).

Formularze będą renderowane na podstawie konfiguracji w klasach Admin i będą zawierać pola zdefiniowane w encjach `Game` i `Task`.

## 4. Szczegóły odpowiedzi (Widoki)
- **Widok listy:** Wyświetli tabelę z paginowanymi danymi, z możliwością sortowania i filtrowania. Domyślna paginacja zostanie ustawiona na 10 elementów.
- **Widok formularza (Create/Edit):** Wyświetli formularz z polami do edycji danych encji. W przypadku błędów walidacji, formularz zostanie wyświetlony ponownie z komunikatami o błędach.
- **Widok szczegółów (Show):** Opcjonalnie, może wyświetlać podsumowanie danych encji w trybie tylko do odczytu.

## 5. Przepływ danych
1.  Administrator nawiguje do odpowiedniej sekcji panelu (np. `/admin/app/game/list`).
2.  Sonata Admin, poprzez odpowiednią klasę `Admin` (np. `GameAdmin`), pobiera dane z bazy za pomocą Doctrine.
3.  Dane są przekazywane do szablonu Twig i renderowane jako widok (np. lista gier).
4.  Podczas operacji zapisu (edycja/tworzenie), dane z formularza są bindowane do obiektu encji.
5.  Walidatory Symfony (standardowe i niestandardowe) są uruchamiane na obiekcie encji.
6.  Jeśli walidacja przejdzie pomyślnie, Doctrine `EntityManager` zapisuje zmiany w bazie danych. W przeciwnym razie, formularz jest renderowany ponownie z błędami.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie:** Dostęp do całego panelu pod ścieżką `/admin` zostanie ograniczony. Zostanie skonfigurowany osobny firewall w `config/packages/security.yaml`, który będzie wymagał logowania.
- **Autoryzacja:** Dostęp do ścieżek `/admin/*` będzie wymagał od użytkownika posiadania roli `ROLE_ADMIN`.
- **Ochrona CSRF:** Wszystkie formularze generowane przez Sonatę będą miały włączoną ochronę CSRF.
- **Użytkownik Admin:** Zostanie utworzony co najmniej jeden użytkownik z rolą `ROLE_ADMIN` (np. za pomocą Doctrine Fixtures), aby umożliwić pierwsze logowanie.

## 7. Obsługa błędów
- **Błędy walidacji:** Będą przechwytywane i wyświetlane bezpośrednio w formularzu, przy odpowiednich polach.
- **Błędy bazy danych:** Naruszenia ograniczeń (np. `UNIQUE constraint violation` dla nazwy gry) zostaną przechwycone. Należy dodać walidator `UniqueEntity` do encji `Game`, aby zapewnić przyjazny komunikat o błędzie, zanim dojdzie do wyjątku z bazy danych.
- **Błędy 500:** Wszelkie nieoczekiwane błędy serwera będą logowane (zgodnie z konfiguracją Monolog) i spowodują wyświetlenie standardowej strony błędu 500 Symfony.

## 8. Rozważania dotyczące wydajności
- **Paginacja:** Wszystkie widoki list będą domyślnie paginowane, aby uniknąć ładowania dużej liczby rekordów jednocześnie.
- **Filtrowanie:** Dodanie filtrów do list pozwoli administratorom na szybkie wyszukiwanie potrzebnych danych, co jest kluczowe przy dużej liczbie rekordów.
- **Optymalizacja zapytań Doctrine:** W przypadku złożonych relacji, należy upewnić się, że zapytania Doctrine są zoptymalizowane (np. przez odpowiednie użycie `fetch="EAGER"` lub `addSelect` w `createQueryBuilder`), aby uniknąć problemu N+1.

## 9. Etapy wdrożenia
1.  **Instalacja i konfiguracja SonataAdminBundle:**
    - pomiń, już zrealizowane

2.  **Konfiguracja bezpieczeństwa:**
    - W `config/packages/security.yaml`, skonfiguruj firewall dla ścieżki `/admin`, ustawiając formularz logowania.
    - W `access_control`, dodaj regułę wymagającą `ROLE_ADMIN` dla ścieżki `^/admin`.
    - Utwórz klasę `User` (jeśli jeszcze nie istnieje) i dodaj użytkownika z rolą `ROLE_ADMIN` za pomocą fixtures.

3.  **Utworzenie klasy `TaskAdmin`:**
    - Stwórz `src/Admin/TaskAdmin.php`.
    - W metodzie `configureFormFields`, dodaj pola: `name`, `description`, `latitude`, `longitude`, `allowed_distance`.
    - W metodzie `configureDatagridFilters`, dodaj filtr dla pola `name`.
    - W metodzie `configureListFields`, zdefiniuj kolumny dla listy: `id`, `name`, `description`.
    - Zarejestruj `TaskAdmin` jako serwis w `config/services.yaml` z tagiem `sonata.admin`.

4.  **Utworzenie klasy `GameAdmin`:**
    - Stwórz `src/Admin/GameAdmin.php`.
    - W metodzie `configureFormFields`, dodaj pola: `name`, `description`, `is_available`.
    - Dodaj pole do zarządzania relacją `gameTasks` używając `Sonata\AdminBundle\Form\Type\CollectionType`. Skonfiguruj je, aby umożliwić dodawanie, usuwanie i zmianę kolejności zadań (`'allow_add' => true`, `'allow_delete' => true`, `'by_reference' => false`).
    - W metodzie `configureDatagridFilters`, dodaj filtry dla `name` i `is_available`.
    - W metodzie `configureListFields`, zdefiniuj kolumny: `id`, `name`, `is_available`, `_action` (z akcjami edycji i usuwania).
    - Zarejestruj `GameAdmin` jako serwis w `config/services.yaml`.

5.  **Implementacja walidacji niestandardowej:**
    - Stwórz klasę walidatora `AtLeastThreeTasksValidator` oraz klasę ograniczenia `AtLeastThreeTasks`.
    - Logika walidatora sprawdzi, czy `Game::isAvailable()` jest `true` i jeśli tak, zweryfikuje, czy `Game::getGameTasks()->count() >= 3`.
    - Dodaj atrybut `#[App\Validator\AtLeastThreeTasks]` do klasy encji `Game`.

6.  **Aktualizacja encji:**
    - Dodaj adnotacje/atrybuty walidacyjne Symfony (`NotBlank`, `UniqueEntity` itp.) do encji `Game` i `Task`.
    - Upewnij się, że relacja `gameTasks` w encji `Game` ma poprawnie skonfigurowane opcje `cascade={"persist"}` oraz `orphanRemoval=true`, aby poprawnie zarządzać nią z poziomu formularza Sonaty.

7.  **Konfiguracja dashboardu:**
    - W `config/packages/sonata_admin.yaml`, w sekcji `sonata_admin.dashboard.groups`, dodaj grupy i elementy dla `GameAdmin` i `TaskAdmin`, aby pojawiły się w menu panelu.

8.  **Testowanie:**
    - Napisz testy funkcjonalne (używając `Symfony\Bundle\FrameworkBundle\Test\WebTestCase`), które:
        - Sprawdzą, czy niezalogowany użytkownik jest przekierowywany na stronę logowania przy próbie dostępu do `/admin/dashboard`.
        - Sprawdzą, czy zalogowany administrator może uzyskać dostęp do listy gier i zadań.
        - Przetestują proces tworzenia nowej gry i zadania.
        - Zweryfikują, że walidator "co najmniej 3 zadania" działa poprawnie i blokuje aktywację gry.
        - Sprawdzą poprawność działania usuwania (soft delete).