<?php

use App\Http\Controllers\ReminderController;
use Illuminate\Support\Facades\Route;

Route::get('/assets/app.css', fn () => response(file_get_contents(resource_path('css/app.css')))->header('Content-Type', 'text/css'))->name('assets.css');
Route::get('/assets/app.js', fn () => response(file_get_contents(resource_path('js/app.js')))->header('Content-Type', 'application/javascript'))->name('assets.js');

Route::get('/', [ReminderController::class, 'index'])->name('reminders.index');
Route::post('/reminders', [ReminderController::class, 'store'])->name('reminders.store');
Route::put('/reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
Route::delete('/reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');
Route::patch('/reminders/{reminder}/toggle', [ReminderController::class, 'toggle'])->name('reminders.toggle');
Route::patch('/reminders/{reminder}/snooze', [ReminderController::class, 'snooze'])->name('reminders.snooze');
