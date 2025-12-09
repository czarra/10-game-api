<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */

final class Version20251209143424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add two test games with associated tasks';
    }

    public function up(Schema $schema): void
    {
        $currentTimestamp = (new \DateTimeImmutable('2025-12-09 17:26:16'))->format('Y-m-d H:i:s');

        // Game 1 UUID and details
        $game1Id = '4b0d0c11-92e0-4a8e-8a2a-e1d8c1e4d7e9';
        $game1Name = 'Przygoda w Starym Porcie';
        $game1Description = 'Odkryj tajemnice zapomnianego portu, pełnego starych legend i ukrytych skarbów.';

        // Game 2 UUID and details
        $game2Id = 'd6e7f8a9-b0c1-2d3e-4f5a-6b7c8d9e0f1a';
        $game2Name = 'Tajemnice Królewskiego Wzgórza';
        $game2Description = 'Przemierzaj historyczne wzgórze, odkrywając zapomniane komnaty i królewskie sekrety.';

        // Task UUIDs
        $task1_1_id = '4c2c54d7-1b8f-4a0b-9c7d-e6a2f3a6b5d1';
        $task1_2_id = '2d1f9a0c-6a7b-4e1f-8e0c-c9d2e1f3a5b7';
        $task1_3_id = '9a8f7d6e-5c4b-3a2d-1b0c-a9b8c7d6e5f4';
        $task1_4_id = '3e2d1c0b-4a5b-6c7d-8e9f-0a1b2c3d4e5f';

        $task2_1_id = '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d';
        $task2_2_id = '6f5e4d3c-2b1a-0f9e-8d7c-6b5a4f3e2d1c';
        $task2_3_id = 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d';
        $task2_4_id = 'b1c2d3e4-f5a6-b7c8-d9e0-f1a2b3c4d5e6';
        $task2_5_id = 'c1d2e3f4-a5b6-c7d8-e9f0-a1b2c3d4e5f6';

        // GameTask UUIDs
        $gameTask1_1_id = 'e1a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c';
        $gameTask1_2_id = 'f1a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c';
        $gameTask1_3_id = '01a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c';
        $gameTask1_4_id = '11a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c';

        $gameTask2_1_id = '21a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c';
        $gameTask2_2_id = '31a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c';
        $gameTask2_3_id = '41a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c';
        $gameTask2_4_id = '51a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c';
        $gameTask2_5_id = '61a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c';


        // Insert Tasks
        $this->addSql("INSERT INTO tasks (id, name, description, latitude, longitude, allowed_distance, created_at, updated_at) VALUES
            ('{$task1_1_id}', 'Zaginiony Latarnik', 'Znajdź ślady latarnika, który zniknął w niewyjaśnionych okolicznościach.', '54.3524', '18.6466', 100, '{$currentTimestamp}', '{$currentTimestamp}'),
            ('{$task1_2_id}', 'Szept Syren', 'Rozszyfruj tajemnicze melodie, które niosą się z głębin morza.', '54.3500', '18.6500', 150, '{$currentTimestamp}', '{$currentTimestamp}'),
            ('{$task1_3_id}', 'Skarb Piratów', 'Odnajdź ukryty skarb legendarnego pirata Czarnobrodego.', '54.3480', '18.6450', 80, '{$currentTimestamp}', '{$currentTimestamp}'),
            ('{$task1_4_id}', 'Ostatnia Przystań', 'Dotrzyj do miejsca, gdzie spoczywają wraki statków i poznaj ich historie.', '54.3450', '18.6480', 120, '{$currentTimestamp}', '{$currentTimestamp}'),
            ('{$task2_1_id}', 'Korona Władcy', 'Znajdź ukrytą koronę, symbol dawnej potęgi.', '50.0594', '19.9366', 90, '{$currentTimestamp}', '{$currentTimestamp}'),
            ('{$task2_2_id}', 'Komnata Alchemika', 'Odszukaj tajne laboratorium alchemika i odkryj jego eliksiry.', '50.0600', '19.9380', 110, '{$currentTimestamp}', '{$currentTimestamp}'),
            ('{$task2_3_id}', 'Smocze Jajo', 'Zlokalizuj legendarne smocze jajo, strzeżone przez pradawne moce.', '50.0610', '19.9350', 130, '{$currentTimestamp}', '{$currentTimestamp}'),
            ('{$task2_4_id}', 'Zaginiony Miecz', 'Odgrzeb miecz króla, który zaginął podczas wielkiej bitwy.', '50.0580', '19.9370', 100, '{$currentTimestamp}', '{$currentTimestamp}'),
            ('{$task2_5_id}', 'Królewski Tron', 'Dotrzyj do królewskiego tronu i zasiądź na nim symbolicznie.', '50.0570', '19.9390', 70, '{$currentTimestamp}', '{$currentTimestamp}')
        ");

        // Insert Games
        $this->addSql("INSERT INTO games (id, name, description, is_available, created_at, updated_at) VALUES
            ('{$game1Id}', '{$game1Name}', '{$game1Description}', true, '{$currentTimestamp}', '{$currentTimestamp}'),
            ('{$game2Id}', '{$game2Name}', '{$game2Description}', true, '{$currentTimestamp}', '{$currentTimestamp}')
        ");

        // Insert GameTasks for Game 1
        $this->addSql("INSERT INTO game_tasks (id, game_id, task_id, sequence_order) VALUES
            ('{$gameTask1_1_id}', '{$game1Id}', '{$task1_1_id}', 1),
            ('{$gameTask1_2_id}', '{$game1Id}', '{$task1_2_id}', 2),
            ('{$gameTask1_3_id}', '{$game1Id}', '{$task1_3_id}', 3),
            ('{$gameTask1_4_id}', '{$game1Id}', '{$task1_4_id}', 4)
        ");

        // Insert GameTasks for Game 2
        $this->addSql("INSERT INTO game_tasks (id, game_id, task_id, sequence_order) VALUES
            ('{$gameTask2_1_id}', '{$game2Id}', '{$task2_1_id}', 1),
            ('{$gameTask2_2_id}', '{$game2Id}', '{$task2_2_id}', 2),
            ('{$gameTask2_3_id}', '{$game2Id}', '{$task2_3_id}', 3),
            ('{$gameTask2_4_id}', '{$game2Id}', '{$task2_4_id}', 4),
            ('{$gameTask2_5_id}', '{$game2Id}', '{$task2_5_id}', 5)
        ");
    }

    public function down(Schema $schema): void
    {
        $game1Id = '4b0d0c11-92e0-4a8e-8a2a-e1d8c1e4d7e9';
        $game2Id = 'd6e7f8a9-b0c1-2d3e-4f5a-6b7c8d9e0f1a';

        $task1_1_id = '4c2c54d7-1b8f-4a0b-9c7d-e6a2f3a6b5d1';
        $task1_2_id = '2d1f9a0c-6a7b-4e1f-8e0c-c9d2e1f3a5b7';
        $task1_3_id = '9a8f7d6e-5c4b-3a2d-1b0c-a9b8c7d6e5f4';
        $task1_4_id = '3e2d1c0b-4a5b-6c7d-8e9f-0a1b2c3d4e5f';

        $task2_1_id = '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d';
        $task2_2_id = '6f5e4d3c-2b1a-0f9e-8d7c-6b5a4f3e2d1c';
        $task2_3_id = 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d';
        $task2_4_id = 'b1c2d3e4-f5a6-b7c8-d9e0-f1a2b3c4d5e6';
        $task2_5_id = 'c1d2e3f4-a5b6-c7d8-e9f0-a1b2c3d4e5f6';

        // Delete GameTasks first to respect foreign key constraints
        $this->addSql("DELETE FROM game_tasks WHERE game_id IN ('{$game1Id}', '{$game2Id}')");
        // Delete Games
        $this->addSql("DELETE FROM games WHERE id IN ('{$game1Id}', '{$game2Id}')");
        // Delete Tasks
        $this->addSql("DELETE FROM tasks WHERE id IN ('{$task1_1_id}', '{$task1_2_id}', '{$task1_3_id}', '{$task1_4_id}', '{$task2_1_id}', '{$task2_2_id}', '{$task2_3_id}', '{$task2_4_id}', '{$task2_5_id}')");
    }
}