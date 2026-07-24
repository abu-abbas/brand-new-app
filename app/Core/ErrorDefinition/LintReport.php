<?php

namespace App\Core\ErrorDefinition;

/**
 * Kumpulan temuan dari ErrorDefinitionLinter.
 */
final class LintReport
{
    /** @var list<LintFinding> */
    private array $findings = [];

    public function add(LintFinding $finding): void
    {
        $this->findings[] = $finding;
    }

    /**
     * @return list<LintFinding>
     */
    public function all(): array
    {
        return $this->findings;
    }

    /**
     * @return list<LintFinding>
     */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->findings,
            fn (LintFinding $f) => $f->level === 'error'
        ));
    }

    /**
     * @return list<LintFinding>
     */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->findings,
            fn (LintFinding $f) => $f->level === 'warning'
        ));
    }

    public function hasErrors(): bool
    {
        return count($this->errors()) > 0;
    }

    public function hasWarnings(): bool
    {
        return count($this->warnings()) > 0;
    }

    public function isEmpty(): bool
    {
        return empty($this->findings);
    }
}
