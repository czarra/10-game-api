<?php

declare(strict_types=1);

namespace App\Service\Exception;

class GameHasNoTasksException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Game has no tasks.');
    }
}
