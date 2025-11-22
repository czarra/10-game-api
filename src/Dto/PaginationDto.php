<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class PaginationDto
{
    public function __construct(
        public int $page,
        public int $limit,
        public int $total,
        public int $pages
    ) {
    }
}
