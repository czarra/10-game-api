<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class PaginatedResponseDto
{
    /**
     * @param array<mixed> $data
     */
    public function __construct(
        public array $data,
        public PaginationDto $pagination
    ) {
    }
}
