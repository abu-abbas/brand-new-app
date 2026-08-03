<?php

namespace App\Providers;

use App\Support\TitleCase;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class TitleCaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/titlecase.php', 'titlecase');
    }

    public function boot(): void
    {
        Str::macro('toTitleCase', fn (string $value, array $options = []): string => TitleCase::make($value, $options));

        Stringable::macro('toTitleCase', function (array $options = []): Stringable {
            return new Stringable(TitleCase::make((string) $this, $options));
        });
    }
}
