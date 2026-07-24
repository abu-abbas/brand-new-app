# Konvensi Proyek

## Custom Vue components

Setiap kali membuat custom component baru (bukan wrapper tipis shadcn-vue di `components/ui/`), taruh di `resources/js/components/custom-ui/<nama-folder>/` dan lengkapi dengan:

- **Komponen itu sendiri** — `<ComponentName>.vue`.
- **Test** — `<ComponentName>.test.ts` di folder yang sama, memakai `@vue/test-utils` + Vitest (lihat komponen lain di `custom-ui/` untuk pola: `mount()`, `describe`/`it`, `wrapper.text()`/`wrapper.emitted()`).
- **Example** — `<ComponentName>Example.vue` di folder yang sama, didaftarkan otomatis oleh `resources/js/pages/ExamplePage.vue` lewat `import.meta.glob('/resources/js/components/custom-ui/**/*Example.vue')`.

**Pengecualian:** subcomponent yang murni implementation detail dari komponen lain di folder yang sama (contoh: `DataTableErrorAlert.vue` di dalam `data-table/`) tidak perlu `*Example.vue` terpisah — `ExamplePage.vue` mem-group satu nav tab per nama folder (bukan per file), jadi Example kedua di folder yang sama akan collide (hash yang sama, saling menimpa). Cukup pastikan subcomponent itu didemokan dari dalam `*Example.vue` yang sudah ada di folder itu, dan tetap wajib punya `.test.ts` sendiri.

## Generated API client (Orval + EDF)

- `npm run generate:api` (scramble export → orval) auto-formats its output via `orval.config.ts`'s `hooks.afterAllFilesWrite: 'prettier --write'`. `resources/js/api/generated/**` should always come out Prettier-clean with zero ESLint warnings — if it doesn't, fix the hook, don't hand-edit generated files.
- `php artisan error-definition:generate` (`resources/js/generated/error-codes.ts`, `storage/app/error-definition/error-catalog.json`) is deliberately **excluded** from Prettier/ESLint (`.prettierignore`, `eslint.config.js`) instead of formatted. Its exact quoted-key format is a byte-level contract from `error-definition-framework` (see `.agents/skills/build-error-definitions/references/contracts.md`); running Prettier on it would strip quotes via `quoteProps` and make `--check` report "out of date" forever. Don't add it to a format hook — if it ever shows lint warnings, the fix is to check the ignore list, not to reformat the file.
