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
        if (! ($node instanceof Coalesce) && ! ($node instanceof Ternary && $node->if === null)) {
            return false;
        }

        return ! $this->isAllowedFallback($node);
    }

    private function isAllowedFallback(Node $node): bool
    {
        $fallbackNode = null;
        if ($node instanceof Coalesce) {
            $fallbackNode = $node->right;
        } elseif ($node instanceof Ternary && $node->if === null) {
            $fallbackNode = $node->else;
        }

        if ($fallbackNode === null) {
            return false;
        }

        // Izinkan fallback null atau false (seperti `$data['field'] ?? null` atau `$data['field'] ?? false`)
        if ($fallbackNode instanceof Node\Expr\ConstFetch && in_array(strtolower($fallbackNode->name->toString()), ['null', 'false'], true)) {
            return true;
        }

        // Izinkan fallback string kosong (seperti `$data['field'] ?? ''`)
        if ($fallbackNode instanceof Node\Scalar\String_ && $fallbackNode->value === '') {
            return true;
        }

        // Izinkan fallback array kosong (seperti `$data['items'] ?? []`)
        if ($fallbackNode instanceof Node\Expr\Array_ && count($fallbackNode->items) === 0) {
            return true;
        }

        // Izinkan fallback integer literal (seperti `$data['order'] ?? 1` atau `$data['level'] ?? 0`)
        if ($fallbackNode instanceof Node\Scalar\Int_) {
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
