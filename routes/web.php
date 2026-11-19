<?php

use App\Http\Controllers\ScraperController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScraperController::class, 'index'])->name('scraper.index');
Route::post('/scrape', [ScraperController::class, 'scrape'])->name('scraper.scrape');
Route::get('/scrape/status/{runId}', [ScraperController::class, 'status'])->name('scraper.status');
