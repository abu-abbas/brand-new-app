<?php

declare(strict_types=1);

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\Ternary;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Menegakkan rule "No Implicit Coalescing on Mutations":
 * DILARANG pakai `??` / `?:` di dalam method yang namanya nunjukin
 * operasi mutasi (create/store/update/delete/dst) pada
 * Controller/Service/Action/Repository.
 *
 * Suppress satu baris tertentu (kalau fallback itu emang business
 * requirement yang eksplisit) dengan komentar di atas statement-nya:
 *
 *   // @allow-fallback alasan: default wilayah kalau user belum pilih
 *   $wilayah = $data['wilayah'] ?? $defaultWilayah;
 *
 * CATATAN KETERBATASAN:
 * - Ini heuristik berbasis nama method + suffix nama class, bukan
 *   data-flow analysis penuh. Method yang namanya nggak match pattern
 *   (misal "processPayment" yang sebenarnya nge-update DB) nggak
 *   akan ke-flag — pertimbangkan rename method biar konsisten,
 *   atau extend MUTATION_METHOD_PATTERN di bawah.
 * - Komentar suppress dibaca dari statement yang membungkus expression,
 *   jadi taruh di baris sebelum statement (leading comment), bukan
 *   trailing comment di baris yang sama.
 *
 * @implements Rule<Node>
 */
final class NoImplicitCoalescingOnMutationsRule implements Rule
{
    private const MUTATION_METHOD_PATTERN = '/^(store|create|update|edit|patch|destroy|delete|save|sync|toggle|reset|restore)/i';

    private const TARGET_CLASS_SUFFIXES = ['Controller', 'Service', 'Action', 'Repository'];

    private const SUPPRESS_TAG = '@allow-fallback';

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->isCoalescingNode($node)) {
            return [];
        }

        if ($this->hasSuppressComment($node)) {
            return [];
        }

        $class = $scope->getClassReflection();
        if ($class === null || ! $this->isTargetClass($class->getName())) {
            return [];
        }

        $function = $scope->getFunction();
        if ($function === null || ! preg_match(self::MUTATION_METHOD_PATTERN, $function->getName())) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Dilarang pakai operator fallback implisit (?? / ?:) di method mutasi "%s()". '
                    .'Cek ketersediaan key eksplisit pakai array_key_exists()/$request->has(), '
                    .'atau biarkan gagal lewat validasi (422) kalau field ini required. '
                    .'Kalau fallback ini emang business requirement, tambahin komentar "%s <alasan>" '
                    .'di baris sebelum statement ini.',
                $function->getName(),
                self::SUPPRESS_TAG,
            ))
                ->identifier('app.noImplicitCoalescingOnMutations')
                ->build(),
        ];
    }

    private function isCoalescingNode(Node $node): bool
    {
        if ($node instanceof Coalesce) {
            return true;
        }

        // Shorthand ternary `$a ?: $b` diparse sebagai Ternary dengan `if` null.
        // Ternary lengkap `$a ? $b : $c` TIDAK termasuk rule ini secara default
        // karena bukan fallback implisit — kalau mau ikut di-cover juga,
        // hapus pengecekan `$node->if === null` di bawah.
        if ($node instanceof Ternary && $node->if === null) {
            return true;
        }

        return false;
    }

    private function isTargetClass(string $className): bool
    {
        foreach (self::TARGET_CLASS_SUFFIXES as $suffix) {
            if (str_ends_with($className, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function hasSuppressComment(Node $node): bool
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), self::SUPPRESS_TAG)) {
                return true;
            }
        }

        return false;
    }
}
