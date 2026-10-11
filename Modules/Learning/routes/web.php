<?php

use Illuminate\Support\Facades\Route;
use Modules\Learning\Http\Controllers\CourseController;
use Modules\Learning\Http\Controllers\GradingController;
use Modules\Learning\Http\Controllers\LessonController;
use Modules\Learning\Http\Controllers\LessonReportController;
use Modules\Learning\Http\Controllers\ProgressController;
use Modules\Learning\Http\Controllers\QuizController;
use Modules\Learning\Http\Controllers\SubmissionController;

Route::name('learning.')->group(function () {
    Route::middleware('can:learning.manage')->group(function () {
        Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
        Route::put('courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
        Route::post('courses/{course}/classes', [CourseController::class, 'attachClass'])->name('courses.classes.store');
        Route::delete('courses/{course}/classes/{class}', [CourseController::class, 'detachClass'])->name('courses.classes.destroy');

        Route::get('courses/{course}/lessons/create', [LessonController::class, 'create'])->name('lessons.create');
        Route::post('courses/{course}/lessons', [LessonController::class, 'store'])->name('lessons.store');
        Route::get('lessons/{lesson}/edit', [LessonController::class, 'edit'])->name('lessons.edit');
        Route::put('lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
        Route::delete('lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
        Route::post('lessons/{lesson}/move', [LessonController::class, 'move'])->name('lessons.move');

        Route::get('grading', [GradingController::class, 'index'])->name('grading.index');
        Route::get('lesson-reports', [LessonReportController::class, 'index'])->name('reports.index');
        Route::post('lesson-reports/{report}/close', [LessonReportController::class, 'resolve'])->name('reports.resolve');
    });

    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
    Route::post('lessons/{lesson}/report', [LessonReportController::class, 'store'])->middleware('throttle:10,1')->name('reports.store');
    Route::post('lessons/{lesson}/complete', [LessonController::class, 'complete'])->name('lessons.complete');
    Route::post('lessons/{lesson}/quiz', [QuizController::class, 'store'])->name('quiz.store');
    Route::post('lessons/{lesson}/quiz/start', [QuizController::class, 'start'])->name('quiz.start');
    Route::post('lessons/{lesson}/submission', [SubmissionController::class, 'store'])->name('submissions.store');
    Route::post('submissions/{submission}/grade', [SubmissionController::class, 'grade'])->name('submissions.grade');
    Route::get('submissions/{submission}/file', [SubmissionController::class, 'file'])->name('submissions.file');

    Route::get('progress', [ProgressController::class, 'index'])->middleware('role:guardian')->name('progress.index');
    Route::get('progress/{student}', [ProgressController::class, 'show'])->name('progress.show');
});
