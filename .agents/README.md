# AI-First Laravel 13 + Vue 3 — Agents Kit (Antigravity IDE)

Kit ini berisi **Rules**, **Skills**, dan **Workflows** untuk Antigravity IDE yang disesuaikan dengan konsep
`AI-First Laravel 13 + Vue 3 Enterprise Starter Kit` milik kamu.

**Semua file di sini bersifat WORKSPACE-SPECIFIC** — tidak ada satupun rule/skill/workflow global
(`~/.gemini/...`). Semua diletakkan di dalam `.agents/` pada root workspace/git repo project kamu,
sesuai dokumentasi Antigravity IDE:

- Rules workspace -> `<workspace-root>/.agents/rules/`
- Skills workspace -> `<workspace-root>/.agents/skills/<skill-folder>/SKILL.md`
- Workflows workspace -> `<workspace-root>/.agents/workflows/` (dipanggil via `/nama-workflow`)

## Cara Pasang

1. Extract isi zip ini.
2. Copy folder `.agents/` ke **root project Laravel** kamu (sejajar dengan `app/`, `resources/`, `composer.json`).
   ```bash
   cp -r .agents /path/ke/project-laravel-kamu/
   ```
3. Buka project di Antigravity IDE — Rules, Skills, dan Workflows akan otomatis terbaca dari `.agents/`
   (workspace-level), tidak perlu setting tambahan.
4. **Wajib** install skill resmi shadcn-vue (dipakai berdampingan dengan rule `05-ui-component-priority.md`
   di kit ini, supaya Agent tahu API/CLI/theming shadcn-vue yang akurat sesuai versi project kamu):
   ```bash
   npx skills add unovue/shadcn-vue
   ```

## Isi Kit

### `.agents/rules/` (11 file)

| File                                       | Aktivasi                   | Isi                                                                                                         |
| ------------------------------------------ | -------------------------- | ----------------------------------------------------------------------------------------------------------- |
| `01-bahasa-indonesia.md`                   | Always On                  | Wajib Bahasa Indonesia untuk penjelasan, istilah teknis tetap asli                                          |
| `02-architecture-layering.md`              | Always On                  | Layer Browser->Blade->Vue->Module->API Facade->Orval->Axios->Laravel                                        |
| `03-openapi-first-flow.md`                 | Always On                  | Laravel+Scramble sebagai single source of truth                                                             |
| `04-state-management.md`                   | Model Decision             | Batasan Pinia (client state) vs TanStack Query (server state)                                               |
| `05-ui-component-priority.md`              | Always On                  | shadcn-vue (layout) > Element Plus (form), wajib SweetAlert2                                                |
| `06-authentication-authorization.md`       | Always On                  | Sanctum SPA, Policy/Gate backend, `auth.can` hanya UX                                                       |
| `07-folder-convention.md`                  | Glob (`resources/js/**/*`) | Struktur folder module frontend                                                                             |
| `08-generated-files-protection.md`         | Always On                  | Larangan edit file hasil generate (Orval/OpenAPI)                                                           |
| `09-testing-strategy.md`                   | Model Decision             | Kapan pakai PHPStan/Pest/Vitest/Playwright                                                                  |
| `10-cicd-pipeline.md`                      | Model Decision             | Referensi gate CI/CD: push, MR, release                                                                     |
| `11-error-handling-and-request-tracing.md` | Always On                  | Error Definition Framework per module, response contract, request ID UUID v4, dan structured logging        |

### `.agents/skills/` (14 skill)

`build-frontend-ui`, `build-admin-page`, `build-datatable`, `build-form`, `build-api`, `build-rbac`,
`build-queue-job`, `generate-openapi`, `sync-orval`, `code-review`, `debug-application`, `write-tests`,
`build-error-handling-tracing`, dan `build-error-definitions`.

### `.agents/workflows/` (6 workflow)

| Workflow                               | Command                             | Catatan                                                                                                                                         |
| -------------------------------------- | ----------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| Create Feature                         | `/create-feature`                   | Alur lengkap: Backend->OpenAPI->Orval->Frontend->Testing->Summary. **Melewati setup Laravel awal** karena scaffold sudah kamu generate.         |
| Build CRUD Module                      | `/build-crud-module`                | Versi ringkas untuk master data sederhana                                                                                                       |
| Add RBAC Permission                    | `/add-rbac-permission`              | Menambah role/permission ke fitur yang sudah ada                                                                                                |
| Code Review PR                         | `/code-review-pr`                   | Review terstruktur sebelum merge                                                                                                                |
| Debug Issue                            | `/debug-issue`                      | Alur diagnosis bug, kini ikut memanfaatkan `trace_id`/`request_id` untuk grep log                                                               |
| Setup Error Handling & Request Tracing | `/setup-error-handling-and-tracing` | Workflow sekali jalan: pasang middleware request ID, exception handler sentral, domain exception, propagasi ke Queue Job, dan interceptor Axios |

## Catatan Penyesuaian dari Konsep Awal

- Sesuai instruksi kamu: **workflow inisialisasi project Laravel di-skip**, karena scaffold Laravel 13 sudah ada.
- Stack backend difokuskan ke: Laravel 13, Scramble, Sanctum (tanpa Queue/Horizon/PHPStan/Pest disebutkan
  eksplisit sebagai stack wajib — namun tetap direferensikan di rule testing/CI-CD & skill `build-queue-job`
  sebagai bagian dari konsep enterprise, silakan hapus jika tidak dipakai).
- Stack frontend: Vue 3, TypeScript, Vue Router, Pinia, Axios, TanStack Query, Orval, SweetAlert2 (wajib,
  no native alert), shadcn-vue (first choice layout), Element Plus (first choice form).
