# Podsumowanie Encji

Ten dokument zawiera podsumowanie wszystkich encji w projekcie, ich pól i relacji między nimi.

## Encje

- [User](#user)
- [Game](#game)
- [Task](#task)
- [GameTask](#gametask)
- [UserGame](#usergame)
- [UserGameTask](#usergametask)
- [UserToken](#usertoken)

---

### User

Reprezentuje użytkownika aplikacji.

| Pole | Typ | Opis |
|---|---|---|
| `id` | `Uuid` | Unikalny identyfikator użytkownika. |
| `email` | `string` | Adres email użytkownika (unikalny). |
| `roles` | `array` | Role użytkownika. |
| `password` | `string` | Hasło użytkownika. |
| `createdAt` | `DateTimeImmutable` | Data i czas utworzenia użytkownika. |
| `updatedAt` | `DateTimeImmutable` | Data i czas ostatniej aktualizacji użytkownika. |

---

### Game

Reprezentuje grę.

| Pole | Typ | Opis |
|---|---|---|
| `id` | `Uuid` | Unikalny identyfikator gry. |
| `name` | `string` | Nazwa gry (unikalna). |
| `description` | `text` | Opis gry. |
| `isAvailable` | `bool` | Czy gra jest dostępna. |
| `createdAt` | `DateTimeImmutable` | Data i czas utworzenia gry. |
| `updatedAt` | `DateTimeImmutable` | Data i czas ostatniej aktualizacji gry. |
| `deletedAt` | `DateTimeImmutable` | Data i czas usunięcia gry (soft delete). |
| `gameTasks` | `Collection<GameTask>` | Zadania w grze (relacja One-to-Many z `GameTask`). |

---

### Task

Reprezentuje zadanie w grze.

| Pole | Typ | Opis |
|---|---|---|
| `id` | `Uuid` | Unikalny identyfikator zadania. |
| `name` | `string` | Nazwa zadania. |
| `description` | `text` | Opis zadania. |
| `latitude` | `decimal` | Szerokość geograficzna zadania. |
| `longitude` | `decimal` | Długość geograficzna zadania. |
| `allowedDistance` | `int` | Dozwolona odległość od zadania. |
| `createdAt` | `DateTimeImmutable` | Data i czas utworzenia zadania. |
| `updatedAt` | `DateTimeImmutable` | Data i czas ostatniej aktualizacji zadania. |
| `deletedAt` | `DateTimeImmutable` | Data i czas usunięcia zadania (soft delete). |

---

### GameTask

Tabela pośrednicząca, która łączy `Game` i `Task` oraz definiuje kolejność zadań w grze.

| Pole | Typ | Opis |
|---|---|---|
| `id` | `Uuid` | Unikalny identyfikator zadania w grze. |
| `game` | `Game` | Gra, do której należy zadanie (relacja Many-to-One z `Game`). |
| `task` | `Task` | Zadanie (relacja Many-to-One z `Task`). |
| `sequenceOrder` | `int` | Kolejność zadania w grze. |
| `deletedAt` | `DateTimeImmutable` | Data i czas usunięcia zadania w grze (soft delete). |

---

### UserGame

Reprezentuje użytkownika grającego w grę.

| Pole | Typ | Opis |
|---|---|---|
| `id` | `Uuid` | Unikalny identyfikator gry użytkownika. |
| `user` | `User` | Użytkownik grający w grę (relacja Many-to-One z `User`). |
| `game` | `Game` | Gra, w którą gra użytkownik (relacja Many-to-One z `Game`). |
| `startedAt` | `DateTimeImmutable` | Data i czas rozpoczęcia gry. |
| `completedAt` | `DateTimeImmutable` | Data i czas ukończenia gry (nullable). |

---

### UserGameTask

Reprezentuje użytkownika, który ukończył zadanie w grze.

| Pole | Typ | Opis |
|---|---|---|
| `id` | `Uuid` | Unikalny identyfikator ukończonego zadania w grze. |
| `userGame` | `UserGame` | Gra użytkownika (relacja Many-to-One z `UserGame`). |
| `gameTask` | `GameTask` | Zadanie w grze (relacja Many-to-One z `GameTask`). |
| `completedAt` | `DateTimeImmutable` | Data i czas ukończenia zadania. |

---

### UserToken

Reprezentuje token odświeżający dla użytkownika.

Dziedziczy z `Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken`.
