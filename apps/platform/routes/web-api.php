<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CreateOrganizationController;
use App\Http\Controllers\Api\V1\InviteMembershipController;
use App\Http\Controllers\Api\V1\ReactivateMembershipController;
use App\Http\Controllers\Api\V1\SuspendMembershipController;
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
        Route::post('/organizations/{organization}/memberships', InviteMembershipController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.memberships.store');
        Route::post('/organizations/{organization}/memberships/{membership}/suspend', SuspendMembershipController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.memberships.suspend');
        Route::post('/organizations/{organization}/memberships/{membership}/reactivate', ReactivateMembershipController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.memberships.reactivate');
    });
