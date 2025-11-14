# Implementation Plan: Admin Panel - Game Completion Statistics (US-005)

## 1. Instalacja wymaganych paczek

Pomiń krok 1, ponieważ Sonata Admin Bundle został już zainstalowany.

## 2. Przegląd punktu końcowego

Celem jest stworzenie nowej sekcji w panelu administratora Sonata, która będzie wyświetlać statystyki ukończonych gier. Będzie to widok listy (read-only) oparty na encji `UserGame`. Administrator będzie mógł przeglądać, filtrować i sortować listę gier ukończonych przez użytkowników wraz z czasem, jaki im to zajęło. Nie będzie możliwości edycji ani usuwania tych rekordów z tego poziomu.

## 3. Szczegóły żądania

- **Metoda HTTP:** `GET`
- **Struktura URL:** `/admin/app/usergame/list` (standardowy URL generowany przez Sonata Admin)
- **Parametry (jako parametry GET w URL):**
  - **Filtrowanie (opcjonalne):**
    - `filter[game][value]`: ID gry
    - `filter[user][value]`: ID użytkownika
    - `filter[completedAt][value]`: Zakres dat ukończenia
  - **Sortowanie (opcjonalne):**
    - `sort_by`: Nazwa pola do sortowania (np. `game.name`, `user.email`, `duration`)
    - `sort_order`: `ASC` lub `DESC`
  - **Paginacja (opcjonalne):**
    - `page`: Numer strony

## 4. Szczegóły odpowiedzi

- **Typ odpowiedzi:** `text/html`
- **Opis:** Wyrenderowana strona HTML zawierająca tabelę z listą ukończonych gier. Tabela będzie zawierać następujące kolumny:
  - **Nazwa Gry:** Nazwa ukończonej gry (z encji `Game`).
  - **Użytkownik:** Adres email użytkownika, który ukończył grę (z encji `User`).
  - **Data rozpoczęcia:** `started_at` z encji `UserGame`.
  - **Data ukończenia:** `completed_at` z encji `UserGame`.
  - **Czas trwania:** Obliczona różnica między `completed_at` a `started_at`, sformatowana jako `HH:MM:SS`.

## 5. Przepływ danych

1.  Administrator loguje się do panelu i przechodzi do sekcji "Statystyki Gier".
2.  Sonata Admin wywołuje klasę `App\Admin\UserGameAdmin`.
3.  Metoda `createQuery()` w `UserGameAdmin` jest wykonywana w celu zbudowania niestandardowego zapytania DQL.
4.  Zapytanie DQL:
    - Wybiera dane z encji `UserGame` (alias `o`).
    - Dołącza (`LEFT JOIN`) encję `Game` (alias `g`) i `User` (alias `u`).
    - Filtruje wyniki, aby pokazać tylko te, gdzie `o.completed_at IS NOT NULL`.
    - Dodaje do selekcji obliczeniową kolumnę `duration` (np. przy użyciu `AGE(o.completed_at, o.started_at)` jeśli baza to wspiera lub przez pobranie obu dat).
5.  Doctrine wykonuje zapytanie w bazie danych PostgreSQL.
6.  Sonata Admin pobiera wyniki, stosuje paginację i przekazuje je do szablonu Twig.
7.  Szablon renderuje tabelę HTML z danymi i kontrolkami do filtrowania, sortowania i paginacji.

## 6. Względy bezpieczeństwa

- **Uwierzytelnianie i Autoryzacja:** Dostęp do całej ścieżki `/admin` musi być ograniczony do użytkowników z rolą `ROLE_ADMIN`. Należy to skonfigurować w pliku `config/packages/security.yaml`.

  ```yaml
  # config/packages/security.yaml
  access_control:
      - { path: ^/admin, roles: ROLE_ADMIN }
  ```
- **CSRF:** Sonata Admin domyślnie zapewnia ochronę przed atakami CSRF dla wszystkich formularzy, co jest dodatkowym zabezpieczeniem.

## 7. Obsługa błędów

- **403 Forbidden:** Jeśli użytkownik bez roli `ROLE_ADMIN` spróbuje uzyskać dostęp, Symfony zwróci stronę błędu 403.
- **500 Internal Server Error:** W przypadku błędu zapytania do bazy danych lub innego błędu po stronie serwera, Symfony wyświetli standardową stronę błędu 500. Wszystkie wyjątki będą logowane przez Monolog.

## 8. Rozważania dotyczące wydajności

