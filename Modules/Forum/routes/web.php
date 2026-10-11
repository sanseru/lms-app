<?php

use Illuminate\Support\Facades\Route;
use Modules\Forum\Http\Controllers\AnswerController;
use Modules\Forum\Http\Controllers\QuestionController;

Route::name('forum.')->prefix('questions')->group(function () {
    Route::get('/', [QuestionController::class, 'index'])->name('questions.index');
    Route::get('new', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/', [QuestionController::class, 'store'])->middleware('throttle:20,1')->name('questions.store');
    Route::get('lesson/{lesson}', [QuestionController::class, 'lessonSummary'])->name('lessons.summary');
    Route::get('{question}', [QuestionController::class, 'show'])->name('questions.show');
    Route::get('{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::post('{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::get('{question}/image', [QuestionController::class, 'image'])->name('questions.image');
    Route::post('{question}/pin', [QuestionController::class, 'pin'])->name('questions.pin');
    Route::post('{question}/resolve', [QuestionController::class, 'resolve'])->name('questions.resolve');
    Route::post('{question}/upvote', [AnswerController::class, 'voteQuestion'])->name('questions.vote');

    Route::post('{question}/answers', [AnswerController::class, 'store'])->middleware('throttle:30,1')->name('answers.store');
    Route::put('answers/{answer}', [AnswerController::class, 'update'])->name('answers.update');
    Route::delete('answers/{answer}', [AnswerController::class, 'destroy'])->name('answers.destroy');
    Route::post('answers/{answer}/accept', [AnswerController::class, 'accept'])->name('answers.accept');
    Route::post('answers/{answer}/helpful', [AnswerController::class, 'vote'])->name('answers.vote');
});
