# Dokument wymagań produktu (PRD) - Gry miejskie

## 1. Przegląd produktu
Aplikacja backendowa do obsługi i tworzenia gier miejskich opartych na geolokalizacji. System umożliwia administratorom tworzenie i zarządzanie grami oraz zadaniami, podczas gdy zalogowani użytkownicy mogą uczestniczyć w grach, zaliczając kolejne zadania w określonej kolejności poprzez potwierdzenie swojej lokalizacji. Zwycięzcą gry zostaje użytkownik, który pokonał trasę w najkrótszym czasie. Produkt składa się z panelu administratora oraz API REST dla aplikacji klienckich.

## 2. Problem użytkownika
Obecnie organizatorzy gier miejskich muszą ręcznie zarządzać trasami, zadaniami i śledzeniem postępu uczestników. Proces ten jest czasochłonny, podatny na błędy i nie zapewnia automatycznej weryfikacji lokalizacji graczy. Użytkownicy natomiast nie mają scentralizowanego systemu do odkrywania gier, śledzenia własnego postępu i porównywania swoich wyników czasowych. Aplikacja rozwiązuje te problemy poprzez zautomatyzowany system geolokalizacji, panel administracyjny do zarządzania treścią oraz mechanizm pomiaru czasu przejścia gier.

## 3. Wymagania funkcjonalne
- Panel administratora z uwierzytelnianiem opartym na sesji z timeoutem
- Pełny CRUD (tworzenie, odczyt, aktualizacja, usuwanie) dla gier i zadań z soft delete
- Relacja wiele-do-wielu między grami a zadaniami z zachowaniem kolejności
- System użytkowników z rejestracją i logowaniem (email + hasło)
- API zabezpieczone tokenami JWT z mechanizmem refresh token
- Geolokalizacja z algorytmem Haversine po stronie serwera
- System pomiaru czasu gry od rozpoczęcia do ukończenia
- Walidacja sekwencji zadań podczas gry
- Paginacja wszystkich list API (domyślnie 10, max 50 elementów)
- Logowanie strukturalne w formacie JSON
- Blokada gier z mniej niż 3 zadaniami

## 4. Granice produktu
- Brak aplikacji mobilnej - tylko API i panel administratora
- Brak importu danych - ręczne wprowadzanie przez panel admina
- Brak udostępniania wyników między użytkownikami
- Brak integracji z zewnętrznymi serwisami
- Brak zespołów - gracze wyłącznie indywidualni
- Brak możliwości pauzowania gry
- Brak zaawansowanych zabezpieczeń przed oszustwami geolokalizacyjnymi
- Brak rate limiting w MVP
- Brak mechanizmów cache'owania danych
- Brak health checks systemu
- Brak backupów i planu odzyskiwania danych
- Brak soft delete dla użytkowników
- Brak eksportu wyników do CSV

## 5. Historyjki użytkowników

### US-001
Tytuł: Logowanie administratora
Opis: Jako administrator, chcę móc zalogować się do panelu administracyjnego, aby zarządzać grami i zadaniami.
Kryteria akceptacji:
- System wyświetla formularz logowania z polami na login i hasło
- Po poprawnym zalogowaniu następuje przekierowanie do pulpitu głównego panelu
- Po niepoprawnym logowaniu wyświetlany jest komunikat błędu
- Sesja administratora wygasa po okresie nieaktywności

### US-002
Tytuł: Tworzenie nowej gry
Opis: Jako administrator, chcę móc utworzyć nową grę miejską, aby udostępnić ją użytkownikom.
Kryteria akceptacji:
- Formularz zawiera pola: nazwa, opis, identyfikator (UUID), status dostępności
- Nowo utworzona gra jest domyślnie niedostępna
- System waliduje unikalność nazwy i identyfikatora
- Możliwość zapisania gry jako nieaktywnej
- Po pomyślnym utworzeniu wyświetlany jest komunikat sukcesu

### US-003
Tytuł: Zarządzanie zadaniami w grze
Opis: Jako administrator, chcę móc przypisywać i usuwać zadania z gry oraz ustalać ich kolejność.
Kryteria akceptacji:
- Możliwość dodania istniejącego zadania do gry
- Możliwość usunięcia zadania z gry (soft delete)
- Interfejs do zmiany kolejności zadań
- System blokuje aktywowanie gry z mniej niż 3 zadaniami
- Walidacja unikalności kolejności zadań w obrębie gry

### US-004
Tytuł: Tworzenie i edycja zadań
Opis: Jako administrator, chcę móc tworzyć i edytować zadania, które mogą być używane w wielu grach.
Kryteria akceptacji:
- Formularz zawiera pola: nazwa, opis, współrzędne (długość, szerokość), dopuszczalna odległość
- System waliduje poprawność współrzędnych geograficznych
- Możliwość użycia tego samego zadania w wielu grach
- Edycja zadania wpływa na wszystkie gry, w których jest używane
- Soft delete usuwa widoczność zadania z aktywnych gier

### US-005
Tytuł: Przeglądanie statystyk ukończenia gier
Opis: Jako administrator, chcę móc przeglądać czasy ukończenia gier przez poszczególnych graczy.
Kryteria akceptacji:
- Lista zawiera: nazwę użytkownika, nazwę gry, czas ukończenia, datę ukończenia
- Możliwość filtrowania według gry i zakresu dat
- Możliwość sortowania według czasu ukończenia
- Brak możliwości edycji ani usuwania wyników
- Paginacja wyników (domyślnie 10 na stronę)

