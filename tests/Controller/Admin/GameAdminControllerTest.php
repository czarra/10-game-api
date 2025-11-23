<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Repository\GameRepository;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class GameAdminControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $container = $this->client->getContainer();

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $adminUser = UserFactory::new()
            ->asAdmin()
            ->withHashedPassword($passwordHasher, 'password')
            ->create(['email' => 'admin-game-test@example.com'])
            ->object();

        $this->client->loginUser($adminUser, 'admin');
    }

    /**
     * Test US-002: Create a new game through the admin panel, save it, and verify in DB.
     */
    public function testAdminCanCreateNewGame(): void
    {
        // 1. Navigate to the game creation page
        $crawler = $this->client->request('GET', '/admin/app/game/create');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Informacje O Grze', $crawler->html());

        // 2. Submit the form with valid data
        $form = $crawler->selectButton('btn_create_and_list')->form();
        parse_str(parse_url($form->getUri(), PHP_URL_QUERY), $query);
        $formName = $query['uniqid'];

        $this->client->submit($form, [
            $formName . '[name]' => 'My E2E Test Game',
            $formName . '[description]' => 'A description created during an E2E test.',
        ]);

        // 3. Assert that the response is a redirect to the list page
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringStartsWith('/admin/app/game/list', $this->client->getResponse()->headers->get('Location'));

        // 4. Verify the entity was created in the database
        $container = $this->client->getContainer();
        /** @var GameRepository $gameRepository */
        $gameRepository = $container->get(GameRepository::class);

        $game = $gameRepository->findOneBy(['name' => 'My E2E Test Game']);

        $this->assertNotNull($game, 'The game should exist in the database.');
        $this->assertSame('A description created during an E2E test.', $game->getDescription());
        $this->assertFalse($game->isAvailable(), 'The game should be unavailable by default.');
    }
}
