<?php

use Illuminate\Support\Facades\Route;
use Modules\Classes\Http\Controllers\ClassRoomController;
use Modules\Classes\Http\Controllers\ScheduleController;

Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');

Route::middleware('can:classes.manage')->group(function () {
    Route::get('classes/create', [ClassRoomController::class, 'create'])->name('classes.create');
    Route::post('classes', [ClassRoomController::class, 'store'])->name('classes.store');
    Route::get('classes/{class}/edit', [ClassRoomController::class, 'edit'])->name('classes.edit');
    Route::put('classes/{class}', [ClassRoomController::class, 'update'])->name('classes.update');
    Route::delete('classes/{class}', [ClassRoomController::class, 'destroy'])->name('classes.destroy');
    Route::post('classes/{class}/students', [ClassRoomController::class, 'enroll'])->name('classes.students.store');
    Route::delete('classes/{class}/students/{student}', [ClassRoomController::class, 'unenroll'])->name('classes.students.destroy');
});

Route::get('classes', [ClassRoomController::class, 'index'])->name('classes.index');
Route::get('classes/{class}', [ClassRoomController::class, 'show'])->name('classes.show');
