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
            font-family: 'Helvetica', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #09090b;
            color: #ffffff;
        }

        .page-container {
            padding: 40px 20px;
        }

        .ticket-wrapper {
            background-color: #18181b;
            border-radius: 20px;
            border: 1px solid #3f3f46;
        }

        .header {
            background-color: #111113;
            padding: 25px 35px;
            border-bottom: 3px solid #a855f7;
        }

        .header-table {
            width: 100%;
        }

        .header-title {
            font-size: 26px;
            font-weight: bold;
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
            font-family: monospace;
            font-size: 15px;
            font-weight: bold;
            color: #a855f7;
        }

        .footer {
            background-color: #111113;
            padding: 20px;
            font-size: 10px;
            color: #71717a;
            text-align: center;
            border-top: 1px solid #27272a;
        }

        .badge {
            background-color: #22c55e;
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
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
                            <div class="value value-large">The Birthday Bash feat. MADA MADA</div>

                            <table style="width: 100%; margin-top: 20px;">
                                <tr>
                                    <td>
                                        <div class="label">Passenger</div>
                                        <div class="value">{{ $order->customer_name }}</div>
                                    </td>
                                    <td>
                                        <div class="label">Purchase Date</div>
                                        <div class="value">{{ \Carbon\Carbon::parse($order->created_at)->format('d M, Y') }}</div>
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
                                <img src="data:image/svg+xml;base64,{{ $qr }}" width="130" height="130">
                            </div>
                            <div class="ticket-code">{{ $ticketCode }}</div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="footer">Galaxy Events Inc. - Digital Asset</div>
        </div>
    </div>
</body>

</html>