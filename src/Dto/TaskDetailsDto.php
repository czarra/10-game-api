<?php

declare(strict_types=1);

namespace App\Dto;

use OpenApi\Attributes as OA;

final class TaskDetailsDto
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid', description: 'UUID zadania')]
        public readonly string $id,
        #[OA\Property(type: 'string', description: 'Nazwa zadania')]
        public readonly string $name,
        #[OA\Property(type: 'string', description: 'Opis zadania')]
        public readonly string $description,
        #[OA\Property(type: 'integer', description: 'Kolejność zadania w grze', nullable: true)]
        public readonly ?int $sequenceOrder = null
    ) {}
}
