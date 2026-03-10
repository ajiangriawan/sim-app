<?php

use App\Http\Controllers\LaporanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php
Route::get('/laporan/export', [LaporanController::class, 'export'])
    ->name('laporan.export');
