<?php

use Illuminate\Support\Facades\Route;
use Modules\Sessions\Http\Controllers\CalendarController;

Route::get('calendar/{token}.ics', [CalendarController::class, 'feed'])
    ->where('token', '[A-Za-z0-9]{40}')
    ->middleware('throttle:60,1')
    ->name('calendar.feed');
