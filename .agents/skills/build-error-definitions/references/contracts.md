# Error Definition Framework Contract

Sumber desain: [`abu-abbas/error-definition-framework`](https://github.com/abu-abbas/error-definition-framework), branch `main`, ditinjau pada commit `46ce428091bc143b7896082866b5f544e0154d8e`. Repository acuan berisi specification/decision docs; implementasi project harus mengikuti kontrak ini tanpa mengasumsikan package Composer tersedia.

## Core types

```php
interface ErrorCode {}

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
```

`ResolvedErrorDefinition` membawa `code`, `message`, `category`, `httpStatus`, `severity`, dan `retryable`. `ErrorDefinitionReader::read(ErrorCode&BackedEnum)` membaca attribute, mengembalikan DTO tersebut, melempar configuration exception bila attribute hilang, dan boleh memakai in-memory cache selama lifecycle reader.

## Category dan severity

Category baseline: `VALIDATION`, `AUTHENTICATION`, `AUTHORIZATION`, `NOT_FOUND`, `BUSINESS_RULE`, `WORKFLOW`, `INTEGRATION`, `SYSTEM`.

Severity: `LOW`, `MEDIUM`, `HIGH`, `CRITICAL`. Mapping log level: low→info, medium→warning, high→error, critical→critical.

Validation definition wajib category `VALIDATION`, HTTP `422`, dan `retryable: false`.

## Enum per context

```php
enum SuratMasukError: string implements ErrorCode
{
    #[ErrorDefinition(
        message: 'Nomor surat wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case NOMOR_SURAT_REQUIRED = 'SM-VAL-001';

    #[ErrorDefinition(
        message: 'Surat sudah disetujui dan tidak dapat diubah.',
        category: ErrorCategory::WORKFLOW,
        httpStatus: 409,
        severity: ErrorSeverity::MEDIUM,
    )]
    case DRAFT_LOCKED = 'SM-WF-001';
}
```

Satu enum mewakili satu vocabulary/aturan/lifecycle bisnis. Ownership mengikuti sumber aturan, bukan UI. Error code bersifat permanen; message boleh diperbaiki tanpa mengganti identitas.

## Exception dan response

`ApplicationException` membawa resolved definition, context internal, dan optional previous exception. Context tidak pernah masuk response.

Application error:

```json
{
  "message": "Draft sedang dikunci.",
  "code": "SM-WF-001",
  "retryable": false
}
```

HTTP status berasal dari definition. Jangan kirim category, severity, context, exception class, file, previous exception, atau stack trace, termasuk pada debug mode.

Validation error selalu HTTP `422`:

```json
{
  "message": "Nomor surat wajib diisi.",
  "errors": {
    "nomor_surat": [
      {
        "code": "SM-VAL-001",
        "message": "Nomor surat wajib diisi.",
        "retryable": false
      }
    ]
  }
}
```

Tidak ada top-level `code` pada validation response karena satu request dapat memiliki beberapa definition. Key memakai attribute aktual; wildcard hanya untuk pencarian mapping. Renderer sentral hanya menangani `ApplicationException` dan `ErrorValidationException` untuk request JSON.

## FormRequest validation

```php
final class StoreSuratMasukRequest extends FormRequest
{
    use HasErrorDefinitions;

    public function rules(): array
    {
        return ['nomor_surat' => ['required']];
    }

    public function errorCodes(): array
    {
        return [
            'nomor_surat.required' => SuratMasukError::NOMOR_SURAT_REQUIRED,
        ];
    }
}
```

Petakan setiap rule yang dapat gagal. Nested field menggunakan dot notation/wildcard. Custom Rule memakai nama class rule sebagai suffix. Gunakan helper `addValidationError()` untuk failure tambahan di `after()`. Jangan override `messages()`.

## Logging

Structured log minimal memuat `error_code`, `category`, `severity`, `retryable`, `http_status`, sanitized `context`, dan exception. Validation exception tidak dilaporkan sebagai application failure. Hindari duplicate reporting.

Context hanya berisi scalar identifier minimum. Baseline sensitive keys meliputi password, token, authorization, cookie, secret, api key, client secret, dan private key; tambahan domain diatur melalui `logging.additional_sensitive_keys`. Sanitasi nested array secara recursive.

## Lint dan generate

- Enum wajib string backed, mengimplementasikan `ErrorCode`, mempunyai tepat satu definition per case, message non-empty, status `400–599`, code unik dan sesuai regex.
- FormRequest opt-in wajib mempunyai mapping valid untuk setiap rule; mapping validation wajib category/status/retryable yang tepat.
- Jalankan `php artisan error-definition:lint` dan gunakan `--strict` bila warning harus menggagalkan CI.
- Jalankan `php artisan error-definition:generate` untuk `resources/js/generated/error-codes.ts` dan `storage/app/error-definition/error-catalog.json`.
- Generated content diurutkan, deterministic, tanpa timestamp. Jangan edit manual. Gunakan `--check` hanya bila artifact sengaja disimpan dalam Git.
