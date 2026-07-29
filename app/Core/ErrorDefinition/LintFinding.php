<?php

namespace App\Core\ErrorDefinition;

/**
 * Satu temuan dari ErrorDefinitionLinter.
 */
final readonly class LintFinding
{
    /**
     * @param  string  $ruleId  Stable rule ID (contoh: 'ED-001')
     * @param  'error'|'warning'  $level
     * @param  string  $source  Class, enum case, atau mapping key
     * @param  string  $message  Penjelasan pelanggaran
     */
    public function __construct(
        public string $ruleId,
        public string $level,
        public string $source,
        public string $message,
    ) {}
}
