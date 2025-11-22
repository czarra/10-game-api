<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Game;
use App\Entity\GameTask;
use App\Entity\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolationBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class GameTest extends TestCase
{
    private ExecutionContextInterface $context;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
    }

    public function testValidateTaskSequenceSucceedsWithValidTasks(): void
    {
        $game = new Game();
        $game->addGameTask($this->createGameTask(1, new Task()));
        $game->addGameTask($this->createGameTask(2, new Task()));
        $game->addGameTask($this->createGameTask(3, new Task()));

        $this->context->expects($this->never())->method('buildViolation');

        $game->validateTaskSequence($this->context);
    }

    public function testValidateTaskSequenceFailsWithDuplicateSequenceOrder(): void
    {
        $game = new Game();
        $game->addGameTask($this->createGameTask(1, new Task()));
        $game->addGameTask($this->createGameTask(1, new Task())); // Duplicate sequence

        $violationBuilder = $this->createMock(\Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->willReturn($violationBuilder);
        $violationBuilder->method('setParameter')->willReturn($violationBuilder);
        $violationBuilder->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('Kolejność zadań musi być unikalna. Wartość "{{ value }}" jest zduplikowana.')
            ->willReturn($violationBuilder);

        $game->validateTaskSequence($this->context);
    }

    public function testValidateTaskSequenceFailsWithDuplicateTask(): void
    {
        $task = new Task();
        $task->setName('Duplicate Task');
        $game = new Game();
        $game->addGameTask($this->createGameTask(1, $task));
        $game->addGameTask($this->createGameTask(2, $task)); // Duplicate task

        $violationBuilder = $this->createMock(\Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->willReturn($violationBuilder);
        $violationBuilder->method('setParameter')->willReturn($violationBuilder);
        $violationBuilder->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('Zadanie "{{ taskName }}" zostało już przypisane do tej gry.')
            ->willReturn($violationBuilder);

        $game->validateTaskSequence($this->context);
    }

    private function createGameTask(int $sequenceOrder, Task $task): GameTask
    {
        $gameTask = new GameTask();
        $gameTask->setSequenceOrder($sequenceOrder);
        $gameTask->setTask($task);
        return $gameTask;
    }
}
