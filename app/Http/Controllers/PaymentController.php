<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Ticket;
use App\Mail\TicketPurchased;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class PaymentController extends Controller
{
    public function simulatePurchase(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email:rfc,dns|max:255',
        ], [
            'name.regex'  => 'El nombre solo puede contener letras.',
            'email.email' => 'Proporciona un correo electrónico válido.'
        ]);

        return DB::transaction(function () use ($validated) {
            try {
                $order = Order::create([
                    'customer_name'   => $validated['name'],
                    'customer_email'  => $validated['email'],
                    'amount'          => 30.00,
                    'status'          => 'completed',
                    'payment_gateway' => 'simulation',
                    'payment_id'      => 'SIM-' . Str::upper(Str::random(10)),
                ]);

                $ticketCode = 'TKT-' . strtoupper(Str::random(6)) . '-' . date('Y');

                $qrData = QrCode::format('svg')
                    ->size(200)
                    ->margin(1)
                    ->generate($ticketCode);

                $qrBase64 = base64_encode($qrData);

                $ticket = Ticket::create([
                    'order_id'    => $order->id,
                    'ticket_code' => $ticketCode,
                ]);

                $pdf = Pdf::loadView('pdfs.ticket', [
                    'order'  => $order,
                    'ticket' => $ticket,
                    'qr'     => $qrBase64
                ])->setPaper('a4', 'portrait');

                Mail::to($order->customer_email)->send(new TicketPurchased($order, $pdf->output()));

                return response()->json([
                    'success'     => true,
                    'message'     => '¡Compra simulada exitosa! Ticket enviado al correo.',
                    'ticket_code' => $ticketCode
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Hubo un problema procesando tu solicitud.',
                    'debug'   => $e->getMessage()
                ], 500);
            }
        });
    }

    public function createCheckoutSession(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email:rfc,dns|max:255',
        ]);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Buenas prácticas: Usar variables de entorno para las URLs
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => ['name' => 'Mada Mada ticket'],
                    'unit_amount'  => 3000, // $30.00
                ],
                'quantity' => 1,
            ]],
            'mode'           => 'payment',
            'customer_email' => $validated['email'],
            'success_url'    => $frontendUrl . '/?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'     => $frontendUrl . '/#payment-section',
            'metadata'       => [
                'customer_name' => $validated['name'],
            ],
        ]);

        return response()->json([
            'success' => true,
            'url'     => $session->url,
        ]);
    }

    public function verifyStripeSuccess(Request $request)
    {
        $sessionId = $request->query('session_id') ?? $request->input('session_id');

        if (!$sessionId) {
            return response()->json(['success' => false, 'error' => 'No session ID proporcionado'], 400);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return response()->json(['success' => false, 'error' => 'El pago no se completó'], 400);
            }

            $paymentId = $session->payment_intent ?? $session->id;

            // 🌟 FIX CRÍTICO: IDEMPOTENCIA (Evita duplicados si el usuario recarga la página)
            $existingOrder = Order::where('payment_id', $paymentId)->first();

            if ($existingOrder) {
                $existingTicket = Ticket::where('order_id', $existingOrder->id)->first();
                return response()->json([
                    'success'     => true,
                    'message'     => 'El pago ya fue procesado anteriormente y tu ticket fue enviado.',
                    'ticket_code' => $existingTicket ? $existingTicket->ticket_code : null,
                ]);
            }

            $name  = $session->metadata->customer_name ?? 'Cliente';
            $email = $session->customer_email;

            return DB::transaction(function () use ($name, $email, $session, $paymentId) {
                try {
                    $order = Order::create([
                        'customer_name'   => $name,
                        'customer_email'  => $email,
                        'amount'          => 30.00,
                        'status'          => 'completed',
                        'payment_gateway' => 'stripe',
                        'payment_id'      => $paymentId,
                    ]);

                    $ticketCode = 'TKT-' . strtoupper(Str::random(6)) . '-' . date('Y');

                    $qrData = QrCode::format('svg')
                        ->size(200)
                        ->margin(1)
                        ->generate($ticketCode);

                    $qrBase64 = base64_encode($qrData);

                    $ticket = Ticket::create([
                        'order_id'    => $order->id,
                        'ticket_code' => $ticketCode,
                    ]);

                    $pdf = Pdf::loadView('pdfs.ticket', [
                        'order'  => $order,
                        'ticket' => $ticket,
                        'qr'     => $qrBase64
                    ])->setPaper('a4', 'portrait');

                    Mail::to($email)->send(new TicketPurchased($order, $pdf->output()));

                    return response()->json([
                        'success'     => true,
                        'message'     => '¡Pago exitoso! Ticket enviado a tu correo.',
                        'ticket_code' => $ticketCode,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception("Error creando ticket: " . $e->getMessage());
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Error verificando el pago: ' . $e->getMessage()
            ], 500);
        }
    }
}
