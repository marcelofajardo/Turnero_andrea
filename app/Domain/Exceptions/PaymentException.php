<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

/** Thrown when a payment operation fails or is rejected */
final class PaymentException extends \RuntimeException
{
    public function __construct(string $message = 'Payment processing failed.', int $code = 402)
    {
        parent::__construct($message, $code);
    }
}