<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CreateOrganizationController;
use App\Http\Controllers\Api\V1\UpdateOrganizationController;
use App\Http\Middleware\ResolveTenantContext;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->as('api.v1.')
    ->middleware(['auth', 'throttle:6,1'])
    ->group(function (): void {
        Route::post('/organizations', CreateOrganizationController::class)->name('organizations.store');
        Route::patch('/organizations/{organization}', UpdateOrganizationController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.update');
    });
