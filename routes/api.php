<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\DownloadController;
use App\Http\Controllers\Api\V1\LicenseController;
use App\Http\Controllers\Api\V1\StatusController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public Endpoints ──
    Route::get('/config', [StatusController::class, 'getConfig']);
    Route::get('/plans', [CheckoutController::class, 'getPlans']);
    Route::get('/status', [StatusController::class, 'checkStatus']);
    Route::post('/download/track', [DownloadController::class, 'track']);
    Route::post('/license/activate', [LicenseController::class, 'activate']);
    Route::post('/license/verify', [LicenseController::class, 'verify']);

    // ── Auth Endpoints ──
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // ── Checkout & Transactions ──
    Route::post('/checkout/create', [CheckoutController::class, 'createTransaction']);

    // ── Payment Webhooks (No auth, validated by signature) ──
    Route::post('/webhook/{gateway}', [WebhookController::class, 'handle']);

    // ── Protected Endpoints (Require Bearer Token) ──
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/bind-device', [AuthController::class, 'bindDevice']);
    });
});
