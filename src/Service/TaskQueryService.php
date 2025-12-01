<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\TaskRepository;
use App\Dto\TaskDetailsDto;

class TaskQueryService
{
    public function __construct(
        private readonly TaskRepository $taskRepository
    ) {
    }

    /**
     * Retrieves a task by its ID and returns it as a DTO.
     *
     * @param string $id The UUID of the task.
     * @return TaskDetailsDto|null The task details DTO, or null if not found.
     */
    public function getTaskDetails(string $id): ?TaskDetailsDto
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            return null;
        }

        return new TaskDetailsDto(
            $task->getId()->toRfc4122(),
            $task->getName(),
            $task->getDescription()
        );
    }

    /**
     * Retrieves all tasks and returns them as an array of DTOs.
     *
     * @return TaskDetailsDto[] An array of task details DTOs.
     */
    public function getAllTasks(): array
    {
        $tasks = $this->taskRepository->findAll();
        $taskDtos = [];

        foreach ($tasks as $task) {
            $taskDtos[] = new TaskDetailsDto(
                $task->getId()->toRfc4122(),
                $task->getName(),
                $task->getDescription()
            );
        }

        return $taskDtos;
    }
}
