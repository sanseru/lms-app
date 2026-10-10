<?php

use Illuminate\Support\Facades\Route;
use Modules\People\Http\Controllers\GuardianController;
use Modules\People\Http\Controllers\StudentController;
use Modules\People\Http\Controllers\TeacherController;

Route::prefix('people')->name('people.')->group(function () {
    Route::resource('students', StudentController::class)
        ->except('show')
        ->middleware('can:people.students.manage');

    Route::resource('teachers', TeacherController::class)
        ->except('show')
        ->middleware('can:people.teachers.manage');

    Route::resource('guardians', GuardianController::class)
        ->except('show')
        ->middleware('can:people.guardians.manage');
});
