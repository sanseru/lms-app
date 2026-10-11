<?php

use Illuminate\Support\Facades\Route;
use Modules\Forum\Http\Controllers\AnswerController;
use Modules\Forum\Http\Controllers\QuestionController;

Route::name('forum.')->prefix('tanya')->group(function () {
    Route::get('/', [QuestionController::class, 'index'])->name('questions.index');
    Route::get('baru', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/', [QuestionController::class, 'store'])->middleware('throttle:20,1')->name('questions.store');
    Route::get('pelajaran/{lesson}', [QuestionController::class, 'lessonSummary'])->name('lessons.summary');
    Route::get('{question}', [QuestionController::class, 'show'])->name('questions.show');
    Route::get('{question}/ubah', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::post('{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::get('{question}/gambar', [QuestionController::class, 'image'])->name('questions.image');
    Route::post('{question}/sematkan', [QuestionController::class, 'pin'])->name('questions.pin');
    Route::post('{question}/selesai', [QuestionController::class, 'resolve'])->name('questions.resolve');
    Route::post('{question}/dukung', [AnswerController::class, 'voteQuestion'])->name('questions.vote');

    Route::post('{question}/jawaban', [AnswerController::class, 'store'])->middleware('throttle:30,1')->name('answers.store');
    Route::put('jawaban/{answer}', [AnswerController::class, 'update'])->name('answers.update');
    Route::delete('jawaban/{answer}', [AnswerController::class, 'destroy'])->name('answers.destroy');
    Route::post('jawaban/{answer}/terbaik', [AnswerController::class, 'accept'])->name('answers.accept');
    Route::post('jawaban/{answer}/bantu', [AnswerController::class, 'vote'])->name('answers.vote');
});
