<?php

use Illuminate\Support\Facades\Route;
use Modules\Gamification\Http\Controllers\AchievementController;

Route::name('gamification.')->prefix('prestasi')->group(function () {
    Route::get('/', [AchievementController::class, 'me'])->name('me');
    Route::get('kelas', [AchievementController::class, 'classes'])->name('classes');
    Route::get('siswa/{student}', [AchievementController::class, 'show'])->name('show');
});
