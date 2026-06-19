<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::patch('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::resource('users', UserController::class)->except(['destroy']);
});

require __DIR__.'/auth.php';
