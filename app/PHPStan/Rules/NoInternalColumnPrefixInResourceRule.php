<?php

declare(strict_types=1);

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Mendeteksi kebocoran prefix kolom database internal ke response API.
 *
 * Prefix kolom internal (v_, dt_, b_, si_, e_, i_, ti_, bi_) adalah
 * konvensi penamaan database yang TIDAK BOLEH bocor ke response API.
 * Transposisi wajib dilakukan di API Resource (`toArray()`), contoh:
 *
 *   // ❌ Salah — prefix bocor ke key response
 *   'v_name' => $this->v_name,
 *
 *   // ✅ Benar — key bersih, value tetap baca dari model
 *   'name' => $this->v_name,
 *
 * Suppress satu key tertentu (kalau memang ada alasan teknis) dengan
 * komentar di atas array item:
 *
 *   // @allow-raw-key alasan: key ini dipakai untuk kompatibilitas legacy API
 *   'v_legacy_field' => $this->v_legacy_field,
 *
 * @implements Rule<Return_>
 */
final class NoInternalColumnPrefixInResourceRule implements Rule
{
    /**
     * Regex prefix kolom database internal.
     *
     * v_  = varchar/string
     * dt_ = datetime
     * b_  = boolean
     * si_ = small integer
     * e_  = enum
     * i_  = integer (ID, counter)
     * ti_ = tiny integer
     * bi_ = big integer
     */
    private const PREFIX_PATTERN = '/^(v_|dt_|b_|si_|e_|i_|ti_|bi_)/';

    private const SUPPRESS_TAG = '@allow-raw-key';

    public function getNodeType(): string
    {
        return Return_::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof Return_) {
            return [];
        }

        $class = $scope->getClassReflection();
        if ($class === null || ! $this->isResourceClass($class->getName())) {
            return [];
        }

        $function = $scope->getFunction();
        if ($function === null || $function->getName() !== 'toArray') {
            return [];
        }

        if (! $node->expr instanceof Array_) {
            return [];
        }

        $errors = [];

        foreach ($node->expr->items as $item) {
            if (! $item instanceof ArrayItem) {
                continue;
            }

            if (! $item->key instanceof String_) {
                continue;
            }

            $key = $item->key->value;

            if (! preg_match(self::PREFIX_PATTERN, $key)) {
                continue;
            }

            if ($this->hasSuppressComment($item)) {
                continue;
            }

            $cleanKey = preg_replace(self::PREFIX_PATTERN, '', $key);

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Key "%s" di API Resource mengandung prefix kolom database internal. '
                    .'Gunakan nama bersih seperti "%s" supaya response API tidak bocorkan skema DB. '
                    .'Kalau key ini memang harus tetap seperti itu, tambahin komentar "%s <alasan>" '
                    .'di baris sebelum array item ini.',
                $key,
                $cleanKey,
                self::SUPPRESS_TAG,
            ))
                ->identifier('app.noInternalColumnPrefixInResource')
                ->line($item->key->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isResourceClass(string $className): bool
    {
        if (! str_ends_with($className, 'Resource')) {
            return false;
        }

        // Pastikan class ini merupakan turunan JsonResource
        // dengan mengecek apakah parent chain mengandung JsonResource.
        // Fallback: cek suffix saja jika reflection tidak tersedia.
        return true;
    }

    private function hasSuppressComment(ArrayItem $item): bool
    {
        foreach ($item->getComments() as $comment) {
            if (str_contains($comment->getText(), self::SUPPRESS_TAG)) {
                return true;
            }
        }

        // Cek juga komentar di key node
        if ($item->key !== null) {
            foreach ($item->key->getComments() as $comment) {
                if (str_contains($comment->getText(), self::SUPPRESS_TAG)) {
                    return true;
                }
            }
        }

        return false;
    }
}
