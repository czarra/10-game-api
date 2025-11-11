# Plan Wdrożenia Encji, Repozytoriów i Serwisów

Na podstawie analizy wymagań produktu, stosu technologicznego oraz schematu bazy danych, poniżej przedstawiono proponowaną architekturę komponentów dla aplikacji Symfony.

## 1. Encje (Entities)

Encje Doctrine ORM, które mapują się na tabele bazy danych.

| Encja          | Tabela docelowa     | Opis                                                                                             | Status      |
|----------------|---------------------|--------------------------------------------------------------------------------------------------|-------------|
| `User`         | `users`             | Przechowuje dane uwierzytelniające i role użytkowników. Implementuje `UserInterface`.            | Do utworzenia |
| `Game`         | `games`             | Reprezentuje grę miejską, jej nazwę, opis i status.                                              | Istnieje    |
| `Task`         | `tasks`             | Reprezentuje pojedyncze zadanie geolokalizacyjne.                                               | Istnieje    |
| `GameTask`     | `game_tasks`        | Encja asocjacyjna (pivot) dla relacji `Game` i `Task`, przechowująca kolejność zadań w grze.      | Do utworzenia |
| `UserGame`     | `user_games`        | Śledzi postęp użytkownika w grze, w tym czas rozpoczęcia i ukończenia.                           | Do utworzenia |
| `UserGameTask` | `user_game_tasks`   | Zapisuje fakt ukończenia konkretnego zadania przez użytkownika w ramach danej sesji gry.         | Do utworzenia |
| `UserToken`    | `user_tokens`       | Przechowuje tokeny odświeżające (refresh tokens) powiązane z użytkownikami.                      | Do utworzenia |

## 2. Repozytoria (Repositories)

Repozytoria Doctrine służące do izolowania logiki zapytań do bazy danych dla każdej encji.

| Repozytorium           | Encja docelowa   | Opis                                                                                             | Status      |
|------------------------|------------------|--------------------------------------------------------------------------------------------------|-------------|
| `UserRepository`       | `User`           | Dostarcza metody do wyszukiwania użytkowników (np. po emailu).                                    | Do utworzenia |
| `GameRepository`       | `Game`           | Dostarcza metody do wyszukiwania gier (np. dostępnych dla graczy).                               | Istnieje    |
| `TaskRepository`       | `Task`           | Dostarcza metody do wyszukiwania zadań.                                                          | Do utworzenia |
| `GameTaskRepository`   | `GameTask`       | Dostarcza metody związane z zadaniami w grze (np. pobieranie zadań w kolejności).                | Do utworzenia |
| `UserGameRepository`   | `UserGame`       | Dostarcza metody do zapytań o sesje gier użytkowników (np. aktywne lub ukończone gry).            | Do utworzenia |
| `UserGameTaskRepository`| `UserGameTask`  | Dostarcza metody do weryfikacji ukończonych zadań przez użytkownika.                             | Do utworzenia |
| `UserTokenRepository`  | `UserToken`      | Dostarcza metody do zarządzania cyklem życia tokenów odświeżających.                             | Do utworzenia |

## 3. Serwisy (Services)

Serwisy implementujące logikę biznesową aplikacji, wstrzykiwane do kontrolerów i innych serwisów.

### Logika Gry (API dla Graczy)
| Serwis                 | Opis                                                                                             |
|------------------------|--------------------------------------------------------------------------------------------------|
| `GamePlayService`      | Kluczowy serwis obsługujący mechanikę gry: rozpoczynanie, zaliczanie zadań i kończenie gry.        |
| `GeolocationService`   | Serwis pomocniczy implementujący algorytm Haversine do weryfikacji odległości od celu zadania.     |

### Serwisy Zapytań (Query Services)
| Serwis                   | Opis                                                                                             | Status   |
|--------------------------|--------------------------------------------------------------------------------------------------|----------|
| `GameQueryService`       | Odpowiada za pobieranie i formatowanie list gier dla API (np. lista dostępnych gier).             | Istnieje |
| `TaskQueryService`       | Odpowiada za pobieranie i formatowanie danych o zadaniach dla API.                               | Do utworzenia |
| `UserGameQueryService`   | Dostarcza dane o historii gier użytkownika (ukończone gry, czasy).                               | Do utworzenia |
| `StatisticsQueryService` | Agreguje i dostarcza dane statystyczne dla panelu administratora (np. rankingi czasowe).         | Do utworzenia |
