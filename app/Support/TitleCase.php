<?php

namespace App\Support;

class TitleCase
{
    public static function make(string $text, array $options = []): string
    {
        $config = array_merge(config('titlecase', []), $options);

        $keepLower = self::buildLookup($config['keep_lower_case'] ?? []);
        $keepUpper = self::buildLookup($config['keep_upper_case'] ?? []);
        $keepTitleDot = self::buildLookup($config['keep_title_with_dot'] ?? []);
        $keepUpperDot = self::buildLookup($config['keep_upper_with_dot'] ?? []);
        $honorifics = self::buildLookup($config['honorifics'] ?? []);

        $honorifics['dr.'] = match ($config['honorific_priority'] ?? null) {
            'dr' => 'dr.',
            'doktor' => 'Dr.',
            default => $honorifics['dr.'] ?? 'dr.',
        };

        $parts = preg_split('/(\s+)/u', trim($text), -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $text;
        }

        $wordIndices = [];

        foreach ($parts as $i => $part) {
            if (trim($part) !== '') {
                $wordIndices[] = $i;
            }
        }

        if ($wordIndices === []) {
            return $text;
        }

        $firstWordIndex = reset($wordIndices);
        $lastWordIndex = end($wordIndices);

        foreach ($parts as $i => &$part) {
            if (trim($part) === '') {
                continue;
            }

            $part = self::transformWord(
                $part,
                $i === $firstWordIndex,
                $i === $lastWordIndex,
                $keepLower,
                $keepUpper,
                $keepTitleDot,
                $keepUpperDot,
                $honorifics,
                $config['regex_upper_case'] ?? [],
            );
        }

        unset($part);

        return implode('', $parts);
    }

    protected static function transformWord(
        string $word,
        bool $isFirst,
        bool $isLast,
        array $keepLower,
        array $keepUpper,
        array $keepTitleDot,
        array $keepUpperDot,
        array $honorifics,
        array $regexUpper,
    ): string {
        $pattern = '/^([\(\["\'\x{201C}\x{2018}]*)(.*?)([\)\]"\'\x{201D}\x{2019},;:]*)$/u';

        if (! preg_match($pattern, $word, $matches)) {
            return $word;
        }

        [, $lead, $core, $trail] = $matches;

        if ($core === '') {
            return $word;
        }

        $lowerCore = mb_strtolower($core);

        if (isset($honorifics[$lowerCore])) {
            $core = $honorifics[$lowerCore];
        } elseif (isset($keepUpperDot[$lowerCore])) {
            $core = $keepUpperDot[$lowerCore];
        } elseif (isset($keepTitleDot[$lowerCore])) {
            $core = $keepTitleDot[$lowerCore];
        } elseif (isset($keepUpper[$lowerCore])) {
            $core = $keepUpper[$lowerCore];
        } elseif (self::matchesRegexUpper($core, $regexUpper)) {
            $core = mb_strtoupper($core);
        } elseif (isset($keepLower[$lowerCore]) && ! $isFirst && ! $isLast) {
            $core = $keepLower[$lowerCore];
        } else {
            $core = mb_strtoupper(mb_substr($core, 0, 1)).mb_strtolower(mb_substr($core, 1));
        }

        return $lead.$core.$trail;
    }

    protected static function matchesRegexUpper(string $core, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (@preg_match($pattern, $core) === 1) {
                return true;
            }
        }

        return false;
    }

    protected static function buildLookup(array $list): array
    {
        $lookup = [];

        foreach ($list as $item) {
            $lookup[mb_strtolower($item)] = $item;
        }

        return $lookup;
    }
}
