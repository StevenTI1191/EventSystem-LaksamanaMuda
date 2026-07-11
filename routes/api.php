<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OfficeAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Stateless (tanpa CSRF). Identity Office Level 2 dipanggil dari index.html
| portal (same-origin). Kontrak action meniru Apps Script.
*/

Route::post('/office', [OfficeAuthController::class, 'handle'])->name('api.office');
