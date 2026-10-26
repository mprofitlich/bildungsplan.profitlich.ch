<?php

use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\FreigabeController;
use App\Http\Controllers\KursController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PersonenController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('app'));

Route::get('/login/{token}', [LoginController::class, 'zeigen'])->name('login.zeigen');
Route::post('/login/{token}', [LoginController::class, 'einloesen'])->middleware('throttle:20,10')->name('login.einloesen');

// Die API liegt bei den Web-Routen, weil sie dieselbe Sitzung und denselben CSRF-Schutz braucht.
Route::prefix('api')->group(function () {
    Route::post('/login', [LoginController::class, 'anfordern'])->middleware('throttle:20,10');
    Route::post('/logout', [LoginController::class, 'abmelden']);

    Route::middleware('auth')->group(function () {
        Route::get('/kurse', [KursController::class, 'index']);
        Route::post('/kurse', [KursController::class, 'store']);
        Route::post('/kurse/import', [KursController::class, 'import']);
        Route::get('/kurse/{kurs}', [KursController::class, 'show']);
        Route::put('/kurse/{kurs}', [KursController::class, 'update']);
        Route::delete('/kurse/{kurs}', [KursController::class, 'destroy']);
        Route::put('/kurse/{kurs}/curriculum', [KursController::class, 'curriculum']);
        Route::post('/kurse/{kurs}/sperre', [KursController::class, 'sperren']);
        Route::delete('/kurse/{kurs}/sperre', [KursController::class, 'entsperren']);
        Route::post('/kurse/{kurs}/freigaben', [FreigabeController::class, 'store']);
        Route::delete('/kurse/{kurs}/freigaben', [FreigabeController::class, 'destroy']);
        Route::get('/personen', PersonenController::class);
        Route::get('/curriculum', CurriculumController::class);
    });
});
