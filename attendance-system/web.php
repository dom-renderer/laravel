<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST'], 'attendance', [AttendanceController::class, 'index'])->name('attendance')
Route::get('check-in', [AttendanceController::class, 'in'])->name('check-in')
Route::get('check-out', [AttendanceController::class, 'out'])->name('check-out')
Route::get('break-in', [AttendanceController::class, 'breakIn'])->name('break-in');
Route::get('break-out', [AttendanceController::class, 'breakOut'])->name('break-out');
