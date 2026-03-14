<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Boarding Ticket</title>
    <style>
        @page {
            margin: 0px;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #09090b;
            color: #ffffff;
        }

        .page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .ticket-wrapper {
            background-color: #18181b;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #3f3f46;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .header {
            background-color: #111113;
            color: #ffffff;
            padding: 25px 35px;
            border-bottom: 3px solid #a855f7;
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
            font-weight: bold;
        }

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
            padding-left: 20px;
        }

        .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #a1a1aa;
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
            letter-spacing: 2px;
        }

        .footer {
            background-color: #111113;
            padding: 20px 30px;
            font-size: 10px;
            color: #71717a;
            text-align: center;
            border-top: 1px solid #27272a;
        }

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
                        <td class="header-title">BIRTHDAY PARTY</td>
                        <td class="header-logo">
                            ENTRY TICKET<br>
                            <span style="color: #ffffff">#{{ $order->payment_id ?? 'N/A' }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="body-section">
                <table class="layout-table">
                    <tr>
                        <td class="layout-col-left">
                            <div class="label">Event</div>
                            <div class="value value-large">The Birthday Bash feat. Special Guest MADA MADA</div>

                            <table style="width: 100%; margin-top: 20px;">
                                <tr>
                                    <td>
                                        <div class="label">Passenger</div>
                                        <div class="value">{{ $order->customer_name ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <div class="label">Purchase Date</div>
                                        <div class="value">
                                            @if(isset($order->created_at))
                                            {{ \Carbon\Carbon::parse($order->created_at)->format('d M, Y') }}
                                            @else
                                            {{ date('d M, Y') }}
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="label">Contact Email</div>
                                        <div class="value">{{ $order->customer_email ?? 'N/A' }}</div>
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
                                    ${{ number_format($order->amount ?? 0, 2) }} USD
                                </div>
                            </div>
                        </td>

                        <td class="layout-col-right">
                            <div class="label">Scan at Entry</div>
                            <div class="qr-box">
                                @if(isset($qr) && $qr)
                                <img src="data:image/svg+xml;base64, {{ $qr }}" width="130" height="130" alt="QR Code">
                                @else
                                <div style="width:130px; height:130px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#333; font-size:12px;">QR No Disponible</div>
                                @endif
                            </div>
                            <div class="ticket-code">{{ $ticket->ticket_code ?? $ticketCode ?? 'N/A' }}</div>
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