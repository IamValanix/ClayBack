<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketPurchased;

class SendTicketEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $orderData, $ticketCode, $pdfBase64, $email;

    public function __construct($orderData, $ticketCode, $pdfBase64, $email)
    {
        $this->orderData = $orderData;
        $this->ticketCode = $ticketCode;
        $this->pdfBase64 = $pdfBase64;
        $this->email = $email;
    }

    public function handle()
    {
        Mail::to($this->email)->send(new TicketPurchased($this->orderData, $this->ticketCode, $this->pdfBase64));
    }
}
