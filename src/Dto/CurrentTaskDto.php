<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class CurrentTaskDto
{
    public function __construct(
        public string $id, // ID z encji GameTask
        public string $name,
        public string $description,
        public int $sequenceOrder,
    ) {
    }
}