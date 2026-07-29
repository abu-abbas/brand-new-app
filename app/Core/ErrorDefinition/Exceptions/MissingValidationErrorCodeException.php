<?php

namespace App\Core\ErrorDefinition\Exceptions;

use RuntimeException;

/**
 * Dilempar ketika FormRequest dengan HasErrorDefinitions tidak memetakan
 * seluruh rule yang gagal ke error code.
 */
final class MissingValidationErrorCodeException extends RuntimeException
{
    public static function forMapping(string $requestClass, string $lookupKey): self
    {
        return new self(
            "FormRequest [{$requestClass}] tidak memiliki mapping error code untuk [{$lookupKey}]. "
              .'Tambahkan mapping pada method errorCodes().'
        );
    }
}
