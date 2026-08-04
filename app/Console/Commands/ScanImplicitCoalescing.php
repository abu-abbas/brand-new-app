<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

#[Signature('app:scan-implicit-coalescing {path=app : Path relatif dari root project}')]
#[Description('Scan heuristic untuk menemukan penggunaan operator ?? dan ?: pada layer aplikasi.')]
class ScanImplicitCoalescing extends Command
{
    /**
     * Sub-direktori yang akan di-scan secara otomatis.
     */
    protected array $targetSubdirs = [
        'Http/Controllers',
        'Services',
        'Actions',
        'Repositories',
    ];

    /**
     * Regex kata kunci mutasi data.
     */
    protected string $mutationRegex = '/(update|create|store|save|fill|delete|destroy|sync|toggle|restore|::create|->update|->save|->fill|->delete)/i';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetPath = $this->argument('path');
        $basePath = base_path($targetPath);

        if (! is_dir($basePath)) {
            $this->error("Direktori '{$basePath}' tidak ditemukan.");
            return self::FAILURE;
        }

        $this->info("== Scan Pola ?? / ?: — {$targetPath} ==");
        $this->newLine();

        $rows = [];
        $totalHits = 0;

        foreach ($this->targetSubdirs as $subdir) {
            $dirPath = rtrim($basePath, '/') . '/' . ltrim($subdir, '/');

            if (! is_dir($dirPath)) {
                continue;
            }

            $finder = new Finder();
            $finder->files()->in($dirPath)->name('*.php');

            foreach ($finder as $file) {
                $filePath = $file->getRelativePathname();
                $lines = explode("\n", $file->getContents());

                foreach ($lines as $lineNum => $lineContent) {
                    $lineNumber = $lineNum + 1;
                    $trimmedLine = trim($lineContent);

                    // Skip komentar
                    if (str_starts_with($trimmedLine, '//') || str_starts_with($trimmedLine, '*') || str_starts_with($trimmedLine, '/*')) {
                        continue;
                    }

                    // 1. [HIGH] Fallback berlapis (2+ operator ??)
                    if (substr_count($lineContent, '??') >= 2) {
                        $rows[] = [
                            '<fg=red>HIGH</>',
                            "{$subdir}/{$filePath}:{$lineNumber}",
                            mb_strimwidth($trimmedLine, 0, 80, '...'),
                        ];
                        $totalHits++;
                        continue;
                    }

                    // Cek ketersediaan operator ?? atau ?:
                    $hasCoalescing = str_contains($lineContent, '??') || str_contains($lineContent, '?:');

                    if ($hasCoalescing) {
                        // 2. [MED] Operator ?? / ?: dekat kata kunci mutasi
                        if (preg_match($this->mutationRegex, $lineContent)) {
                            $rows[] = [
                                '<fg=yellow>MED</>',
                                "{$subdir}/{$filePath}:{$lineNumber}",
                                mb_strimwidth($trimmedLine, 0, 80, '...'),
                            ];
                            $totalHits++;
                        } else {
                            // 3. [LOW] Penggunaan ?? / ?: lainnya
                            $rows[] = [
                                '<fg=gray>LOW</>',
                                "{$subdir}/{$filePath}:{$lineNumber}",
                                mb_strimwidth($trimmedLine, 0, 80, '...'),
                            ];
                            $totalHits++;
                        }
                    }
                }
            }
        }

        if (empty($rows)) {
            $this->info(' (bersih — tidak ditemukan pola mencurigakan)');
            return self::SUCCESS;
        }

        // Tampilkan hasil scan dalam tabel terminal
        $this->table(['Risk', 'File & Line', 'Snippet'], $rows);

        $this->newLine();
        $this->comment("Total temuan: {$totalHits}");
        $this->comment('Catatan: Hasil ini bersifat heuristik. Lakukan review manual pada level HIGH & MED.');

        return self::SUCCESS;
    }
}
