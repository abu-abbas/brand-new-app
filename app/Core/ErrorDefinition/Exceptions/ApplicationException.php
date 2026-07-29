<?php

namespace App\Core\ErrorDefinition\Exceptions;

use App\Core\ErrorDefinition\ApplicationExceptionReporter;
use App\Core\ErrorDefinition\ErrorResponseRenderer;
use App\Core\ErrorDefinition\ResolvedErrorDefinition;
use RuntimeException;
use Throwable;

/**
 * Exception standar aplikasi yang membawa Error Definition yang sudah di-resolve.
 *
 * ApplicationException hanya berfungsi sebagai data carrier.
 * Tidak melakukan resolve enum, render response, logging, maupun sanitasi context.
 *
 * @see ErrorResponseRenderer untuk rendering response
 * @see ApplicationExceptionReporter untuk logging
 */
final class ApplicationException extends RuntimeException
{
    /**
     * @param  ResolvedErrorDefinition  $definition  Definition yang sudah di-resolve
     * @param  array<string, mixed>  $context  Runtime context untuk logging/debugging
     * @param  Throwable|null  $previous  Optional previous exception
     */
    public function __construct(
        public readonly ResolvedErrorDefinition $definition,
        public readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $definition->message,
            code: 0,
            previous: $previous,
        );
    }
}
