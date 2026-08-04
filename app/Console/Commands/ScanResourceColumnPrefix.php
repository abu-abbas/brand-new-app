<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

#[Signature('app:scan-resource-column-prefix {path=app/Http/Resources : Path relatif ke folder JsonResource}')]
#[Description('Scan heuristic untuk menemukan penggunaan prefix kolom DB (v_, dt_, b_, si_, e_, i_, ti_, bi_) sebagai array key pada JsonResource.')]
class ScanResourceColumnPrefix extends Command
{
    /**
     * Regex matcher key ber-prefix.
     */
    protected string $prefixKeyRegex = "/['\"](v_|dt_|b_|si_|e_|i_|ti_|bi_)[a-zA-Z0-9_]+['\"]\s*=>/";

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

        $this->info("== Scan Kebocoran Prefix Kolom DB di API Resource — {$targetPath} ==");
        $this->newLine();

        $rows = [];
        $totalHits = 0;

        $finder = new Finder();
        $finder->files()->in($basePath)->name('*Resource.php');

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

                if (preg_match($this->prefixKeyRegex, $lineContent, $matches)) {
                    $rows[] = [
                        '<fg=red>HIGH</>',
                        "{$filePath}:{$lineNumber}",
                        mb_strimwidth($trimmedLine, 0, 80, '...'),
                    ];
                    $totalHits++;
                }
            }
        }

        if (empty($rows)) {
            $this->info(' (bersih — tidak ditemukan key ber-prefix di API Resource)');
            return self::SUCCESS;
        }

        // Tampilkan hasil scan dalam tabel terminal
        $this->table(['Risk', 'File & Line', 'Snippet'], $rows);

        $this->newLine();
        $this->comment("Total temuan: {$totalHits}");
        $this->comment('Lakukan review pada temuan di atas. Key array pada toArray() wajib di-transpose ke nama bersih.');

        return self::SUCCESS;
    }
}
