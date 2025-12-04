# Implementation Plan: Admin Panel - Game Completion Statistics (US-005) - v2.1 (final)

## 1. Wymagane Pakiety

Pomiń krok 1, ponieważ Sonata Admin Bundle został już zainstalowany.

## 2. Przegląd Funkcjonalności

Celem jest stworzenie nowej sekcji w panelu administratora Sonata, która będzie wyświetlać statystyki ukończonych gier. Będzie to widok listy (read-only) oparty na encji `UserGame`. Administrator będzie mógł przeglądać, filtrować i sortować listę gier ukończonych przez użytkowników, **lista jest domyślnie sortowana według czasu trwania gry (od najkrótszego)**. Nie będzie możliwości edycji ani usuwania tych rekordów z tego poziomu.

## 3. Szczegóły Techniczne

- **Metoda HTTP:** `GET`
- **Struktura URL:** `/admin/app/usergame/list` (standardowy URL generowany przez Sonata Admin)
- **Parametry (jako parametry GET w URL):**
  - **Filtrowanie (opcjonalne):**
    - `filter[game][value]`: ID gry
    - `filter[user][value]`: ID użytkownika
  - **Sortowanie (opcjonalne):**
    - `sort_by`: Nazwa pola do sortowania (np. `game.name`, `user.email`)
    - `sort_order`: `ASC` lub `DESC`

## 4. Szczegóły Odpowiedzi

- **Typ odpowiedzi:** `text/html`
- **Opis:** Wyrenderowana strona HTML z tabelą. Kolumny:
  - Nazwa Gry
  - Użytkownik (email)
  - Data rozpoczęcia
  - Data ukończenia
  - **Czas trwania:** Obliczona różnica między `completed_at` a `started_at`, sformatowana jako `GG:MM:SS`.

## 5. Przepływ Danych

1.  Administrator wchodzi do sekcji "Statystyki Gier".
2.  Sonata Admin wywołuje klasę `App\Admin\UserGameAdmin`.
3.  Metoda `configureQuery()` w `UserGameAdmin` modyfikuje zapytanie DQL.
4.  **Zapytanie DQL:**
    - Wybiera dane z encji `UserGame` (alias `o`).
    - Dołącza (`LEFT JOIN`) encje `Game` (`g`) i `User` (`u`).
    - Filtruje wyniki (`o.completedAt IS NOT NULL`).
    - **Dodaje do selekcji obliczeniową kolumnę `durationSeconds` przy użyciu niestandardowej funkcji DQL `TIMESTAMPDIFF`, która oblicza różnicę czasu w sekundach.**
    - **Zawiera domyślną klauzulę ORDER BY `durationSeconds` ASC.**
5.  Doctrine wykonuje zapytanie w bazie danych PostgreSQL.
6.  Sonata Admin pobiera wyniki, stosuje paginację i przekazuje je do szablonu Twig.
7.  Szablon renderuje tabelę. Kolumna "Czas trwania" otrzymuje gotową wartość w sekundach i jedynie ją formatuje.

## 6. Bezpieczeństwo

- Dostęp ograniczony do `ROLE_ADMIN` poprzez `config/packages/security.yaml`.
- Ochrona CSRF zapewniona przez Sonata Admin.

## 7. Rozważania Wydajnościowe

- **Indeksowanie:** Kolumny `user_id`, `game_id` i `completed_at` w tabeli `user_games` powinny być zaindeksowane.
- **Paginacja:** Domyślnie włączona w Sonata.
- **Zapytanie:** Użycie `LEFT JOIN` i selektywne wybieranie pól jest kluczowe. Obliczenia w DQL są wydajniejsze niż w PHP dla dużych zbiorów danych.

## 8. Etapy Wdrożenia

### 8.1. Utworzenie niestandardowej funkcji DQL `TIMESTAMPDIFF`

- Stwórz plik `src/DQL/TimestampDiff.php`.
- Klasa będzie rozszerzać `Doctrine\ORM\Query\AST\Functions\FunctionNode` i implementować logikę dla funkcji `TIMESTAMPDIFF(unit, start, end)`.

