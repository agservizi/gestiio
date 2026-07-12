<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Ricevuta {{ $deposit->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { border-bottom: 2px solid #f59e0b; padding-bottom: 12px; margin-bottom: 20px; }
        .title { font-size: 22px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 8px 0; border-bottom: 1px solid #eee; }
        .total { font-size: 18px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Ricevuta Deposito Bagagli</div>
        <div>{{ config('app.name') }}</div>
    </div>
    <table>
        <tr><td>Codice</td><td><strong>{{ $deposit->code }}</strong></td></tr>
        <tr><td>Cliente</td><td>{{ $deposit->customer_name }}</td></tr>
        <tr><td>Borse</td><td>{{ $deposit->bag_count }}</td></tr>
        <tr><td>Check-in</td><td>{{ $deposit->checked_in_at?->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Check-out</td><td>{{ $deposit->checked_out_at?->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Tariffa/giorno</td><td>€ {{ number_format($deposit->daily_rate, 2, ',', '.') }}</td></tr>
        <tr><td>Metodo pagamento</td><td>{{ $deposit->payment_method }}</td></tr>
    </table>
    <div class="total">Totale: € {{ number_format($deposit->total_amount ?? 0, 2, ',', '.') }}</div>
</body>
</html>
