<?php

use App\Support\TitleCase;

if (! function_exists('toTitleCase')) {
    function toTitleCase(string $value, array $options = []): string
    {
        return TitleCase::make($value, $options);
    }
}
