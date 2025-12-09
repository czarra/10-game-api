<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */

final class Version20251114143424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sample game';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO games (id, name, description, is_available, created_at, updated_at) VALUES ('dc993c3c-b8e7-44c6-a320-eb9a3a149c4f', 'Przykładowa gra', 'To jest opis przykładowej gry.', false, '2025-12-09 10:00:00', '2025-12-09 10:00:00')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM games WHERE id = 'dc993c3c-b8e7-44c6-a320-eb9a3a149c4f'");
    }
}
