<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CreateOrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->as('api.v1.')
    ->middleware(['auth', 'throttle:6,1'])
    ->group(function (): void {
        Route::post('/organizations', CreateOrganizationController::class)->name('organizations.store');
    });
