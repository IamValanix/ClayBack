<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Agrupamos lo que necesita protección contra ataques de fuerza bruta o bots
Route::middleware(['throttle:payments'])->group(function () {
    Route::post('/create-checkout-session', [PaymentController::class, 'createCheckoutSession']);
    Route::post('/paypal/create-order', [PaymentController::class, 'createPaypalOrder']);
    Route::post('/paypal/capture-order', [PaymentController::class, 'capturePaypalOrder']);
});

// Rutas públicas normales
Route::get('/stripe/success', [PaymentController::class, 'verifyStripeSuccess']);
Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook']);
Route::get('/tickets/available', [PaymentController::class, 'getAvailableTickets']);
Route::post('/paypal/webhook', [PaymentController::class, 'handlePaypalWebhook']);
