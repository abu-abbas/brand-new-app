<?php

namespace App\Core\ErrorDefinition;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final readonly class ErrorDefinition
{
    public function __construct(
        public string $message,
        public ErrorCategory $category,
        public int $httpStatus,
        public ErrorSeverity $severity = ErrorSeverity::MEDIUM,
        public bool $retryable = false,
    ) {}
}
