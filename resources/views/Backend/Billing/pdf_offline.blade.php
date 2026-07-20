<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Proforma {{ $record->periodo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
    </style>
</head>
<body>
<h1>{{ $record->labelSource() }} — {{ $record->periodo }}</h1>
<p>Stato: {{ ucfirst($record->status) }} · Totale: {{ number_format((float)$record->totale, 2, ',', '.') }} €</p>
<p><em>Documento generato offline (InvoiceShelf non attivo). Configura INVOICESHELF_* per PDF ufficiali.</em></p>
<table>
    <thead>
    <tr>
        <th>Descrizione</th>
        <th class="right">Qty</th>
        <th class="right">Importo</th>
    </tr>
    </thead>
    <tbody>
    @foreach(($record->meta['items'] ?? []) as $item)
        <tr>
            <td>{{ $item['name'] ?? '' }}</td>
            <td class="right">{{ $item['quantity'] ?? 1 }}</td>
            <td class="right">{{ number_format((float)($item['price'] ?? 0), 2, ',', '.') }} €</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
