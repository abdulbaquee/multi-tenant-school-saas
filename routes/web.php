<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolSettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware(['verified', 'can:dashboard.view'])
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/school-settings', [SchoolSettingController::class, 'edit'])->name('school-settings.edit');
    Route::patch('/school-settings', [SchoolSettingController::class, 'update'])->name('school-settings.update');

    Route::patch('/schools/{school}/activate', [SchoolController::class, 'activate'])->name('schools.activate');
    Route::patch('/schools/{school}/deactivate', [SchoolController::class, 'deactivate'])->name('schools.deactivate');
    Route::resource('schools', SchoolController::class)->except(['destroy']);

    Route::patch('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::resource('users', UserController::class)->except(['destroy']);

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}/permissions', [RoleController::class, 'update'])->name('roles.permissions.update');
});

require __DIR__.'/auth.php';
