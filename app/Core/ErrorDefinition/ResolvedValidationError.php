<?php

namespace App\Core\ErrorDefinition;

/**
 * DTO typed untuk setiap validation error yang sudah di-resolve.
 * Menggabungkan attribute aktual, rule identifier, dan definition.
 */
final readonly class ResolvedValidationError
{
    /**
     * @param  string  $message  Message definition dengan placeholder (:attribute, :min, :max) sudah diinterpolasi.
     */
    public function __construct(
        public string $attribute,
        public string $rule,
        public ResolvedErrorDefinition $definition,
        public string $message,
    ) {}

    /**
     * @return array{code: string, message: string, retryable: bool}
     */
    public function toPublicArray(): array
    {
        return [
            'code' => $this->definition->code,
            'message' => $this->message,
            'retryable' => $this->definition->retryable,
        ];
    }
}
