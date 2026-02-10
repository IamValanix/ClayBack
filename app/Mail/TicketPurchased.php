<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope; // Importante para Laravel 9+
use Illuminate\Mail\Mailables\Content;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketPurchased extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $pdfData;

    // Recibimos la orden y el PDF generado
    public function __construct(Order $order, $pdfData)
    {
        $this->order = $order;
        $this->pdfData = $pdfData;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Aquí tienes tu entrada! 🚀',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket', // Necesitarás crear esta vista simple
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => $this->pdfData, 'Entrada-Evento.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
