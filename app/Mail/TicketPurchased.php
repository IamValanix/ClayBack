<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;

class TicketPurchased extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $pdfBase64; // Renombrado para mayor claridad

    public function __construct(Order $order, $pdfBase64)
    {
        $this->order = $order;
        $this->pdfBase64 = $pdfBase64;
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
            view: 'emails.ticket',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => base64_decode($this->pdfBase64), 'Entrada-Evento.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
