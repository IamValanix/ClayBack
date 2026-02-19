<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/simulate-payment', [PaymentController::class, 'simulatePurchase']);
Route::post('/create-checkout-session', [PaymentController::class, 'createCheckoutSession']);
Route::get('/stripe/success', [PaymentController::class, 'verifyStripeSuccess']);
Route::get('/tickets/available', [PaymentController::class, 'getAvailableTickets']);
