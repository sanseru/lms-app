<?php

use Illuminate\Support\Facades\Route;
use Modules\Coding\Http\Controllers\ExerciseController;

Route::name('coding.')->group(function () {
    Route::middleware('can:learning.manage')->group(function () {
        Route::get('courses/{course}/code/create', [ExerciseController::class, 'create'])->name('exercises.create');
        Route::post('courses/{course}/code', [ExerciseController::class, 'store'])->name('exercises.store');
        Route::get('lessons/{lesson}/code/edit', [ExerciseController::class, 'edit'])->name('exercises.edit');
        Route::put('lessons/{lesson}/code', [ExerciseController::class, 'update'])->name('exercises.update');
        Route::post('lessons/{lesson}/code/verify', [ExerciseController::class, 'verify'])->name('exercises.verify');
    });

    Route::post('lessons/{lesson}/code/submit', [ExerciseController::class, 'submit'])->middleware('throttle:30,1')->name('exercises.submit');
});
