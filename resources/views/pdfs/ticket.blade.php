<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tu Ticket Espacial</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            color: #333;
        }

        .ticket-box {
            border: 2px dashed #6b21a8;
            /* Morado */
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .header h1 {
            color: #6b21a8;
            margin: 0;
            text-transform: uppercase;
        }

        .info {
            margin-top: 20px;
        }

        .info p {
            font-size: 14px;
            margin: 5px 0;
        }

        .qr-container {
            text-align: center;
            margin-top: 30px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #777;
            margin-top: 30px;
        }

        .badge {
            background: #6b21a8;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="ticket-box">
        <div class="header">
            <h1>Entrada Confirmada</h1>
            <p>Evento Galáctico 2024</p>
        </div>

        <div class="info">
            <p><strong>Asistente:</strong> {{ $order->customer_name }}</p>
            <p><strong>Email:</strong> {{ $order->customer_email }}</p>
            <p><strong>Fecha de Compra:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Estado:</strong> <span class="badge">PAGADO</span></p>
            <p><strong>Precio:</strong> ${{ number_format($order->amount, 2) }} USD</p>
        </div>

        <div class="qr-container">
            <img src="data:image/svg+xml;base64, {{ $qr }}" width="150" height="150">
            <p style="letter-spacing: 2px; font-weight: bold;">{{ $ticket->ticket_code }}</p>
        </div>

        <div class="footer">
            <p>Presenta este código QR en la entrada.</p>
            <p>ID de Transacción: {{ $order->payment_id }}</p>
        </div>
    </div>
</body>

</html>