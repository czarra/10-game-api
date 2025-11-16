<?php

declare(strict_types=1);

namespace App\Service\Exception;

class GameUnavailableException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Game is not available.');
    }
}
