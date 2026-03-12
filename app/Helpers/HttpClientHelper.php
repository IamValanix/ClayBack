<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class HttpClientHelper
{
    /**
     * Obtiene la configuración SSL basada en el entorno
     */
    public static function getSslOptions(): array
    {
        $verifySsl = env('HTTP_VERIFY_SSL', true);

        // En producción, siempre verificar SSL
        if (app()->environment('production')) {
            $verifySsl = true;
        }

        return [
            'verify' => $verifySsl
        ];
    }

    /**
     * Crea una instancia de Http con opciones SSL configuradas
     */
    public static function createClient()
    {
        return Http::withOptions(self::getSslOptions());
    }

    /**
     * Para PayPal específicamente
     */
    public static function createPayPalClient($token = null)
    {
        $client = Http::withOptions(self::getSslOptions());

        if ($token) {
            $client = $client->withToken($token);
        }

        return $client;
    }
}
