<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\Game;
use App\Validator\AtLeastThreeTasks;
use App\Validator\AtLeastThreeTasksValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\Common\Collections\ArrayCollection;

final class AtLeastThreeTasksValidatorTest extends TestCase
{
    private ExecutionContextInterface|MockObject $context;
    private AtLeastThreeTasksValidator $validator;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new AtLeastThreeTasksValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidateDoesNothingIfGameNotAvailable(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('isAvailable')->willReturn(false);
        $game->method('getGameTasks')->willReturn(new ArrayCollection([1])); // Less than 3

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($game, new AtLeastThreeTasks());
    }

    public function testValidateAddsViolationIfAvailableAndNotEnoughTasks(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('isAvailable')->willReturn(true);
        $game->method('getGameTasks')->willReturn(new ArrayCollection([1, 2])); // Only 2 tasks

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->willReturn($violationBuilder);
        $violationBuilder->method('addViolation')->willReturn(null);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->validator->validate($game, new AtLeastThreeTasks());
    }

    public function testValidateSucceedsIfAvailableAndEnoughTasks(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('isAvailable')->willReturn(true);
        $game->method('getGameTasks')->willReturn(new ArrayCollection([1, 2, 3])); // 3 tasks

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($game, new AtLeastThreeTasks());
    }
}
