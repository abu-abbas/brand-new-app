<?php

namespace App\Core\ErrorDefinition;

final readonly class ResolvedErrorDefinition
{
  public function __construct(
    public string $code,
    public string $message,
    public ErrorCategory $category,
    public int $httpStatus,
    public ErrorSeverity $severity,
    public bool $retryable,
  ) {}

  /**
   * @return array{message: string, code: string, retryable: bool}
   */
  public function toPublicArray(): array
  {
    return [
      'message' => $this->message,
      'code' => $this->code,
      'retryable' => $this->retryable,
    ];
  }
}
