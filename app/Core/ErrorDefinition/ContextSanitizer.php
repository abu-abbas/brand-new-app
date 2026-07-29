<?php

namespace App\Core\ErrorDefinition;

/**
 * Sanitasi recursive terhadap runtime context sebelum diteruskan ke logger.
 *
 * Baseline sensitive keys tidak dapat dihapus oleh konfigurasi aplikasi.
 * Domain-specific keys ditambahkan melalui config `error-definition.additional_sensitive_keys`.
 */
final class ContextSanitizer
{
    /** @var list<string> */
    private const BASELINE_SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'cookie',
        'secret',
        'api_key',
        'client_secret',
        'private_key',
    ];

    /** @var list<string> */
    private readonly array $sensitiveKeys;

    /**
     * @param  list<string>  $additionalKeys
     */
    public function __construct(array $additionalKeys = [])
    {
        $merged = array_merge(
            self::BASELINE_SENSITIVE_KEYS,
            array_map('strtolower', $additionalKeys)
        );

        $this->sensitiveKeys = array_values(array_unique($merged));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function sanitize(array $context): array
    {
        $clean = [];

        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $this->sensitiveKeys, true)) {
                $clean[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $clean[$key] = $this->sanitize($value);
            } elseif (is_scalar($value) || $value === null) {
                $clean[$key] = $value;
            } elseif ($value instanceof \BackedEnum) {
                $clean[$key] = $value->value;
            } elseif ($value instanceof \UnitEnum) {
                $clean[$key] = $value->name;
            } elseif ($value instanceof \Stringable) {
                $clean[$key] = (string) $value;
            } elseif (is_object($value)) {
                $clean[$key] = get_class($value);
            } else {
                $clean[$key] = (string) $value;
            }
        }

        return $clean;
    }

    /**
     * Factory method yang membaca konfigurasi aplikasi.
     */
    public static function fromConfig(): self
    {
        /** @var list<string> $additional */
        $additional = config('error-definition.additional_sensitive_keys', []);

        return new self($additional);
    }
}
