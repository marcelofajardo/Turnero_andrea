<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

/** Thrown when a requested time slot is not available for booking */
final class SlotNotAvailableException extends \RuntimeException
{
    public function __construct(string $message = 'The selected time slot is not available.')
    {
        parent::__construct($message, 409);
    }
}