<?php

namespace App\Core\ErrorDefinition\Exceptions;

use App\Core\ErrorDefinition\ResolvedValidationError;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Validation exception yang membawa list ResolvedValidationError.
 *
 * Extends ValidationException Laravel agar tetap kompatibel dengan
 * middleware dan exception handling bawaan Laravel.
 *
 * @see \App\Core\ErrorDefinition\ErrorResponseRenderer untuk rendering response
 */
final class ErrorValidationException extends ValidationException
{
  /** @var list<ResolvedValidationError> */
  public readonly array $validationErrors;

  /**
   * @param Validator $validator Instance Validator asli dari failedValidation(), bukan stub,
   *                              agar data/rules/errors bawaan Laravel tetap utuh.
   * @param list<ResolvedValidationError> $validationErrors
   */
  public function __construct(
    Validator $validator,
    array $validationErrors,
  ) {
    $this->validationErrors = $validationErrors;

    // Buat summary message seperti Laravel
    $firstMessage = !empty($validationErrors)
      ? $validationErrors[0]->message
      : 'Validasi data gagal.';

    $totalErrors = count($validationErrors);
    $summaryMessage = $totalErrors > 1
      ? "{$firstMessage} (dan " . ($totalErrors - 1) . " error lainnya)"
      : $firstMessage;

    parent::__construct($validator);
    $this->message = $summaryMessage;
  }

  /**
   * @return array<string, list<array{code: string, message: string, retryable: bool}>>
   */
  public function structuredErrors(): array
  {
    $result = [];

    foreach ($this->validationErrors as $error) {
      $result[$error->attribute][] = $error->toPublicArray();
    }

    return $result;
  }
}
