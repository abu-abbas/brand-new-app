<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Demo shadcn-vue & Laravel</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <script>
            window.__APP_CONFIG__ = {{ Illuminate\Support\Js::from([
                'name' => config('app.name'),
                'theme_accent' => config('app.theme_accent'),
                'env' => config('app.env'),
                'url' => config('app.url'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'current_date' => now()->format('Y-m-d'),
                'current_fulldate' => now()->toIso8601String(),
                'captcha' => [
                    'enabled' => ! config('captcha.disable'),
                ],
                'references' => [
                    'permission_types' => App\Enums\PermissionType::options(),
                ],
            ]) }};
        </script>
        {{ Vite::fonts() }}
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
