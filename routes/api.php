<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

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
Route::get('/download-ticket', [PaymentController::class, 'downloadTicket'])->name('download.ticket');


Route::get('/test-mailtrap', function () {
    Mail::raw('Este es un correo de prueba sencillo desde Mailtrap API.', function ($message) {
        $message->to('valan465@gmail.com')->subject('Prueba Mailtrap Básica');
    });
    return 'Correo de prueba enviado a Mailtrap.';
});
