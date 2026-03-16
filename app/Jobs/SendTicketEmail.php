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
    public $timeout = 120;
    public $tries = 3;

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
            Log::info("Intentando enviar correo a: {$this->email} via Mailtrap API");

            // Usar el mailable existente
            Mail::to($this->email)->send(
                new TicketPurchased($this->orderData, $this->ticketCode, $this->pdfBase64)
            );

            Log::info("Correo enviado exitosamente a: {$this->email}");
        } catch (\Exception $e) {
            Log::error("Error enviando correo: " . $e->getMessage());

            // Reintentar si es necesario
            if ($this->attempts() < $this->tries) {
                $this->release(30); // Reintentar en 30 segundos
            } else {
                throw $e;
            }
        }
    }

    public function failed(\Exception $e)
    {
        Log::error("Job de correo falló definitivamente para: {$this->email}");
        Log::error("Error final: " . $e->getMessage());
    }
}
