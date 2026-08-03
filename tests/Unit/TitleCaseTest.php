<?php

use Illuminate\Support\Str;

test('it resolves ambiguous doctor honorific from preference', function () {
    expect(toTitleCase('DR. BUDI'))
        ->toBe('dr. Budi')
        ->and(Str::toTitleCase('dr. budi', ['honorific_priority' => 'doktor']))
        ->toBe('Dr. Budi')
        ->and((string) str('DR. BUDI')->toTitleCase(['honorific_priority' => 'dr']))
        ->toBe('dr. Budi');
});
