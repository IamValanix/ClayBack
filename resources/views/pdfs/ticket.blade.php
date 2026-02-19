<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Boarding Ticket</title>
    <style>
        /* Reset para PDF */
        @page {
            margin: 0px;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #09090b;
            /* Zinc 950 del front */
            color: #ffffff;
        }

        .page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* El Ticket con estilo Dark */
        .ticket-wrapper {
            background-color: #18181b;
            /* Zinc 900 */
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #3f3f46;
            /* Borde sutil */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        /* Encabezado con el Morado del checkout */
        .header {
            background-color: #111113;
            /* Fondo más oscuro para contraste */
            color: #ffffff;
            padding: 25px 35px;
            border-bottom: 3px solid #a855f7;
            /* Línea Morada Neón */
        }

        .header-table {
            width: 100%;
        }

        .header-title {
            font-size: 26px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .header-logo {
            text-align: right;
            font-size: 13px;
            color: #a855f7;
            /* Texto morado */
            font-weight: bold;
        }

        /* Cuerpo */
        .body-section {
            padding: 35px;
        }

        .layout-table {
            width: 100%;
            border-collapse: collapse;
        }

        .layout-col-left {
            width: 65%;
            vertical-align: top;
            padding-right: 20px;
        }

        .layout-col-right {
            width: 35%;
            vertical-align: top;
            text-align: center;
            border-left: 1px dashed #3f3f46;
            /* Línea de corte oscura */
            padding-left: 20px;
        }

        /* Estilos de etiquetas y datos */
        .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #a1a1aa;
            /* Zinc 400 */
            margin-bottom: 4px;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
            color: #f4f4f5;
            margin-bottom: 20px;
        }

        .value-large {
            font-size: 22px;
            color: #ffffff;
            border-left: 3px solid #a855f7;
            padding-left: 10px;
        }

        /* Caja del QR (Blanca para que el escáner no falle) */
        .qr-box {
            margin-top: 10px;
            padding: 15px;
            background: #ffffff;
            border-radius: 12px;
            display: inline-block;
        }

        .ticket-code {
            margin-top: 15px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 15px;
            font-weight: bold;
            color: #a855f7;
            /* Código en morado */
            letter-spacing: 2px;
        }

        /* Footer */
        .footer {
            background-color: #111113;
            padding: 20px 30px;
            font-size: 10px;
            color: #71717a;
            text-align: center;
            border-top: 1px solid #27272a;
        }

        /* Badge de Status */
        .badge {
            background-color: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
    </style>
</head>

<body>

    <div class="page-container">
        <div class="ticket-wrapper">

            <div class="header">
                <table class="header-table">
                    <tr>
                        <td class="header-title">MADA MADA</td>
                        <td class="header-logo">
                            ENTRY TICKET<br>
                            <span style="color: #ffffff">#{{ $order->payment_id }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="body-section">
                <table class="layout-table">
                    <tr>
                        <td class="layout-col-left">
                            <div class="label">Event</div>
                            <div class="value value-large">Interstellar Journey 2026</div>

                            <table style="width: 100%; margin-top: 20px;">
                                <tr>
                                    <td>
                                        <div class="label">Passenger</div>
                                        <div class="value">{{ $order->customer_name }}</div>
                                    </td>
                                    <td>
                                        <div class="label">Purchase Date</div>
                                        <div class="value">{{ $order->created_at->format('d M, Y') }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="label">Contact Email</div>
                                        <div class="value">{{ $order->customer_email }}</div>
                                    </td>
                                    <td>
                                        <div class="label">Status</div>
                                        <div class="value"><span class="badge">CONFIRMED</span></div>
                                    </td>
                                </tr>
                            </table>

                            <div style="margin-top: 25px; border-top: 1px solid #27272a; padding-top: 20px;">
                                <div class="label">Total Paid</div>
                                <div class="value" style="font-size: 24px; color: #4ade80;">
                                    ${{ number_format($order->amount, 2) }} USD
                                </div>
                            </div>
                        </td>

                        <td class="layout-col-right">
                            <div class="label">Scan at Entry</div>

                            <div class="qr-box">
                                <img src="data:image/svg+xml;base64, {{ $qr }}" width="130" height="130">
                            </div>

                            <div class="ticket-code">{{ $ticket->ticket_code }}</div>
                            <div style="font-size: 9px; color: #71717a; margin-top: 8px;">Valid for one person</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="footer">
                This document is a digital asset of Galaxy Events Inc. Keep it secure.
                <br>Unauthorized duplication will be detected by our security protocols.
            </div>

        </div>
    </div>

</body>

</html>