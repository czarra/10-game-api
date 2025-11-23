### Przewodnik po Testach E2E Panelu Admina (Sonata)

Poniższe podsumowanie opisuje kroki i wzorce postępowania wypracowane podczas tworzenia testu logowania administratora (`AdminLoginControllerTest`), które należy stosować w przyszłych testach E2E wymagających zalogowanego użytkownika.

#### 1. Konfiguracja `phpunit.xml.dist`

Każdy test typu `WebTestCase` wymaga dostępu do jądra aplikacji (Kernel). Upewnij się, że w pliku `phpunit.xml.dist` znajduje się następująca zmienna środowiskowa w sekcji `<php>`:

```xml
<server name="KERNEL_CLASS" value="App\Kernel" />
```

#### 2. Tworzenie Użytkowników z Haszowanym Hasłem

Fabryka `UserFactory` została przystosowana do hashowania haseł. Zamiast przekazywać hasło jako zwykły tekst, należy użyć metody `withHashedPassword()`, przekazując do niej serwis haszujący pobrany z kontenera.

**Przykład użycia w teście:**

```php
// tests/Controller/Admin/YourAdminTest.php

protected function setUp(): void
{
    $this->client = static::createClient();

    // Uzyskaj dostęp do kontenera
    self::bootKernel();
    $container = static::getContainer();

    // Pobierz serwis haszujący
    /** @var UserPasswordHasherInterface $passwordHasher */
    $passwordHasher = $container->get(UserPasswordHasherInterface::class);

    // Stwórz użytkownika z zahaszowanym hasłem
    UserFactory::new()
        ->asAdmin()
        ->withHashedPassword($passwordHasher, 'password')
        ->with(['email' => 'admin@example.com'])
        ->create();
}
```

#### 3. Logowanie Użytkownika na Potrzeby Testów

Istnieją dwa scenariusze logowania użytkownika w testach E2E.

##### Scenariusz A: Gdy testujesz sam proces logowania

Jeśli celem testu jest sprawdzenie samego formularza logowania (jak w `AdminLoginControllerTest`), musisz ręcznie zasymulować ten proces:

1.  **Nawiguj do strony logowania:** `/admin/login`.
2.  **Znajdź przycisk:** Przycisk do wysłania formularza ma tekst `Login`.
3.  **Wypełnij pola:** Nazwy pól formularza to `admin_login_form[email]` i `admin_login_form[password]`.

```php
$crawler = $this->client->request('GET', '/admin/login');

$this->client->submitForm('Login', [
    'admin_login_form[email]' => 'admin@example.com',
    'admin_login_form[password]' => 'password',
]);

$this->assertResponseRedirects('/admin/dashboard');
```

##### Scenariusz B: Gdy testujesz funkcjonalność po zalogowaniu (ZALECANE)

Dla wszystkich testów, które sprawdzają funkcjonalność panelu admina *po* zalogowaniu (np. CRUD na encjach), **nie należy** symulować wypełniania formularza. Zamiast tego, użyj wbudowanej metody `loginUser()`, która jest znacznie szybsza i bardziej niezawodna.

**Zalecany wzorzec:**

```php
// tests/Controller/Admin/GameAdminControllerTest.php

public function testGameCreationPageIsAccessible(): void
{
    // 1. Stwórz użytkownika-administratora (jeśli nie ma go w setUp)
    $adminUser = UserFactory::new()->asAdmin()->create()->object();

    // 2. Zaloguj użytkownika do klienta
    $this->client->loginUser($adminUser, 'admin'); // 'admin' to nazwa firewalla z security.yaml

    // 3. Wykonaj żądanie jako zalogowany użytkownik
    $this->client->request('GET', '/admin/app/game/create');

    // 4. Wykonaj asercje
    $this->assertResponseIsSuccessful();
    $this->assertStringContainsString('Create Game', $this->client->getResponse()->getContent());
}
```

#### 4. Przydatne Asercje dla Dashboardu

Po poprawnym zalogowaniu, na pulpicie nawigacyjnym znajdują się kluczowe nagłówki, które można wykorzystać do asercji, że użytkownik jest na właściwej stronie:

*   Nagłówki bloków: `Game Management` i `Statistics` znajdują się w tagach `<h3>` z klasą `box-title`.
*   Asercja ogólna (mniej podatna na zmiany struktury HTML):

```php
$crawler = $this->client->followRedirect();

$this->assertStringContainsString('Game Management', $crawler->text());
$this->assertStringContainsString('Statistics', $crawler->text());
```