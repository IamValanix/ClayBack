<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/simulate-payment', [PaymentController::class, 'simulatePurchase']);
