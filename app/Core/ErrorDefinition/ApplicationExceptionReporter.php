<?php

namespace App\Core\ErrorDefinition;

use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use Psr\Log\LoggerInterface;

/**
 * Reporter terpusat untuk ApplicationException.
 *
 * Setiap ApplicationException dicatat tepat satu kali.
 * ErrorValidationException TIDAK dicatat (volume tinggi, nilai rendah).
 *
 * Severity mapping ke PSR-3 log level:
 * - LOW      → info
 * - MEDIUM   → warning
 * - HIGH     → error
 * - CRITICAL → critical
 */
final class ApplicationExceptionReporter
{
  public function __construct(
    private readonly LoggerInterface $logger,
    private readonly ContextSanitizer $sanitizer,
  ) {}

  /**
   * Catat ApplicationException ke logger dengan structured context.
   */
  public function report(ApplicationException $exception): void
  {
    $definition = $exception->definition;
    $logLevel = $this->mapSeverityToLogLevel($definition->severity);

    $logContext = [
      'error_code' => $definition->code,
      'category' => strtolower($definition->category->value),
      'severity' => strtolower($definition->severity->value),
      'retryable' => $definition->retryable,
      'http_status' => $definition->httpStatus,
      'context' => $this->sanitizer->sanitize($exception->context),
      'exception' => $exception,
    ];

    $this->logger->{$logLevel}($definition->message, $logContext);
  }

  private function mapSeverityToLogLevel(ErrorSeverity $severity): string
  {
    return match ($severity) {
      ErrorSeverity::LOW => 'info',
      ErrorSeverity::MEDIUM => 'warning',
      ErrorSeverity::HIGH => 'error',
      ErrorSeverity::CRITICAL => 'critical',
    };
  }
}
