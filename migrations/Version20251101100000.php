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
                id uuid primary key default gen_random_uuid(),
                email varchar(255) not null unique,
                password varchar(255) not null,
                roles json not null,
                created_at timestamptz not null default current_timestamp,
                updated_at timestamptz not null default current_timestamp
            );
        ');

        // =================================================================
        // Create games table
        // =================================================================
        $this->addSql('
            create table games (
                id uuid primary key default gen_random_uuid(),
                name varchar(255) not null unique,
                description text not null,
                is_available boolean not null default false,
                created_at timestamptz not null default current_timestamp,
                updated_at timestamptz not null default current_timestamp,
                deleted_at timestamptz null
            );
        ');

        // =================================================================
        // Create tasks table
        // =================================================================
        $this->addSql('
            create table tasks (
                id uuid primary key default gen_random_uuid(),
                name varchar(255) not null,
                description text not null,
                latitude decimal(10, 7) not null,
                longitude decimal(10, 7) not null,
                allowed_distance integer not null,
                created_at timestamptz not null default current_timestamp,
                updated_at timestamptz not null default current_timestamp,
                deleted_at timestamptz null
            );
        ');

        // =================================================================
        // Create game_tasks table
        // =================================================================
        $this->addSql('
            create table game_tasks (
                id uuid primary key default gen_random_uuid(),
                game_id uuid not null references games(id) on delete cascade,
                task_id uuid not null references tasks(id) on delete cascade,
                sequence_order integer not null,
                deleted_at timestamptz null
            );
        ');
        $this->addSql('
            create unique index game_tasks_game_id_sequence_order_unique
            on game_tasks (game_id, sequence_order)
            where deleted_at is null;
        ');

        // =================================================================
        // Create user_games table
        // =================================================================
        $this->addSql('
            create table user_games (
                id uuid primary key default gen_random_uuid(),
                user_id uuid not null references users(id) on delete cascade,
                game_id uuid not null references games(id) on delete cascade,
                started_at timestamptz not null default current_timestamp,
                completed_at timestamptz null
            );
        ');
        $this->addSql('
            create unique index user_games_user_id_game_id_unique_active
            on user_games (user_id, game_id)
            where completed_at is null;
        ');

        // =================================================================
        // Create user_game_tasks table
        // =================================================================
        $this->addSql('
            create table user_game_tasks (
                id uuid primary key default gen_random_uuid(),
                user_game_id uuid not null references user_games(id) on delete cascade,
                game_task_id uuid not null references game_tasks(id) on delete cascade,
                completed_at timestamptz not null default current_timestamp
            );
        ');
        $this->addSql('
            create unique index user_game_tasks_user_game_id_game_task_id_unique
            on user_game_tasks (user_game_id, game_task_id);
        ');

        // =================================================================
        // Create user_tokens table
        // =================================================================
        $this->addSql('
            create table user_tokens (
                id uuid primary key default gen_random_uuid(),
                user_id uuid not null references users(id) on delete cascade,
                token varchar(255) not null unique,
                expires_at timestamptz not null,
                created_at timestamptz not null default current_timestamp
            );
        ');

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

        // --- RLS for user_tokens table ---
        $this->addSql('alter table user_tokens enable row level security;');
        $this->addSql('create policy user_self_access on user_tokens for all using (user_id = current_setting(\'app.current_user_id\', true)::uuid);');
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

