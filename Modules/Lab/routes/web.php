<?php

use Illuminate\Support\Facades\Route;
use Modules\Lab\Http\Controllers\CommunityController;
use Modules\Lab\Http\Controllers\LabProjectController;

Route::prefix('lab')->name('lab.')->middleware('can:lab.use')->whereNumber('project')->group(function () {
    Route::get('/', [LabProjectController::class, 'index'])->name('projects.index');
    Route::post('/', [LabProjectController::class, 'store'])->name('projects.store');
    Route::get('{project}', [LabProjectController::class, 'edit'])->name('projects.edit');
    Route::put('{project}', [LabProjectController::class, 'update'])->name('projects.update');
    Route::delete('{project}', [LabProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('{project}/share', [LabProjectController::class, 'share'])->name('projects.share');
    Route::delete('{project}/share', [LabProjectController::class, 'unshare'])->name('projects.unshare');
});

Route::prefix('community')->name('community.')->whereNumber(['project', 'comment'])->group(function () {
    Route::get('/', [CommunityController::class, 'index'])->name('index');
    Route::get('{project}', [CommunityController::class, 'show'])->name('show');
    Route::get('{project}/file', [CommunityController::class, 'file'])->name('file');
    Route::post('{project}/like', [CommunityController::class, 'like'])->name('like');
    Route::post('{project}/comments', [CommunityController::class, 'comment'])->name('comments.store');
    Route::delete('comments/{comment}', [CommunityController::class, 'destroyComment'])->name('comments.destroy');
    Route::post('{project}/hide', [CommunityController::class, 'hide'])->name('hide');
    Route::delete('{project}/hide', [CommunityController::class, 'restore'])->name('restore');
});
