<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class AdminLoginControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private const ADMIN_USERNAME = 'admin@example.com';
    private const ADMIN_PASSWORD = 'password';

    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Boot the kernel to access the container
        self::bootKernel();
        $container = static::getContainer();

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        UserFactory::new()
            ->asAdmin()
            ->withHashedPassword($passwordHasher, self::ADMIN_PASSWORD)
            ->with([
                'email' => self::ADMIN_USERNAME,
            ])
            ->create();
    }

    public function testAdminLogsInSuccessfully(): void
    {
        $crawler = $this->client->request('GET', '/admin/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.login-logo a span', 'Login');

        $this->client->submitForm('Login', [
            'admin_login_form[email]' => self::ADMIN_USERNAME,
            'admin_login_form[password]' => self::ADMIN_PASSWORD,
        ]);

        // Should be a redirect after successful login
        $this->assertResponseStatusCodeSame(302);

        // Follow the redirect to the admin dashboard
        $crawler = $this->client->followRedirect();

        // Assert that we are on the admin dashboard by checking for key texts
        $this->assertStringContainsString('Game Management', $crawler->text());
        $this->assertStringContainsString('Statistics', $crawler->text());
        $this->assertResponseIsSuccessful(); // Check if the dashboard itself loaded successfully
    }

    public function testAdminLoginFailsWithInvalidCredentials(): void
    {
        $this->client->request('GET', '/admin/login');

        $this->client->submitForm('Login', [
            'admin_login_form[email]' => 'wrong@example.com',
            'admin_login_form[password]' => 'wrongpassword',
        ]);

        // Should be a redirect back to the login page
        $this->assertResponseStatusCodeSame(302);

        $crawler = $this->client->followRedirect();

        // Assert that we are still on the login page and an error message is present
        $this->assertSelectorTextContains('.login-logo a span', 'Login');
        $this->assertSelectorTextContains('.alert-danger', 'Invalid credentials.');
    }

    public function testAdminAreaIsProtected(): void
    {
        // Attempt to access a protected admin route without logging in
        $this->client->request('GET', '/admin/dashboard'); // Assuming /admin/dashboard is a protected route

        // Should be redirected to the login page
        $this->assertResponseStatusCodeSame(302);

        $crawler = $this->client->followRedirect();

        // Assert that the redirected page is the login page
        $this->assertSelectorTextContains('.login-logo a span', 'Login');
        $this->assertResponseIsSuccessful();
    }
}
