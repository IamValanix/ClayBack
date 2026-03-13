<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Excluimos las rutas de Webhooks de la verificación CSRF
        // Esto permite que Stripe y PayPal nos envíen datos sin errores de seguridad.
        $middleware->validateCsrfTokens(except: [
            'api/stripe/webhook',
            'api/paypal/webhook', // <--- Añadido para PayPal
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
