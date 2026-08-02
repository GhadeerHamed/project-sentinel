<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\Identifier\LaravelUuidV7Generator;
use Illuminate\Support\ServiceProvider;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            UuidV7Generator::class,
            LaravelUuidV7Generator::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
