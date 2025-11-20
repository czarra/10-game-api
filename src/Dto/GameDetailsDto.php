<?php

declare(strict_types=1);

namespace App\Dto;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final class GameDetailsDto
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid', description: 'UUID gry')]
        public readonly string $id,
        #[OA\Property(type: 'string', description: 'Nazwa gry')]
        public readonly string $name,
        #[OA\Property(type: 'string', description: 'Opis gry')]
        public readonly string $description,
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: TaskDetailsDto::class)))]
        public readonly array $tasks
    ) {}
}
