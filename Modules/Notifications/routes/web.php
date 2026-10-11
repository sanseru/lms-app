<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\NotificationController;

Route::name('notifications.')->prefix('notifikasi')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('baca-semua', [NotificationController::class, 'readAll'])->name('read-all');
    Route::get('{id}', [NotificationController::class, 'open'])->whereUuid('id')->name('open');
});
