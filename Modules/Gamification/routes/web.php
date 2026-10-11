<?php

use Illuminate\Support\Facades\Route;
use Modules\Gamification\Http\Controllers\AchievementController;

Route::name('gamification.')->prefix('achievements')->group(function () {
    Route::get('/', [AchievementController::class, 'me'])->name('me');
    Route::get('classes', [AchievementController::class, 'classes'])->name('classes');
    Route::get('student/{student}', [AchievementController::class, 'show'])->name('show');
});
