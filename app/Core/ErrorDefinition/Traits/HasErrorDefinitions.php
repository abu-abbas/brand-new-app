<?php

namespace App\Core\ErrorDefinition\Traits;

use App\Core\ErrorDefinition\ErrorCode;
use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ErrorValidationException;
use App\Core\ErrorDefinition\Exceptions\MissingValidationErrorCodeException;
use App\Core\ErrorDefinition\ResolvedValidationError;
use BackedEnum;
use Illuminate\Contracts\Validation\Validator;

/**
 * Trait untuk FormRequest yang memetakan validation rule ke Error Definition.
 *
 * FormRequest yang menggunakan trait ini WAJIB memetakan seluruh rule
 * yang dapat gagal melalui method errorCodes().
 */
trait HasErrorDefinitions
{
  /**
   * Mapping attribute.rule → ErrorCode enum case.
   *
   * Key menggunakan format "attribute.rule" (lowercase).
   * Nested attribute menggunakan dot notation.
   * Wildcard "*.rule" diperiksa setelah exact match.
   *
   * @return array<string, ErrorCode&BackedEnum>
   */
  abstract public function errorCodes(): array;

  /**
   * Error manual yang ditambahkan melalui addValidationError() di after() callback.
   *
   * @var list<ResolvedValidationError>
   */
  protected array $additionalValidationErrors = [];

  /**
   * Override failedValidation dari FormRequest.
   *
   * Memetakan setiap validation failure ke Error Definition,
   * melempar ErrorValidationException jika ada failure.
   * Melempar MissingValidationErrorCodeException jika mapping tidak ditemukan.
   */
  protected function failedValidation(Validator $validator): void
  {
    $failedRules = $validator->failed();
    $mapping = $this->errorCodes();
    $reader = app(ErrorDefinitionReader::class);

    /** @var list<ResolvedValidationError> $validationErrors */
    $validationErrors = [];

    foreach ($failedRules as $field => $rules) {
      foreach ($rules as $ruleName => $ruleArgs) {
        $lookupKey = $field . '.' . strtolower($ruleName);
        $wildcardField = preg_replace('/\d+/', '*', $field);
        $wildcardKey = $wildcardField . '.' . strtolower($ruleName);

        // Exact match dulu, kemudian wildcard
        $enumCase = $mapping[$lookupKey]
          ?? $mapping[$wildcardKey]
          ?? null;

        if ($enumCase === null) {
          throw MissingValidationErrorCodeException::forMapping(
            static::class,
            $lookupKey
          );
        }

        $resolved = $reader->read($enumCase);

        $validationErrors[] = new ResolvedValidationError(
          attribute: $field,
          rule: strtolower($ruleName),
          definition: $resolved,
          message: $this->interpolateMessage($validator, $resolved->message, $field, $ruleName, $ruleArgs),
        );
      }
    }

    // Gabungkan error manual dari addValidationError() di after() callback,
    // jika tidak digabung di sini akan hilang diam-diam dari response.
    $validationErrors = array_merge($validationErrors, $this->additionalValidationErrors);

    throw new ErrorValidationException(
      validator: $validator,
      validationErrors: $validationErrors,
    );
  }

  /**
   * Helper untuk menambahkan validation error dari after() callback.
   *
   * Memastikan error manual tetap memiliki attribute, rule identifier,
   * dan Error Definition yang ter-resolve.
   *
   * @param ErrorCode&BackedEnum $error
   */
  protected function addValidationError(
    Validator $validator,
    string $attribute,
    string $rule,
    ErrorCode&BackedEnum $error,
  ): void {
    $reader = app(ErrorDefinitionReader::class);
    $resolved = $reader->read($error);
    $message = $this->interpolateMessage($validator, $resolved->message, $attribute, $rule, []);

    $validator->errors()->add($attribute, $message);

    $this->additionalValidationErrors[] = new ResolvedValidationError(
      attribute: $attribute,
      rule: $rule,
      definition: $resolved,
      message: $message,
    );
  }

  /**
   * Interpolasi placeholder (:attribute, :min, :max, dst) memakai replacer bawaan Laravel.
   *
   * @param list<mixed> $parameters
   */
  private function interpolateMessage(
    Validator $validator,
    string $message,
    string $attribute,
    string $rule,
    array $parameters,
  ): string {
    if (!method_exists($validator, 'makeReplacements')) {
      return $message;
    }

    return $validator->makeReplacements($message, $attribute, $rule, $parameters);
  }
}
