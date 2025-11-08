<?php

use AxelvdS\UptimeMonitorExtended\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/api/up-down-stats', [DashboardController::class, 'upDownStats'])->name('api.up-down-stats');
Route::get('/api/devices-down', [DashboardController::class, 'devicesDown'])->name('api.devices-down');
Route::get('/api/uptime-graph', [DashboardController::class, 'uptimeGraph'])->name('api.uptime-graph');