### US-006
Tytuł: Rejestracja użytkownika
Opis: Jako nowy użytkownik, chcę móc utworzyć konto, aby móc uczestniczyć w grach.
Kryteria akceptacji:
- Rejestracji przez api wymaga podania email i hasła
- Walidacja formatu email i siły hasła (min. 8 znaków, 1 cyfra)
- System sprawdza unikalność adresu email
- Po pomyślnej rejestracji użytkownik jest automatycznie logowany
- Brak wymaganej weryfikacji email w MVP

### US-007
Tytuł: Logowanie użytkownika
Opis: Jako zarejestrowany użytkownik, chcę móc zalogować się do systemu, aby uzyskać dostęp do gier.
Kryteria akceptacji:
- Logowanie za pomocą email i hasła
- Po poprawnym logowaniu zwracany jest token JWT
- Po niepoprawnym logowaniu zwracany jest błąd 401
- Token JWT ma ograniczony czas ważności
- Mechanizm refresh token pozwala na przedłużenie sesji

### US-008
Tytuł: Przeglądanie dostępnych gier
Opis: Jako zalogowany użytkownik, chcę móc przeglądać listę dostępnych gier, aby wybrać grę do rozpoczęcia.
Kryteria akceptacji:
- API zwraca listę gier oznaczonych jako dostępne
- Każda pozycja zawiera: nazwę, opis, identyfikator
- Lista jest paginowana (domyślnie 10 elementów)
- Gry niedostępne nie są wyświetlane
- Możliwość przeglądania kolejnych stron wyników

### US-009
Tytuł: Rozpoczęcie gry
Opis: Jako zalogowany użytkownik, chcę móc rozpocząć wybraną grę, aby zacząć wykonywać zadania.
Kryteria akceptacji:
- Wywołanie API rozpoczyna pomiar czasu dla użytkownika
- System zwraca pierwsze zadanie w sekwencji
- Gracz może uczestniczyć w wielu grach równocześnie
- Nie można rozpocząć gry oznaczonej jako niedostępna
- Nie można rozpocząć tej samej gry wielokrotnie jednocześnie

### US-010
Tytuł: Weryfikacja lokalizacji i zaliczanie zadań
Opis: Jako gracz, chcę móc potwierdzić swoją lokalizację, aby zaliczyć bieżące zadanie i otrzymać kolejne.
Kryteria akceptacji:
- API przyjmuje współrzędne gracza i identyfikator zadania
- System weryfikuje odległość od docelowej lokalizacji algorytmem Haversine
- Przy poprawnej lokalizacji zadanie jest zaliczane i zwracane jest kolejne
- Przy niepoprawnej lokalizacji zwracany jest błąd z informacją
- System waliduje kolejność zadań (błąd 409 przy złej sekwencji)

### US-011
Tytuł: Zakończenie gry i podgląd czasu
Opis: Jako gracz, chcę móc zobaczyć mój czas ukończenia gry po zaliczeniu wszystkich zadań.
Kryteria akceptacji:
- Po zaliczeniu ostatniego zadania gra jest oznaczana jako ukończona
- System zatrzymuje pomiar czasu i zapisuje wynik
- API zwraca łączny czas przejścia gry
- Czas jest mierzony od rozpoczęcia do ukończenia gry
- Wynik jest natychmiast dostępny do przeglądania

### US-012
Tytuł: Przeglądanie historii ukończonych gier
Opis: Jako gracz, chcę móc przeglądać listę ukończonych przeze mnie gier z czasami przejścia.
Kryteria akceptacji:
- API zwraca listę ukończonych gier z czasami przejścia
- Lista zawiera nazwę gry, datę ukończenia, czas przejścia
- Możliwość paginacji wyników
- Sortowanie według daty ukończenia (najnowsze pierwsze)
- Brak możliwości udostępniania wyników innym użytkownikom

### US-013
Tytuł: Bezpieczne zarządzanie sesjami
Opis: Jako system, chcę zapewnić bezpieczne uwierzytelnianie i autoryzację dla wszystkich operacji.
Kryteria akceptacji:
- Wszystkie requesty do API (poza login/rejestracja) wymagają tokena JWT
- Panel administratora używa sesji z timeoutem
- Tokeny JWT mają ograniczony czas ważności
- Mechanizm refresh token pozwala na ciche odświeżanie sesji
- Błędy autoryzacji zwracają odpowiednie kody HTTP

### US-014
Tytuł: Obsługa błędów i logowanie
Opis: Jako developer, chcę mieć szczegółowe logi wszystkich operacji, aby móc diagnozować problemy.
Kryteria akceptacji:
- Wszystkie błędy są logowane w formacie JSON
- Logi zawierają: timestamp, userId, action, errorCode, stackTrace
- Struktura błędów API jest spójna: {"field": "nazwa_pola", "message": "opis_błędu"}
- Komunikaty błędów są w języku polskim
- Logi nie zawierają wrażliwych danych (hasła, tokeny)

## 6. Metryki sukcesu
- 100% funkcjonalności CRUD dla gier i zadań przez panel administratora
- Możliwość utworzenia kompletnej gry z min. 3 zadaniami
- Użytkownik może przejść pełną grę od startu do mety bez błędów systemowych
- Czas odpowiedzi API < 2s dla endpointów krytycznych (weryfikacja lokalizacji, rozpoczynanie gry)
- Poprawna weryfikacja lokalizacji z dokładnością do zdefiniowanej dopuszczalnej odległości
- Bezpieczne logowanie i uwierzytelnianie bez incydentów bezpieczeństwa w trakcie MVP
- Dostępność systemu na poziomie min. 95% w trakcie okresu testowego
