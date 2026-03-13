<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Ticket;
use App\Mail\TicketPurchased;
use App\Helpers\HttpClientHelper; // 👈 Importar el helper
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class PaymentController extends Controller
{
    private $ticketLimit;
    private $ticketPrice;

    public function __construct()
    {
        $this->ticketLimit = config('payment.ticket_limit', 150);
        $this->ticketPrice = config('payment.ticket_price', 31.30);
    }

    /**
     * Consulta de tickets disponibles directamente de la DB
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
            'name'  => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email:rfc,dns|max:255',
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
            return response()->json(['success' => false, 'error' => 'Payment service unavailable'], 500);
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
    // PAYPAL LOGIC - VERSIÓN CON SSL INTELIGENTE
    // -------------------------------------------------------------------------

    private function getPaypalBaseUrl()
    {
        return env('PAYPAL_MODE') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Obtiene token de acceso de PayPal con configuración SSL adaptativa
     */
    private function getPaypalToken()
    {
        try {
            Log::info('PayPal: Intentando obtener token', [
                'mode' => env('PAYPAL_MODE'),
                'client_id' => substr(env('PAYPAL_CLIENT_ID'), 0, 10) . '...',
                'url' => $this->getPaypalBaseUrl() . "/v1/oauth2/token",
                'environment' => app()->environment(),
                'verify_ssl' => env('HTTP_VERIFY_SSL', true)
            ]);

            // Usar el helper para crear el cliente con SSL configurado
            $response = HttpClientHelper::createClient()
                ->withBasicAuth(env('PAYPAL_CLIENT_ID'), env('PAYPAL_SECRET'))
                ->asForm()
                ->timeout(30)
                ->post($this->getPaypalBaseUrl() . "/v1/oauth2/token", [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->failed()) {
                Log::error('PayPal token error response', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();

            if (!isset($data['access_token'])) {
                Log::error('PayPal: No access_token en respuesta', ['data' => $data]);
                return null;
            }

            Log::info('PayPal token obtenido exitosamente');
            return $data['access_token'];
        } catch (\Exception $e) {
            Log::error('PayPal token exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Crea una orden en PayPal
     */
    public function createPaypalOrder(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email'
        ]);

        if (Ticket::count() >= $this->ticketLimit) {
            return response()->json([
                'success' => false,
                'error' => 'Sold out!'
            ], 400);
        }

        try {
            $token = $this->getPaypalToken();

            if (!$token) {
                Log::error('PayPal: No se pudo obtener token de autenticación');
                return response()->json([
                    'success' => false,
                    'error' => 'Could not authenticate with PayPal. Please check server configuration.'
                ], 500);
            }

            // Usar el helper para crear el cliente con SSL configurado
            $response = HttpClientHelper::createPayPalClient($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ])
                ->timeout(30)
                ->post($this->getPaypalBaseUrl() . "/v2/checkout/orders", [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format($this->ticketPrice, 2, '.', '')
                        ],
                        'description' => 'Mada Mada Event Ticket'
                    ]],
                    'application_context' => [
                        'brand_name' => 'Mada Mada Events',
                        'landing_page' => 'BILLING',
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'PAY_NOW',
                        'return_url' => env('FRONTEND_URL') . '/?payment=success',
                        'cancel_url' => env('FRONTEND_URL') . '/?payment=cancel'
                    ]
                ]);

            if ($response->failed()) {
                Log::error('PayPal create order error: ' . $response->body());
                return response()->json([
                    'success' => false,
                    'error' => 'PayPal order creation failed: ' . $response->status()
                ], 500);
            }

            $data = $response->json();

            if (!isset($data['id'])) {
                Log::error('PayPal: No order ID in response', ['response' => $data]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid PayPal response'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'orderID' => $data['id']
            ]);
        } catch (\Exception $e) {
            Log::error("PayPal Create Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'PayPal service temporarily unavailable: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Captura (completa) una orden de PayPal
     */
    public function capturePaypalOrder(Request $request)
    {
        $validated = $request->validate([
            'orderID' => 'required|string',
            'email'   => 'sometimes|email',
            'name'    => 'sometimes|string'
        ]);

        try {
            $token = $this->getPaypalToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication failed'
                ], 500);
            }

            // Usar el helper para crear el cliente con SSL configurado
            $response = HttpClientHelper::createPayPalClient($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(30)
                ->post($this->getPaypalBaseUrl() . "/v2/checkout/orders/{$validated['orderID']}/capture");

            if ($response->failed()) {
                Log::error('PayPal capture error: ' . $response->body());
                return response()->json([
                    'success' => false,
                    'error' => 'Payment capture failed'
                ], 400);
            }

            $data = $response->json();

            if (($data['status'] ?? '') === 'COMPLETED') {
                // Obtener email de PayPal o del request
                $email = $data['payer']['email_address'] ?? $validated['email'] ?? null;

                if (!$email) {
                    Log::error('PayPal: No email in response');
                    return response()->json([
                        'success' => false,
                        'error' => 'Could not retrieve payer email'
                    ], 400);
                }

                // Obtener nombre
                $givenName = $data['payer']['name']['given_name'] ?? $request->input('name', 'Customer');
                $surname = $data['payer']['name']['surname'] ?? '';
                $name = trim("$givenName $surname");
                $name = preg_replace('/[^a-zA-Z\s]/', '', $name) ?: 'Customer';

                return $this->fulfillOrder($name, $email, 'paypal', $data['id']);
            }

            return response()->json([
                'success' => false,
                'error' => 'Payment not completed'
            ], 400);
        } catch (\Exception $e) {
            Log::error("PayPal Capture Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Payment processing failed'
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // CUMPLIMIENTO (FULFILLMENT)
    // -------------------------------------------------------------------------

    protected function fulfillOrder($name, $email, $gateway, $paymentId)
    {
        $existingOrder = Order::where('payment_id', $paymentId)->first();
        if ($existingOrder) {
            $ticket = Ticket::where('order_id', $existingOrder->id)->first();
            return response()->json([
                'success' => true,
                'message' => 'Ticket already processed.',
                'ticket_code' => $ticket->ticket_code ?? null
            ]);
        }

        try {
            return DB::transaction(function () use ($name, $email, $gateway, $paymentId) {
                $currentCount = Ticket::count();
                if ($currentCount >= $this->ticketLimit) {
                    throw new \Exception('Sold out during transaction');
                }

                $order = Order::create([
                    'customer_name'   => $name,
                    'customer_email'  => $email,
                    'amount'          => $this->ticketPrice,
                    'status'          => 'completed',
                    'payment_gateway' => $gateway,
                    'payment_id'      => $paymentId,
                ]);

                $ticketCode = 'TKT-' . strtoupper(Str::random(6)) . '-' . date('Y');
                $qrData = QrCode::format('svg')->size(150)->margin(1)->generate($ticketCode);
                $qrBase64 = base64_encode((string)$qrData);

                $ticket = Ticket::create([
                    'order_id'    => $order->id,
                    'ticket_code' => $ticketCode,
                ]);

                $pdf = Pdf::loadView('pdfs.ticket', [
                    'order'  => $order,
                    'ticket' => $ticket,
                    'qr'     => $qrBase64
                ])->setPaper('a4', 'portrait');

                // Preparamos datos planos para el Mailable
                $orderData = $order->toArray();
                $orderData['created_at_formatted'] = $order->created_at->format('d M, Y');
                $pdfBase64 = base64_encode($pdf->output());

                // Usamos ->send() para evitar problemas de serialización en la tabla jobs
                Mail::to($email)->send(new TicketPurchased($orderData, $ticketCode, $pdfBase64));

                return response()->json([
                    'success'     => true,
                    'message'     => 'Success! Ticket sent to email.',
                    'ticket_code' => $ticketCode,
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Fulfillment Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Processing failed'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // WEBHOOKS
    // -------------------------------------------------------------------------
    public function handlePaypalWebhook(Request $request)
    {
        $payload = $request->all();

        if (($payload['event_type'] ?? '') === 'CHECKOUT.ORDER.APPROVED') {
            $orderId = $payload['resource']['id'];

            // 1. Obtener el token de autenticación
            $token = $this->getPaypalToken();

            // 2. Obtener detalles de la orden directamente desde PayPal
            $response = HttpClientHelper::createPayPalClient($token)
                ->get($this->getPaypalBaseUrl() . "/v2/checkout/orders/{$orderId}");

            if ($response->successful()) {
                $data = $response->json();

                // 3. Extraer info del pagador
                $payer = $data['payer'];
                $email = $payer['email_address'];
                $name = ($payer['name']['given_name'] ?? 'Guest') . ' ' . ($payer['name']['surname'] ?? '');

                // 4. Llamar a la función de cumplimiento (como si fuera un pago normal)
                $this->fulfillOrder($name, $email, 'paypal', $orderId);

                Log::info("PayPal Webhook: Orden {$orderId} completada exitosamente.");
            }
        }

        return response()->json(['status' => 'success']);
    }
    public function handleWebhook(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $sig_header = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($request->getContent(), $sig_header, env('STRIPE_WEBHOOK_SECRET'));
            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                $this->fulfillOrder(
                    $session->metadata->customer_name ?? 'Guest',
                    $session->customer_email,
                    'stripe',
                    $session->payment_intent ?? $session->id
                );
            }
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error("Stripe Webhook Error: " . $e->getMessage());
            return response()->json(['error' => 'Webhook failed'], 400);
        }
    }
}
