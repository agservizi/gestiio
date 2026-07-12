<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Ritiro completato — {{ $deposit->code }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:400,600,700">
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:Inter,Arial,sans-serif; background:#ecfdf5; color:#065f46; padding:24px; }
        .card { background:#fff; border-radius:20px; padding:32px 24px; max-width:420px; width:100%; text-align:center; box-shadow:0 12px 40px rgba(6,95,70,.12); }
        h1 { margin:0 0 8px; font-size:1.6rem; }
        .amount { font-size:2rem; font-weight:800; margin:16px 0; }
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size:3rem;line-height:1">✓</div>
        <h1>Ritiro completato</h1>
        <p>{{ $deposit->customer_name }} · {{ $deposit->code }}</p>
        @if($deposit->total_amount)
            <div class="amount">€ {{ number_format($deposit->total_amount, 2, ',', '.') }}</div>
            <p>{{ $deposit->payment_method }} · {{ $deposit->checked_out_at?->format('d/m/Y H:i') }}</p>
        @endif
        <p style="color:#5d6b82;margin-top:20px">I bagagli sono stati consegnati al cliente.</p>
    </div>
</body>
</html>
