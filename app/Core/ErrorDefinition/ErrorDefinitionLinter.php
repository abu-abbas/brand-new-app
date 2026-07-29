<?php

namespace App\Core\ErrorDefinition;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use BackedEnum;
use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;
use ReflectionEnum;
use ReflectionEnumUnitCase;
use Throwable;

/**
 * Memeriksa enum error dan FormRequest yang diberikan kepadanya.
 *
 * Linter TIDAK memindai folder, mencari class, atau menentukan struktur project.
 * Input berupa iterable enum class dan FormRequest class dari Discovery.
 *
 * Linter dijalankan secara eksplisit melalui test, command, atau CI.
 * Pemeriksaan tidak berjalan ketika aplikasi boot dan bukan dependency runtime.
 *
 * Rules:
 * - ED-001: Enum harus mengimplementasikan ErrorCode dan BackedEnum
 * - ED-002: Enum harus menggunakan string backing
 * - ED-003: Setiap case harus memiliki tepat satu #[ErrorDefinition]
 * - ED-004: Error code harus sesuai format regex
 * - ED-005: Message tidak boleh kosong
 * - ED-006: HTTP status harus antara 400-599
 * - ED-007: Error code harus unik di seluruh target
 * - VM-001: Validation mapping rule tidak ditemukan di errorCodes()
 * - VM-002: Validation definition harus category VALIDATION, status 422, retryable false
 * - VM-003: FormRequest opt-in tidak boleh meng-override messages()
 */
final class ErrorDefinitionLinter
{
    private const ERROR_CODE_PATTERN = '/^[A-Z0-9]+(?:-[A-Z0-9]+)+-\d{3,}$/';

    /**
     * @param  list<class-string>  $errorEnums
     * @param  list<class-string>  $formRequests
     */
    public function lint(array $errorEnums, array $formRequests): LintReport
    {
        $report = new LintReport;
        $allCodes = [];

        foreach ($errorEnums as $enumClass) {
            $this->lintEnum($enumClass, $report, $allCodes);
        }

        // Cek duplikat error code lintas enum
        $this->checkDuplicateCodes($allCodes, $report);

        foreach ($formRequests as $requestClass) {
            $this->lintFormRequest($requestClass, $report);
        }

        return $report;
    }

    /**
     * @param  array<string, list<string>>  &$allCodes  error_code => list of enum sources
     */
    private function lintEnum(string $enumClass, LintReport $report, array &$allCodes): void
    {
        // ED-001: Harus enum yang implements ErrorCode
        try {
            $ref = new ReflectionClass($enumClass);
        } catch (Throwable) {
            $report->add(new LintFinding(
                ruleId: 'ED-001',
                level: 'error',
                source: $enumClass,
                message: "Class [{$enumClass}] tidak dapat di-reflect.",
            ));

            return;
        }

        if (! $ref->isEnum()) {
            $report->add(new LintFinding(
                ruleId: 'ED-001',
                level: 'error',
                source: $enumClass,
                message: "[{$enumClass}] bukan enum.",
            ));

            return;
        }

        if (! $ref->implementsInterface(ErrorCode::class)) {
            $report->add(new LintFinding(
                ruleId: 'ED-001',
                level: 'error',
                source: $enumClass,
                message: "[{$enumClass}] tidak mengimplementasikan ErrorCode.",
            ));

            return;
        }

        // ED-002: Harus string backed
        if (! is_subclass_of($enumClass, BackedEnum::class)) {
            $report->add(new LintFinding(
                ruleId: 'ED-002',
                level: 'error',
                source: $enumClass,
                message: "[{$enumClass}] bukan BackedEnum.",
            ));

            return;
        }

        // Periksa backing type
        $backingType = (new ReflectionEnum($enumClass))->getBackingType();
        if ($backingType === null || $backingType->getName() !== 'string') {
            $report->add(new LintFinding(
                ruleId: 'ED-002',
                level: 'error',
                source: $enumClass,
                message: "[{$enumClass}] tidak menggunakan string backing type.",
            ));

            return;
        }

        foreach ($enumClass::cases() as $case) {
            /** @var ErrorCode&BackedEnum $case */
            $caseSource = "{$enumClass}::{$case->name}";
            $caseRef = new ReflectionEnumUnitCase($enumClass, $case->name);
            $attributes = $caseRef->getAttributes(ErrorDefinition::class);

            // ED-003: Tepat satu attribute
            if (count($attributes) === 0) {
                $report->add(new LintFinding(
                    ruleId: 'ED-003',
                    level: 'error',
                    source: $caseSource,
                    message: "Case [{$case->name}] tidak memiliki attribute #[ErrorDefinition].",
                ));

                continue;
            }

            if (count($attributes) > 1) {
                $report->add(new LintFinding(
                    ruleId: 'ED-003',
                    level: 'error',
                    source: $caseSource,
                    message: "Case [{$case->name}] memiliki lebih dari satu attribute #[ErrorDefinition].",
                ));

                continue;
            }

            /** @var ErrorDefinition $attr */
            $attr = $attributes[0]->newInstance();
            $code = (string) $case->value;

            // Track untuk duplicate check
            $allCodes[$code][] = $caseSource;

            // ED-004: Format error code
            if (! preg_match(self::ERROR_CODE_PATTERN, $code)) {
                $report->add(new LintFinding(
                    ruleId: 'ED-004',
                    level: 'error',
                    source: $caseSource,
                    message: "Error code [{$code}] tidak sesuai format. Expected: ^[A-Z0-9]+(?:-[A-Z0-9]+)+-\\d{3,}$",
                ));
            }

            // ED-005: Message non-empty
            if (trim($attr->message) === '') {
                $report->add(new LintFinding(
                    ruleId: 'ED-005',
                    level: 'error',
                    source: $caseSource,
                    message: "Message pada [{$case->name}] kosong.",
                ));
            }

            // ED-006: HTTP status 400-599
            if ($attr->httpStatus < 400 || $attr->httpStatus > 599) {
                $report->add(new LintFinding(
                    ruleId: 'ED-006',
                    level: 'error',
                    source: $caseSource,
                    message: "HTTP status [{$attr->httpStatus}] di luar rentang 400-599.",
                ));
            }
        }
    }

