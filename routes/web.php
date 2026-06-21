<?php

use App\Http\Controllers\AcademicTermController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolSettingController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPhotoController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
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

    Route::patch('/academic-years/{academic_year}/activate', [AcademicYearController::class, 'activate'])
        ->name('academic-years.activate');
    Route::patch('/academic-years/{academic_year}/deactivate', [AcademicYearController::class, 'deactivate'])
        ->name('academic-years.deactivate');
    Route::patch('/academic-years/{academic_year}/reactivate', [AcademicYearController::class, 'reactivate'])
        ->name('academic-years.reactivate');
    Route::resource('academic-years', AcademicYearController::class)->except(['destroy']);

    Route::patch('/academic-terms/{academic_term}/deactivate', [AcademicTermController::class, 'deactivate'])
        ->name('academic-terms.deactivate');
    Route::patch('/academic-terms/{academic_term}/reactivate', [AcademicTermController::class, 'reactivate'])
        ->name('academic-terms.reactivate');
    Route::resource('academic-terms', AcademicTermController::class)->except(['destroy']);

    Route::patch('/teacher-profiles/{teacher_profile}/activate', [TeacherController::class, 'activate'])
        ->name('teacher-profiles.activate');
    Route::patch('/teacher-profiles/{teacher_profile}/deactivate', [TeacherController::class, 'deactivate'])
        ->name('teacher-profiles.deactivate');
    Route::patch('/teacher-profiles/{teacher_profile}/archive', [TeacherController::class, 'archive'])
        ->name('teacher-profiles.archive');
    Route::patch('/teacher-profiles/{teacher_profile}/restore', [TeacherController::class, 'restore'])
        ->withTrashed()
        ->name('teacher-profiles.restore');
    Route::resource('teacher-profiles', TeacherController::class)
        ->parameters(['teacher-profiles' => 'teacher_profile'])
        ->withTrashed(['show'])
        ->except(['destroy']);

    Route::patch('/classes/{school_class}/activate', [SchoolClassController::class, 'activate'])->name('classes.activate');
    Route::patch('/classes/{school_class}/deactivate', [SchoolClassController::class, 'deactivate'])->name('classes.deactivate');
    Route::patch('/classes/{school_class}/archive', [SchoolClassController::class, 'archive'])->name('classes.archive');
    Route::patch('/classes/{school_class}/restore', [SchoolClassController::class, 'restore'])
        ->withTrashed()
        ->name('classes.restore');
    Route::resource('classes', SchoolClassController::class)
        ->parameters(['classes' => 'school_class'])
        ->withTrashed(['show'])
        ->except(['destroy']);

    Route::patch('/sections/{section}/activate', [SectionController::class, 'activate'])->name('sections.activate');
    Route::patch('/sections/{section}/deactivate', [SectionController::class, 'deactivate'])->name('sections.deactivate');
    Route::patch('/sections/{section}/archive', [SectionController::class, 'archive'])->name('sections.archive');
    Route::patch('/sections/{section}/restore', [SectionController::class, 'restore'])
        ->withTrashed()
        ->name('sections.restore');
    Route::resource('sections', SectionController::class)
        ->withTrashed(['show'])
        ->except(['destroy']);

    Route::patch('/subjects/{subject}/activate', [SubjectController::class, 'activate'])->name('subjects.activate');
    Route::patch('/subjects/{subject}/deactivate', [SubjectController::class, 'deactivate'])->name('subjects.deactivate');
    Route::patch('/subjects/{subject}/archive', [SubjectController::class, 'archive'])->name('subjects.archive');
    Route::patch('/subjects/{subject}/restore', [SubjectController::class, 'restore'])
        ->withTrashed()
        ->name('subjects.restore');
    Route::resource('subjects', SubjectController::class)
        ->withTrashed(['show'])
        ->except(['destroy']);

    Route::patch('/students/{student}/activate', [StudentController::class, 'activate'])->name('students.activate');
    Route::patch('/students/{student}/deactivate', [StudentController::class, 'deactivate'])->name('students.deactivate');
    Route::patch('/students/{student}/archive', [StudentController::class, 'archive'])->name('students.archive');
    Route::patch('/students/{student}/restore', [StudentController::class, 'restore'])
        ->withTrashed()
        ->name('students.restore');
    Route::get('/students/{student}/photo', [StudentPhotoController::class, 'show'])->name('students.photo.show');
    Route::put('/students/{student}/photo', [StudentPhotoController::class, 'update'])->name('students.photo.update');
    Route::delete('/students/{student}/photo', [StudentPhotoController::class, 'destroy'])->name('students.photo.destroy');
    Route::resource('students', StudentController::class)
        ->withTrashed(['show'])
        ->except(['destroy']);
});

require __DIR__.'/auth.php';
