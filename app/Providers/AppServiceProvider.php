<?php

namespace App\Providers;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Models\User;
use App\Support\Scramble\PaginationSchemaExtension;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ErrorDefinitionReader::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->isRoot()) {
                return true;
            }

            return in_array($ability, $user->getPermissionsList(), true) ? true : null;
        });

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            (new PaginationSchemaExtension)->handle($openApi);
        });
    }
}
