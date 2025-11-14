<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251113221555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_tasks DROP CONSTRAINT game_tasks_game_id_fkey');
        $this->addSql('ALTER TABLE game_tasks DROP CONSTRAINT game_tasks_task_id_fkey');
        $this->addSql('DROP INDEX game_tasks_game_id_sequence_order_unique');
        $this->addSql('ALTER TABLE game_tasks ADD CONSTRAINT FK_626B1C18E48FD905 FOREIGN KEY (game_id) REFERENCES games (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_tasks ADD CONSTRAINT FK_626B1C188DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX game_tasks_game_id_sequence_order_unique ON game_tasks (game_id, sequence_order) WHERE deleted_at IS NULL');
        $this->addSql('ALTER TABLE user_game_tasks DROP CONSTRAINT user_game_tasks_game_task_id_fkey');
        $this->addSql('ALTER TABLE user_game_tasks DROP CONSTRAINT user_game_tasks_user_game_id_fkey');
        $this->addSql('ALTER TABLE user_game_tasks ADD CONSTRAINT FK_C50CDAF4BC82C70F FOREIGN KEY (user_game_id) REFERENCES user_games (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_game_tasks ADD CONSTRAINT FK_C50CDAF418747EA9 FOREIGN KEY (game_task_id) REFERENCES game_tasks (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_games DROP CONSTRAINT user_games_game_id_fkey');
        $this->addSql('ALTER TABLE user_games DROP CONSTRAINT user_games_user_id_fkey');
        $this->addSql('DROP INDEX user_games_user_id_game_id_unique_active');
        $this->addSql('ALTER TABLE user_games ADD CONSTRAINT FK_1DE1D069A76ED395 FOREIGN KEY (user_id) REFERENCES "users" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_games ADD CONSTRAINT FK_1DE1D069E48FD905 FOREIGN KEY (game_id) REFERENCES games (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX user_games_user_id_game_id_unique_active ON user_games (user_id, game_id) WHERE completed_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE user_games DROP CONSTRAINT FK_1DE1D069A76ED395');
        $this->addSql('ALTER TABLE user_games DROP CONSTRAINT FK_1DE1D069E48FD905');
        $this->addSql('DROP INDEX user_games_user_id_game_id_unique_active');
        $this->addSql('ALTER TABLE user_games ADD CONSTRAINT user_games_game_id_fkey FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_games ADD CONSTRAINT user_games_user_id_fkey FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX user_games_user_id_game_id_unique_active ON user_games (user_id, game_id) WHERE (completed_at IS NULL)');
        $this->addSql('ALTER TABLE game_tasks DROP CONSTRAINT FK_626B1C18E48FD905');
        $this->addSql('ALTER TABLE game_tasks DROP CONSTRAINT FK_626B1C188DB60186');
        $this->addSql('DROP INDEX game_tasks_game_id_sequence_order_unique');
        $this->addSql('ALTER TABLE game_tasks ADD CONSTRAINT game_tasks_game_id_fkey FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_tasks ADD CONSTRAINT game_tasks_task_id_fkey FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX game_tasks_game_id_sequence_order_unique ON game_tasks (game_id, sequence_order) WHERE (deleted_at IS NULL)');
        $this->addSql('ALTER TABLE user_game_tasks DROP CONSTRAINT FK_C50CDAF4BC82C70F');
        $this->addSql('ALTER TABLE user_game_tasks DROP CONSTRAINT FK_C50CDAF418747EA9');
        $this->addSql('ALTER TABLE user_game_tasks ADD CONSTRAINT user_game_tasks_game_task_id_fkey FOREIGN KEY (game_task_id) REFERENCES game_tasks (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_game_tasks ADD CONSTRAINT user_game_tasks_user_game_id_fkey FOREIGN KEY (user_game_id) REFERENCES user_games (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
