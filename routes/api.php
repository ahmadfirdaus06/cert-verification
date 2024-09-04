<?php

use App\Http\Controllers\CertificateVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('/certificates')->name('certificates.')->group(function () {
    Route::post('/verify', [CertificateVerificationController::class, 'verify'])->name('verify');
    Route::get('/results', [CertificateVerificationController::class, 'results'])->name('results');
});
