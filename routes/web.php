<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/onboarding');

Route::controller(OnboardingController::class)->group(function () {
    Route::get('/onboarding', 'create')->name('onboarding.create');
    Route::post('/onboarding', 'store')->name('onboarding.store')->middleware('audit');
});

Route::prefix('schools/{school:slug}')
    ->middleware(['tenant', 'audit'])
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::controller(StudentController::class)->prefix('students')->name('students.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store')->middleware('role:school_admin,teacher');
            Route::put('/{student}', 'update')->name('update')->middleware('role:school_admin,teacher');
            Route::delete('/{student}', 'destroy')->name('destroy')->middleware('role:school_admin');
        });

        Route::controller(AttendanceController::class)->prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store')->middleware('role:school_admin,teacher');
        });

        Route::controller(FeeController::class)->prefix('fees')->name('fees.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store')->middleware('role:school_admin,accountant');
            Route::patch('/{invoice}/paid', 'markPaid')->name('mark-paid')->middleware('role:school_admin,accountant');
        });

        Route::controller(ExamController::class)->prefix('exams')->name('exams.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store')->middleware('role:school_admin,teacher');
            Route::post('/{exam}/results', 'storeResult')->name('results.store')->middleware('role:school_admin,teacher');
        });

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });
