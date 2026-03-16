<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Ticket;
use App\Helpers\HttpClientHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Carbon\Carbon;

class PaymentController extends Controller
{
    private $ticketLimit;
    private $ticketPrice;

    public function __construct()
    {
        // Configuración de límites y precios
        $this->ticketLimit = config('payment.ticket_limit', 150);
        $this->ticketPrice = config('payment.ticket_price', 31.30);
    }

    /**
     * Consulta de tickets disponibles
     */
    public function getAvailableTickets()
    {
        $soldTickets = Ticket::count();
        $available = max(0, $this->ticketLimit - $soldTickets);

        return response()->json([
            'success'   => true,
            'available' => $available,
            'sold_out'  => $available === 0
        ]);
    }

    // -------------------------------------------------------------------------
    // STRIPE LOGIC
    // -------------------------------------------------------------------------

    public function createCheckoutSession(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        if (Ticket::count() >= $this->ticketLimit) {
            return response()->json(['success' => false, 'error' => 'Sold out!'], 400);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency'     => 'usd',
                        'product_data' => ['name' => 'Mada Mada Event Ticket'],
                        'unit_amount'  => (int)($this->ticketPrice * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode'           => 'payment',
                'customer_email' => $validated['email'],
                'success_url'    => env('FRONTEND_URL') . '/?session_id={CHECKOUT_SESSION_ID}#payment-section',
                'cancel_url'     => env('FRONTEND_URL') . '/#payment-section',
                'metadata'       => ['customer_name' => $validated['name']],
            ]);

            return response()->json(['success' => true, 'url' => $session->url]);
        } catch (\Exception $e) {
            Log::error("Stripe Session Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Stripe error'], 500);
        }
    }

    public function verifyStripeSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) return response()->json(['success' => false, 'error' => 'No session ID'], 400);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $session = StripeSession::retrieve($sessionId);
            if ($session->payment_status !== 'paid') {
                return response()->json(['success' => false, 'error' => 'Payment not completed'], 400);
            }

            return $this->fulfillOrder(
                $session->metadata->customer_name ?? 'Guest',
                $session->customer_email,
                'stripe',
                $session->payment_intent ?? $session->id
            );
        } catch (\Exception $e) {
            Log::error("Stripe Verification Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Verification failed'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // PAYPAL LOGIC
    // -------------------------------------------------------------------------

    private function getPaypalBaseUrl()
    {
        return env('PAYPAL_MODE') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    private function getPaypalToken()
    {
        try {
            $response = HttpClientHelper::createClient()
                ->withBasicAuth(env('PAYPAL_CLIENT_ID'), env('PAYPAL_SECRET'))
                ->asForm()
                ->post($this->getPaypalBaseUrl() . "/v1/oauth2/token", ['grant_type' => 'client_credentials']);

            return $response->json()['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error("PayPal Token Error: " . $e->getMessage());
            return null;
        }
    }

    public function createPaypalOrder(Request $request)
    {
        $token = $this->getPaypalToken();
        if (!$token) return response()->json(['success' => false, 'error' => 'PayPal Auth Failed'], 500);

        $response = HttpClientHelper::createPayPalClient($token)
            ->post($this->getPaypalBaseUrl() . "/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($this->ticketPrice, 2, '.', '')
                    ]
                ]]
            ]);

        return response()->json(['success' => true, 'orderID' => $response->json()['id']]);
    }

    public function capturePaypalOrder(Request $request)
    {
        $token = $this->getPaypalToken();
        $orderID = $request->input('orderID');

        $response = HttpClientHelper::createPayPalClient($token)
            ->post($this->getPaypalBaseUrl() . "/v2/checkout/orders/{$orderID}/capture");

        $data = $response->json();

        if (($data['status'] ?? '') === 'COMPLETED') {
            $email = $data['payer']['email_address'];
            $name = ($data['payer']['name']['given_name'] ?? 'Guest') . ' ' . ($data['payer']['name']['surname'] ?? '');
            return $this->fulfillOrder($name, $email, 'paypal', $data['id']);
        }

        return response()->json(['success' => false, 'error' => 'PayPal capture failed'], 400);
    }

    // -------------------------------------------------------------------------
    // FULFILLMENT & DOWNLOAD
    // -------------------------------------------------------------------------

    protected function fulfillOrder($name, $email, $gateway, $paymentId)
    {
        try {
            $orderData = DB::transaction(function () use ($name, $email, $gateway, $paymentId) {
                // Verificar si ya existe para evitar duplicados
                $existingOrder = Order::where('payment_id', $paymentId)->first();
                if ($existingOrder) {
                    $tkt = Ticket::where('order_id', $existingOrder->id)->first();
                    return ['order' => $existingOrder, 'ticket' => $tkt];
                }

                if (Ticket::count() >= $this->ticketLimit) throw new \Exception('Sold out');

                $order = Order::create([
                    'customer_name'   => $name,
                    'customer_email'  => $email,
                    'amount'          => $this->ticketPrice,
                    'status'          => 'completed',
                    'payment_gateway' => $gateway,
                    'payment_id'      => $paymentId,
                ]);

                $ticket = Ticket::create([
                    'order_id'    => $order->id,
                    'ticket_code' => 'TKT-' . strtoupper(Str::random(6)) . '-' . date('Y'),
                ]);

                return ['order' => $order, 'ticket' => $ticket];
            });

            return response()->json([
                'success'     => true,
                'ticket_code' => $orderData['ticket']->ticket_code,
                'download_url' => route('download.ticket', ['code' => $orderData['ticket']->ticket_code])
            ]);
        } catch (\Exception $e) {
            Log::error("Fulfillment Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Processing failed'], 500);
        }
    }

    /**
     * Genera y descarga el PDF del ticket
     */
    public function downloadTicket(Request $request)
    {
        $ticket = Ticket::where('ticket_code', $request->query('code'))->firstOrFail();
        $order = Order::findOrFail($ticket->order_id);

        // Generar QR en Base64
        $qrData = QrCode::format('svg')->size(150)->margin(1)->generate($ticket->ticket_code);
        $qrBase64 = base64_encode((string)$qrData);

        // Cargar vista del PDF
        $pdf = Pdf::loadView('pdfs.ticket', [
            'order'      => $order,
            'ticket'     => $ticket,
            'qr'         => $qrBase64,
            'ticketCode' => $ticket->ticket_code
        ]);

        // Configurar papel (opcional, por defecto es A4)
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("Ticket-{$ticket->ticket_code}.pdf");
    }
}
