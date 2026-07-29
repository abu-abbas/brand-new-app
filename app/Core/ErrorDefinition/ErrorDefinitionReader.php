<?php

namespace App\Core\ErrorDefinition;

use App\Core\ErrorDefinition\Exceptions\MissingErrorDefinitionException;
use BackedEnum;
use ReflectionEnumUnitCase;

/**
 * Membaca satu enum case ErrorCode, mengambil attribute ErrorDefinition,
 * melakukan validasi dasar, dan mengembalikan ResolvedErrorDefinition.
 *
 * Reader TIDAK melakukan discovery, pemindaian folder, validasi duplicate
 * error code, pembuatan catalog, logging, registry, atau integrasi ITSM.
 */
final class ErrorDefinitionReader
{
    /**
     * @var array<string, ResolvedErrorDefinition>
     */
    private array $cache = [];

    /**
     * Membaca satu enum case dan mengembalikan definition yang sudah di-resolve.
     *
     * @throws MissingErrorDefinitionException
     */
    public function read(ErrorCode&BackedEnum $enumCase): ResolvedErrorDefinition
    {
        $code = (string) $enumCase->value;

        if (isset($this->cache[$code])) {
            return $this->cache[$code];
        }

        $className = get_class($enumCase);
        $ref = new ReflectionEnumUnitCase($className, $enumCase->name);
        $attributes = $ref->getAttributes(ErrorDefinition::class);

        if (empty($attributes)) {
            throw MissingErrorDefinitionException::forCase($className, $enumCase->name);
        }

        /** @var ErrorDefinition $attr */
        $attr = $attributes[0]->newInstance();

        $resolved = new ResolvedErrorDefinition(
            code: $code,
            message: $attr->message,
            category: $attr->category,
            httpStatus: $attr->httpStatus,
            severity: $attr->severity,
            retryable: $attr->retryable,
        );

        $this->cache[$code] = $resolved;

        return $resolved;
    }
}
