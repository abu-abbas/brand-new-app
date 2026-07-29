<?php

namespace App\Core\ErrorDefinition\Exceptions;

use RuntimeException;

/**
 * Dilempar ketika enum ErrorCode tidak memiliki attribute #[ErrorDefinition].
 */
final class MissingErrorDefinitionException extends RuntimeException
{
    public static function forCase(string $enumClass, string $caseName): self
    {
        return new self(
            "Case [{$caseName}] pada [{$enumClass}] tidak memiliki attribute #[ErrorDefinition]."
        );
    }
}
