<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Boarding Ticket</title>
    <style>
        /* Reset básico */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }

        /* Contenedor principal que simula la hoja */
        .page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* El Ticket en sí */
        .ticket-wrapper {
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            /* Para que las esquinas redondeadas funcionen */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Encabezado Azul Oscuro */
        .header {
            background-color: #1e1b4b;
            /* Azul espacial oscuro */
            color: #ffffff;
            padding: 20px 30px;
            border-bottom: 4px solid #fbbf24;
            /* Línea dorada */
        }

        .header-table {
            width: 100%;
        }

        .header-title {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .header-logo {
            text-align: right;
            font-size: 14px;
            opacity: 0.8;
        }

        /* Cuerpo del ticket */
        .body-section {
            padding: 30px;
        }

        /* Usamos tablas para maquetar porque dompdf no soporta Flexbox */
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
            border-left: 2px dashed #ddd;
            padding-left: 20px;
        }

        /* Estilos de etiquetas y datos */
        .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 3px;
            letter-spacing: 1px;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
            color: #1e1b4b;
            margin-bottom: 15px;
        }

        .value-large {
            font-size: 20px;
            color: #000;
        }

        /* Caja del QR */
        .qr-box {
            margin-top: 10px;
            padding: 10px;
            background: #f9fafb;
            border: 1px solid #eee;
            border-radius: 8px;
            display: inline-block;
        }

        .ticket-code {
            margin-top: 10px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            font-weight: bold;
            color: #666;
            letter-spacing: 1px;
        }

        /* Footer */
        .footer {
            background-color: #f9fafb;
            padding: 15px 30px;
            font-size: 10px;
            color: #999;
            text-align: center;
            border-top: 1px solid #eee;
        }

        /* Utilidades */
        .badge {
            background-color: #dcfce7;
            color: #166534;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
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
                            #{{ $order->payment_id }}
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

                            <table style="width: 100%;">
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
                                        <div class="value"><span class="badge">Confirmed</span></div>
                                    </td>
                                </tr>
                            </table>

                            <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                                <div class="label">Total Paid</div>
                                <div class="value" style="font-size: 24px; color: #16a34a;">
                                    ${{ number_format($order->amount, 2) }} USD
                                </div>
                            </div>
                        </td>

                        <td class="layout-col-right">
                            <div class="label">Scan at Entry</div>

                            <div class="qr-box">
                                <img src="data:image/svg+xml;base64, {{ $qr }}" width="140" height="140">
                            </div>

                            <div class="ticket-code">{{ $ticket->ticket_code }}</div>
                            <div style="font-size: 9px; color: #aaa; margin-top: 5px;">Valid for one person</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="footer">
                This ticket is personal and non-transferable. By purchasing it you agree to the terms and conditions of Galaxy Events Inc.
                <br>Built with Laravel and lots of coffee.
            </div>

        </div>
    </div>

</body>

</html>