### 8.2. Rejestracja funkcji DQL w Doctrine

- W pliku `config/packages/doctrine.yaml` zarejestruj nową funkcję, aby Doctrine mógł jej używać.

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        # ...
        dql:
            numeric_functions:
                # ...
                TIMESTAMPDIFF: App\DQL\TimestampDiff
```

### 8.3. Aktualizacja `UserGameAdmin`

- **Rejestracja jako serwis:** Ze względu na brak wsparcia dla atrybutu `#[AsAdmin]` w bieżącej wersji SonataAdminBundle, serwis `UserGameAdmin` musi zostać zarejestrowany w pliku `config/services.yaml`. Należy użyć ID serwisu `admin.user_game_stats`, aby zachować spójność z konfiguracją dashboardu Sonaty.

  ```yaml
  # config/services.yaml
  services:
      # ...
      admin.user_game_stats:
          class: App\Admin\UserGameAdmin
          tags:
              - { name: sonata.admin, manager_type: orm, group: "Statystyki", label: "Ukończone Gry", model_class: App\Entity\UserGame }
  ```

- **Konfiguracja pól listy (`configureListFields`):**
  - Zmodyfikuj pole `duration`, aby było sortowalne. Wskaż, że sortowanie ma się odbywać na obliczonym przez DQL polu `durationSeconds`.

  ```php
  // src/Admin/UserGameAdmin.php
  protected function configureListFields(ListMapper $list): void
  {
      // ...
      $list->add('duration', 'string', [
          'label' => 'Czas trwania',
          'template' => 'admin/list/list_duration.html.twig',
      ]);
  }
  ```

- **Modyfikacja zapytania (`configureQuery`):**
  - Zmodyfikuj metodę, aby dodawała do zapytania obliczoną kolumnę.

  ```php
  // src/Admin/UserGameAdmin.php
  protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
  {
      /** @var ProxyQuery $query */
      $rootAlias = $query->getRootAliases()[0];

      $query
          ->addSelect("TIMESTAMPDIFF(SECOND, {$rootAlias}.startedAt, {$rootAlias}.completedAt) as HIDDEN durationSeconds")
          ->andWhere($query->expr()->isNotNull($rootAlias . '.completedAt'))
          ->leftJoin($rootAlias . '.game', 'g')
          ->leftJoin($rootAlias . '.user', 'u')
          ->orderBy('durationSeconds', 'ASC');

      return $query;
  }
  ```

### 8.4. Aktualizacja szablonu dla czasu trwania

- Zmodyfikuj plik `templates/admin/list/list_duration.html.twig`.
- Szablon nie będzie już obliczał różnicy. Zamiast tego, otrzyma z `object.duration` wartość w sekundach (dzięki `sort_field_mapping`), którą sformatuje.

```twig
{# templates/admin/list/list_duration.html.twig #}
{% extends '@SonataAdmin/CRUD/base_list_field.html.twig' %}

{% block field_value %}
    {% if value is not null %}
        {% set seconds = value %}
        {% set hours = (seconds / 3600)|round(0, 'floor') %}
        {% set minutes = ((seconds % 3600) / 60)|round(0, 'floor') %}
        {% set remaining_seconds = seconds % 60 %}
        {{ '%02d:%02d:%02d'|format(hours, minutes, remaining_seconds) }}
    {% else %}
        N/A
    {% endif %}
{% endblock %}
```

### 8.5. Usunięcie niepotrzebnych akcji

- Upewnij się, że metoda `configureRoutes` usuwa akcje `create`, `edit`, `delete` i `export`.

### 8.6. Weryfikacja

- Uruchom aplikację i jako administrator przejdź do nowej sekcji.
- Sprawdź, czy wyświetlane są tylko ukończone gry.
- **Sprawdź, czy lista jest domyślnie posortowana rosnąco według czasu trwania.**
- Sprawdź działanie filtrów.
- Upewnij się, że użytkownik z inną rolą nie ma dostępu do panelu admina.