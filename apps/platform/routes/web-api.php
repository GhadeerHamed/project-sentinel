<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AcceptMembershipInvitationController;
use App\Http\Controllers\Api\V1\ArchiveApplicationController;
use App\Http\Controllers\Api\V1\ArchiveProjectController;
use App\Http\Controllers\Api\V1\AssignRoleController;
use App\Http\Controllers\Api\V1\CreateApplicationController;
use App\Http\Controllers\Api\V1\CreateOrganizationController;
use App\Http\Controllers\Api\V1\CreateProjectController;
use App\Http\Controllers\Api\V1\GetApplicationController;
use App\Http\Controllers\Api\V1\GetProjectController;
use App\Http\Controllers\Api\V1\InviteMembershipController;
use App\Http\Controllers\Api\V1\ListAccessibleOrganizationsController;
use App\Http\Controllers\Api\V1\ListApplicationsController;
use App\Http\Controllers\Api\V1\ListProjectsController;
use App\Http\Controllers\Api\V1\ReactivateMembershipController;
use App\Http\Controllers\Api\V1\RemoveMembershipController;
use App\Http\Controllers\Api\V1\RevokeRoleController;
use App\Http\Controllers\Api\V1\SuspendMembershipController;
use App\Http\Controllers\Api\V1\SuspendOrganizationController;
use App\Http\Controllers\Api\V1\UpdateOrganizationController;
use App\Http\Controllers\Api\V1\UpdateRolePermissionsController;
use App\Http\Middleware\ResolveTenantContext;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->as('api.v1.')
    ->middleware(['auth', 'throttle:6,1'])
    ->group(function (): void {
        Route::post('/organizations', CreateOrganizationController::class)->name('organizations.store');
        Route::get('/organizations', ListAccessibleOrganizationsController::class)->name('organizations.index');
        Route::post('/organizations/{organization}/memberships/{membership}/accept', AcceptMembershipInvitationController::class)->name('organizations.memberships.accept');
        Route::patch('/organizations/{organization}', UpdateOrganizationController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.update');
        Route::post('/organizations/{organization}/suspend', SuspendOrganizationController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.suspend');
        Route::post('/organizations/{organization}/projects', CreateProjectController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.projects.store');
        Route::post('/organizations/{organization}/projects/{project}/applications', CreateApplicationController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.projects.applications.store');
        Route::get('/organizations/{organization}/projects/{project}/applications', ListApplicationsController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.projects.applications.index');
        Route::get('/organizations/{organization}/projects/{project}/applications/{application}', GetApplicationController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.projects.applications.show');
        Route::post('/organizations/{organization}/projects/{project}/applications/{application}/archive', ArchiveApplicationController::class)->middleware(ResolveTenantContext::class)->name('organizations.projects.applications.archive');
        Route::get('/organizations/{organization}/projects', ListProjectsController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.projects.index');
        Route::get('/organizations/{organization}/projects/{project}', GetProjectController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.projects.show');
        Route::post('/organizations/{organization}/projects/{project}/archive', ArchiveProjectController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.projects.archive');
        Route::post('/organizations/{organization}/memberships', InviteMembershipController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.memberships.store');
        Route::post('/organizations/{organization}/memberships/{membership}/suspend', SuspendMembershipController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.memberships.suspend');
        Route::post('/organizations/{organization}/memberships/{membership}/reactivate', ReactivateMembershipController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.memberships.reactivate');
        Route::post('/organizations/{organization}/memberships/{membership}/remove', RemoveMembershipController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.memberships.remove');
        Route::post('/organizations/{organization}/memberships/{membership}/roles', AssignRoleController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.memberships.roles.store');
        Route::post('/organizations/{organization}/role-assignments/{roleAssignment}/revoke', RevokeRoleController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.role-assignments.revoke');
        Route::patch('/organizations/{organization}/roles/{role}/permissions', UpdateRolePermissionsController::class)
            ->middleware(ResolveTenantContext::class)
            ->name('organizations.roles.permissions.update');
    });
