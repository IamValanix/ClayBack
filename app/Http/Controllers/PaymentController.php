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

class PaymentController extends Controller
{
    public function simulatePurchase(Request $request)
    {
        // 1. SEGURIDAD: Validación estricta de datos de entrada
        // Esto evita inyecciones SQL y datos mal formados
        $validated = $request->validate([
            'name'  => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/', // Solo letras y espacios
            'email' => 'required|email:rfc,dns|max:255', // Valida formato y DNS del dominio
        ], [
            'name.regex' => 'El nombre solo puede contener letras.',
            'email.email' => 'Proporciona un correo electrónico válido.'
        ]);

        // Iniciamos una transacción de base de datos.
        // Si algo falla dentro, se revierte todo (Rollback).
        return DB::transaction(function () use ($validated) {
            try {
                // A. Crear la Orden (Simulada)
                $order = Order::create([
                    'customer_name'   => $validated['name'],
                    'customer_email'  => $validated['email'],
                    'amount'          => 100.00, // Precio fijo por ahora
                    'status'          => 'completed', // Simulamos éxito directo
                    'payment_gateway' => 'simulation',
                    'payment_id'      => 'SIM-' . Str::upper(Str::random(10)),
                ]);

                // B. Generar Código Único para el Ticket
                $ticketCode = 'TKT-' . strtoupper(Str::random(6)) . '-' . date('Y');

                // C. Generar QR en formato SVG (no requiere imagick)
                $qrData = QrCode::format('svg') // Cambiamos de 'png' a 'svg'
                    ->size(200)
                    ->margin(1)
                    ->generate($ticketCode);

                // Para el PDF, pasamos el SVG tal cual o lo encodeamos
                $qrBase64 = base64_encode($qrData);

                // D. Crear el Ticket en BD
                $ticket = Ticket::create([
                    'order_id'    => $order->id,
                    'ticket_code' => $ticketCode,
                ]);

                // E. Generar PDF en memoria
                $pdf = Pdf::loadView('pdfs.ticket', [
                    'order'  => $order,
                    'ticket' => $ticket,
                    'qr'     => $qrBase64
                ]);

                // Configuración opcional del papel
                $pdf->setPaper('a4', 'portrait');

                // F. Enviar Correo (Manejo de errores específico para correo)
                try {
                    Mail::to($order->customer_email)
                        ->send(new TicketPurchased($order, $pdf->output()));
                } catch (\Exception $e) {
                    // Si falla el correo, podríamos decidir si revertir la compra o solo loguear el error.
                    // Por ahora, lanzamos error para que el usuario sepa que falló.
                    throw new \Exception("Error enviando el correo: " . $e->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'message' => '¡Compra simulada exitosa! Ticket enviado al correo.',
                    'ticket_code' => $ticketCode
                ], 200);
            } catch (\Exception $e) {
                // Si algo falla, Laravel hace Rollback automático de la DB
                return response()->json([
                    'success' => false,
                    'error'   => 'Hubo un problema procesando tu solicitud.',
                    'debug'   => $e->getMessage() // Quitar esto en producción
                ], 500);
            }
        });
    }
}