    /**
     * @param  array<string, list<string>>  $allCodes
     */
    private function checkDuplicateCodes(array $allCodes, LintReport $report): void
    {
        foreach ($allCodes as $code => $sources) {
            if (count($sources) > 1) {
                $sourceList = implode(', ', $sources);
                $report->add(new LintFinding(
                    ruleId: 'ED-007',
                    level: 'error',
                    source: $code,
                    message: "Error code [{$code}] digunakan oleh beberapa case: {$sourceList}.",
                ));
            }
        }
    }

    private function lintFormRequest(string $requestClass, LintReport $report): void
    {
        try {
            $ref = new ReflectionClass($requestClass);
        } catch (Throwable) {
            $report->add(new LintFinding(
                ruleId: 'VM-001',
                level: 'error',
                source: $requestClass,
                message: "FormRequest [{$requestClass}] tidak dapat di-reflect.",
            ));

            return;
        }

        if ($ref->isAbstract() || ! $ref->isSubclassOf(FormRequest::class)) {
            return;
        }

        $uses = class_uses_recursive($requestClass);
        if (! in_array(HasErrorDefinitions::class, $uses, true)) {
            return;
        }

        // VM-003: FormRequest opt-in tidak boleh meng-override messages()
        $messagesMethod = $ref->getMethod('messages');
        if ($messagesMethod->getDeclaringClass()->getName() !== FormRequest::class) {
            $report->add(new LintFinding(
                ruleId: 'VM-003',
                level: 'error',
                source: $requestClass,
                message: "FormRequest [{$requestClass}] tidak boleh meng-override messages() saat menggunakan HasErrorDefinitions.",
            ));
        }

        // Instansiasi langsung agar FormRequest tidak auto-validasi saat di-resolve container.
        try {
            /** @var FormRequest&HasErrorDefinitions $instance */
            $instance = $ref->newInstance();
            $rules = $instance->rules();
            $errorCodes = $instance->errorCodes();
        } catch (Throwable $e) {
            $report->add(new LintFinding(
                ruleId: 'VM-001',
                level: 'warning',
                source: $requestClass,
                message: "FormRequest [{$requestClass}] tidak dapat diinstansiasi untuk validasi mapping: {$e->getMessage()}",
            ));

            return;
        }

        $reader = app(ErrorDefinitionReader::class);

        // Cek setiap rule memiliki mapping
        foreach ($rules as $field => $fieldRules) {
            $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);

            foreach ($ruleList as $rule) {
                if ($rule === 'nullable' || $rule === 'sometimes' || $rule === 'bail') {
                    continue;
                }

                $ruleName = $this->extractRuleName($rule);
                $lookupKey = $field.'.'.strtolower($ruleName);
                $wildcardField = preg_replace('/\d+/', '*', $field);
                $wildcardKey = $wildcardField.'.'.strtolower($ruleName);

                if (! isset($errorCodes[$lookupKey]) && ! isset($errorCodes[$wildcardKey])) {
                    $report->add(new LintFinding(
                        ruleId: 'VM-001',
                        level: 'error',
                        source: "{$requestClass}::{$lookupKey}",
                        message: "Rule [{$lookupKey}] tidak memiliki mapping di errorCodes().",
                    ));

                    continue;
                }

                // VM-002: Validasi definition harus VALIDATION, 422, retryable: false
                $enumCase = $errorCodes[$lookupKey] ?? $errorCodes[$wildcardKey];
                try {
                    $resolved = $reader->read($enumCase);

                    if ($resolved->category !== ErrorCategory::VALIDATION) {
                        $report->add(new LintFinding(
                            ruleId: 'VM-002',
                            level: 'error',
                            source: "{$requestClass}::{$lookupKey}",
                            message: "Validation error [{$resolved->code}] harus category VALIDATION, got [{$resolved->category->value}].",
                        ));
                    }

                    if ($resolved->httpStatus !== 422) {
                        $report->add(new LintFinding(
                            ruleId: 'VM-002',
                            level: 'error',
                            source: "{$requestClass}::{$lookupKey}",
                            message: "Validation error [{$resolved->code}] harus HTTP status 422, got [{$resolved->httpStatus}].",
                        ));
                    }

                    if ($resolved->retryable !== false) {
                        $report->add(new LintFinding(
                            ruleId: 'VM-002',
                            level: 'error',
                            source: "{$requestClass}::{$lookupKey}",
                            message: "Validation error [{$resolved->code}] harus retryable: false.",
                        ));
                    }
                } catch (Throwable $e) {
                    $report->add(new LintFinding(
                        ruleId: 'VM-002',
                        level: 'error',
                        source: "{$requestClass}::{$lookupKey}",
                        message: "Gagal membaca definition: {$e->getMessage()}",
                    ));
                }
            }
        }
    }

    private function extractRuleName(mixed $rule): string
    {
        if (is_string($rule)) {
            // Handle "in:asc,desc" → "in", "max:100" → "max"
            $parts = explode(':', $rule, 2);

            return $parts[0];
        }

        if (is_object($rule)) {
            // Custom Rule object → FQCN
            return get_class($rule);
        }

        return (string) $rule;
    }
}
