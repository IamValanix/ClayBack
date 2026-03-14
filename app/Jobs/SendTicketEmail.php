<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\TicketPurchased;

class SendTicketEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $orderData, $ticketCode, $pdfBase64, $email;
    public $timeout = 120; // Aumentar timeout a 120 segundos

    public function __construct($orderData, $ticketCode, $pdfBase64, $email)
    {
        $this->orderData = $orderData;
        $this->ticketCode = $ticketCode;
        $this->pdfBase64 = $pdfBase64;
        $this->email = $email;
    }

    public function handle()
    {
        try {
            Log::info("Intentando enviar correo SIMPLE a: {$this->email}");

            // Enviar solo texto primero para probar
            Mail::raw("¡Gracias por tu compra! Tu código de ticket es: {$this->ticketCode}", function ($message) {
                $message->to($this->email)
                    ->subject('¡Ticket confirmado!');
            });

            Log::info("Correo SIMPLE enviado exitosamente a: {$this->email}");
        } catch (\Exception $e) {
            Log::error("Error enviando correo simple: " . $e->getMessage());
            throw $e;
        }
    }
}
