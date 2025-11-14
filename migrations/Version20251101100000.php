<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Create initial database schema
 *
 * Tables created:
 * - users: Stores user account information.
 * - games: Stores information about city games.
 * - tasks: Stores tasks that can be part of games.
 * - game_tasks: Pivot table for the many-to-many relationship between games and tasks.
 * - user_games: Tracks user progress in a game.
 * - user_game_tasks: Tracks user completion of individual tasks within a game session.
 * - user_tokens: Manages refresh tokens for JWT authentication.
 *
 * This migration sets up the core tables, relationships, indexes, and Row-Level Security (RLS) policies
 * to ensure data integrity and security from the start.
 */
final class Version20251101100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the initial database schema including users, games, tasks, and related tables with RLS policies.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('create extension if not exists "pgcrypto";');

        // =================================================================
        // Create users table
        // =================================================================
        $this->addSql('
            create table users (
                id uuid primary key,
                email varchar(255) not null,
                password varchar(255) not null,
                roles json not null,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL
            );
        ');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email);
        ');
        $this->addSql('COMMENT ON COLUMN users.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN users.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.updated_at IS \'(DC2Type:datetimetz_immutable)\'');

        // =================================================================
        // Create games table
        // =================================================================
        $this->addSql('
            create table games (
                id uuid primary key,
                name varchar(255) not null,
                description text not null,
                is_available boolean not null,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL
            );
        ');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_FF232B315E237E06 ON games (name);
        ');
        $this->addSql('COMMENT ON COLUMN games.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN games.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN games.updated_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN games.deleted_at IS \'(DC2Type:datetimetz_immutable)\'');

        // =================================================================
        // Create tasks table
        // =================================================================
        $this->addSql('
            create table tasks (
                id uuid primary key,
                name varchar(255) not null,
                description text not null,
                latitude decimal(10, 7) not null,
                longitude decimal(10, 7) not null,
                allowed_distance integer not null,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL
            );
        ');
        $this->addSql('COMMENT ON COLUMN tasks.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN tasks.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN tasks.updated_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN tasks.deleted_at IS \'(DC2Type:datetimetz_immutable)\'');

        // =================================================================
        // Create game_tasks table
        // =================================================================
        $this->addSql('
            create table game_tasks (
                id uuid primary key,
                game_id uuid not null references games(id) on delete cascade,
                task_id uuid not null references tasks(id) on delete cascade,
                sequence_order integer not null,
                deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL
            );
        ');
        $this->addSql('
            create unique index game_tasks_game_id_sequence_order_unique
            on game_tasks (game_id, sequence_order)
            where deleted_at is null;
        ');
        $this->addSql('COMMENT ON COLUMN game_tasks.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_tasks.game_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_tasks.task_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_tasks.deleted_at IS \'(DC2Type:datetimetz_immutable)\'');

        // =================================================================
        // Create user_games table
        // =================================================================
        $this->addSql('
            create table user_games (
                id uuid primary key,
                user_id uuid not null references users(id) on delete cascade,
                game_id uuid not null references games(id) on delete cascade,
                started_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                completed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL
            );
        ');
        $this->addSql('
            create unique index user_games_user_id_game_id_unique_active
            on user_games (user_id, game_id)
            where completed_at is null;
        ');
        $this->addSql('COMMENT ON COLUMN user_games.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_games.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_games.game_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_games.started_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_games.completed_at IS \'(DC2Type:datetimetz_immutable)\'');

        // =================================================================
        // Create user_game_tasks table
        // =================================================================
        $this->addSql('
            create table user_game_tasks (
                id uuid primary key,
                user_game_id uuid not null references user_games(id) on delete cascade,
                game_task_id uuid not null references game_tasks(id) on delete cascade,
                completed_at TIMESTAMP(0) WITH TIME ZONE NOT NULL
            );
        ');
        $this->addSql('
            create unique index user_game_tasks_user_game_id_game_task_id_unique
            on user_game_tasks (user_game_id, game_task_id);
        ');
        $this->addSql('COMMENT ON COLUMN user_game_tasks.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_game_tasks.user_game_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_game_tasks.game_task_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_game_tasks.completed_at IS \'(DC2Type:datetimetz_immutable)\'');

        // =================================================================
        // Create user_tokens table
        // =================================================================
        $this->addSql('CREATE SEQUENCE user_tokens_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE user_tokens (id INT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CF080AB3C74F2195 ON user_tokens (refresh_token)');

        // =================================================================
        // Apply Row-Level Security (RLS)
        // =================================================================

        // --- RLS for users table ---
        $this->addSql('alter table users enable row level security;');
        $this->addSql('create policy admin_all_access on users for all using (current_setting(\'app.current_user_role\', true) = \'ROLE_ADMIN\');');
        $this->addSql('create policy user_self_access on users for all using (id = current_setting(\'app.current_user_id\', true)::uuid);');

        // --- RLS for games table ---
        $this->addSql('alter table games enable row level security;');
        $this->addSql('create policy admin_all_access on games for all using (current_setting(\'app.current_user_role\', true) = \'ROLE_ADMIN\');');
        $this->addSql('create policy user_select_available on games for select using (is_available = true and deleted_at is null);');

        // --- RLS for tasks table ---
        $this->addSql('alter table tasks enable row level security;');
        $this->addSql('create policy admin_all_access on tasks for all using (current_setting(\'app.current_user_role\', true) = \'ROLE_ADMIN\');');

        // --- RLS for game_tasks table ---
        $this->addSql('alter table game_tasks enable row level security;');
        $this->addSql('create policy admin_all_access on game_tasks for all using (current_setting(\'app.current_user_role\', true) = \'ROLE_ADMIN\');');
        $this->addSql('
            create policy user_select_playing on game_tasks for select using (
                exists (
                    select 1 from user_games ug
                    where ug.game_id = game_tasks.game_id and ug.user_id = current_setting(\'app.current_user_id\', true)::uuid
                )
            );
        ');

        // --- RLS for user_games table ---
        $this->addSql('alter table user_games enable row level security;');
        $this->addSql('create policy admin_select_access on user_games for select using (current_setting(\'app.current_user_role\', true) = \'ROLE_ADMIN\');');
        $this->addSql('create policy user_self_access on user_games for all using (user_id = current_setting(\'app.current_user_id\', true)::uuid);');

        // --- RLS for user_game_tasks table ---
        $this->addSql('alter table user_game_tasks enable row level security;');
        $this->addSql('create policy admin_select_access on user_game_tasks for select using (current_setting(\'app.current_user_role\', true) = \'ROLE_ADMIN\');');
        $this->addSql('create policy user_self_access on user_game_tasks for all using (
                exists (
                    select 1 from user_games ug
                    where ug.id = user_game_id and ug.user_id = current_setting(\'app.current_user_id\', true)::uuid
                )
            );');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('drop table if exists user_game_tasks cascade;');
        $this->addSql('drop table if exists user_games cascade;');
        $this->addSql('drop table if exists game_tasks cascade;');
        $this->addSql('drop table if exists tasks cascade;');
        $this->addSql('drop table if exists games cascade;');
        $this->addSql('drop table if exists user_tokens cascade;');
        $this->addSql('drop table if exists users cascade;');
    }
}

