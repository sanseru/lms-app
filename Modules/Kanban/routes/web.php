<?php

use Illuminate\Support\Facades\Route;
use Modules\Kanban\Http\Controllers\BoardController;
use Modules\Kanban\Http\Controllers\CardController;
use Modules\Kanban\Http\Controllers\ProjectController;

Route::name('kanban.')->prefix('proyek')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('baru', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('{project}', [ProjectController::class, 'show'])->whereNumber('project')->name('projects.show');
    Route::get('{project}/ubah', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::get('papan/{board}', [BoardController::class, 'show'])->name('boards.show');
    Route::put('papan/{board}/hasil', [BoardController::class, 'result'])->name('boards.result');
    Route::post('papan/{board}/kartu', [CardController::class, 'store'])->middleware('throttle:60,1')->name('cards.store');

    Route::put('kartu/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::delete('kartu/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
    Route::post('kartu/{card}/pindah', [CardController::class, 'move'])->name('cards.move');
    Route::post('kartu/{card}/cek', [CardController::class, 'review'])->name('cards.review');
});