- **Indeksowanie:** Aby zapewnić szybkie działanie filtrów i sortowania, należy upewnić się, że kolumny `user_id`, `game_id` i `completed_at` w tabeli `user_games` są zaindeksowane. Zgodnie z planem bazy danych, klucze obce są już indeksowane. Warto dodać indeks na `completed_at`.
- **Paginacja:** Sonata Admin domyślnie paginuje wyniki, co zapobiega problemom z wydajnością przy dużej liczbie rekordów.
- **Zapytanie:** Zapytanie DQL powinno być zoptymalizowane, aby unikać pobierania niepotrzebnych danych. Należy używać `LEFT JOIN` i selektywnie wybierać pola.

## 9. Etapy wdrożenia

1.  **Utworzenie klasy Admin:**
    - Stwórz nowy plik `src/Admin/UserGameAdmin.php`.
    - Klasa `UserGameAdmin` powinna dziedziczyć po `Sonata\AdminBundle\Admin\AbstractAdmin`.

2.  **Rejestracja serwisu Admin:**
    - Zarejestruj `UserGameAdmin` jako serwis w `config/services.yaml` i oznacz go tagiem `sonata.admin`.

    ```yaml
    # config/services.yaml
    services:
        # ...
        App\Admin\UserGameAdmin:
            class: App\Admin\UserGameAdmin
            arguments: [~, App\Entity\UserGame, ~]
            tags:
                - { name: sonata.admin, manager_type: orm, group: "Statystyki", label: "Ukończone Gry" }
    ```

3.  **Konfiguracja pól listy (`configureListFields`):**
    - W `UserGameAdmin.php` zaimplementuj metodę `configureListFields`, aby zdefiniować kolumny.
    - Dla pola `duration` użyj niestandardowego szablonu do obliczenia i sformatowania różnicy czasu.

    ```php
    // src/Admin/UserGameAdmin.php
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('game.name', null, ['label' => 'Nazwa Gry'])
            ->add('user.email', null, ['label' => 'Użytkownik'])
            ->add('startedAt', null, [
                'label' => 'Data rozpoczęcia',
                'format' => 'Y-m-d H:i:s'
            ])
            ->add('completedAt', null, [
                'label' => 'Data ukończenia',
                'format' => 'Y-m-d H:i:s'
            ])
            ->add('duration', 'string', [
                'label' => 'Czas trwania',
                'template' => 'admin/list/list_duration.html.twig'
            ]);
    }
    ```

4.  **Stworzenie szablonu dla czasu trwania:**
    - Utwórz plik `templates/admin/list/list_duration.html.twig`.
    - W szablonie oblicz różnicę między `object.completedAt` a `object.startedAt`.

    ```twig
    {# templates/admin/list/list_duration.html.twig #}
    {% extends '@SonataAdmin/CRUD/base_list_field.html.twig' %}

    {% block field_value %}
        {% if object.completedAt and object.startedAt %}
            {{ object.completedAt.diff(object.startedAt).format('%H:%I:%S') }}
        {% else %}
            N/A
        {% endif %}
    {% endblock %}
    ```

5.  **Konfiguracja filtrów (`configureDatagridFilters`):**
    - W `UserGameAdmin.php` zaimplementuj metodę `configureDatagridFilters`.

    ```php
    // src/Admin/UserGameAdmin.php
    protected function configureDatagridFilters(DatagridMapper $datagrid): void
    {
        $datagrid
            ->add('game', null, ['label' => 'Gra'])
            ->add('user', null, ['label' => 'Użytkownik']);
    }
    ```

6.  **Modyfikacja zapytania (`createQuery`):**
    - W `UserGameAdmin.php` nadpisz metodę `createQuery`, aby filtrować tylko ukończone gry i dodać aliasy.

    ```php
    // src/Admin/UserGameAdmin.php
    public function createQuery(string $context = 'list'): ProxyQueryInterface
    {
        $query = parent::createQuery($context);
        $rootAlias = $query->getRootAliases()[0];

        $query->andWhere($query->expr()->isNotNull($rootAlias . '.completedAt'));
        
        // Opcjonalne dołączenie, jeśli Sonata nie robi tego automatycznie
        $query->leftJoin($rootAlias . '.game', 'g');
        $query->leftJoin($rootAlias . '.user', 'u');

        return $query;
    }
    ```

7.  **Usunięcie niepotrzebnych akcji:**
    - Ponieważ ma to być widok tylko do odczytu, usuń akcje tworzenia, edycji i usuwania.

    ```php
    // src/Admin/UserGameAdmin.php
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->remove('edit');
        $collection->remove('delete');
        $collection->remove('export');
    }
    ```

8.  **Weryfikacja:**
    - Uruchom aplikację, zaloguj się jako administrator i przejdź do nowej sekcji w panelu.
    - Sprawdź, czy wyświetlane są tylko ukończone gry, czy działają filtry i sortowanie (szczególnie po kolumnach z relacji).
    - Upewnij się, że użytkownik z inną rolą (np. `ROLE_USER`) nie ma dostępu do panelu admina.
