<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;

class TicketPurchased extends Mailable
{
    use Queueable, SerializesModels;

    public $orderData;
    public $ticketCode;
    public $pdfBase64;

    public function __construct(array $orderData, $ticketCode, $pdfBase64)
    {
        $this->orderData = $orderData;
        $this->ticketCode = $ticketCode;
        $this->pdfBase64 = $pdfBase64;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '¡Aquí tienes tu entrada! 🚀');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.ticket');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => base64_decode($this->pdfBase64), 'Entrada-Evento.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
