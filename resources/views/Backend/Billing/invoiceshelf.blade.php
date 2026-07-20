@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-nowrap align-items-center gap-2">
        <a href="{{ action([$controller, 'index']) }}" class="btn btn-sm btn-light flex-shrink-0">Documenti</a>
        <a href="https://invoice.agenziaplinio.it" class="btn btn-sm btn-light-primary flex-shrink-0" target="_blank" rel="noopener">Apri InvoiceShelf</a>
    </div>
@endsection

@section('content')
    @include('Backend._components.alertMessage')

    <div class="alert alert-light border mb-5">
        Scarica l’XML <strong>FatturaPA</strong> da caricare nel tuo software di fatturazione elettronica
        (firma e invio allo SDI / Agenzia delle Entrate avvengono lì).
        Per ogni cliente su InvoiceShelf imposta <code>tax_id</code> (P.IVA o CF) e, se possibile,
        campi custom <code>codice_destinatario</code> / <code>pec</code>.
    </div>

    @if($error)
        <div class="alert alert-warning">{{ $error }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                    <tr class="fw-bolder">
                        <th>IS #</th>
                        <th>Numero</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Stato</th>
                        <th class="text-end">Totale</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($invoices as $inv)
                        @php
                            $cust = $inv['customer'] ?? [];
                            $custName = $cust['company_name'] ?? $cust['name'] ?? '—';
                        @endphp
                        <tr>
                            <td>{{ $inv['id'] ?? '' }}</td>
                            <td>{{ $inv['invoice_number'] ?? '—' }}</td>
                            <td>{{ $inv['formatted_invoice_date'] ?? ($inv['invoice_date'] ?? '—') }}</td>
                            <td>{{ $custName }}</td>
                            <td>{{ $inv['status'] ?? '—' }}</td>
                            <td class="text-end">
                                @if(isset($inv['total']))
                                    {!! importo($inv['total'], true) !!}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-light-success"
                                   href="{{ action([$controller, 'exportInvoiceShelfXml'], $inv['id']) }}">
                                    XML FE
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">Nessuna fattura su InvoiceShelf.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if(($page ?? 1) > 1 || count($invoices) >= 30)
                <div class="d-flex justify-content-between mt-4">
                    @if(($page ?? 1) > 1)
                        <a class="btn btn-sm btn-light" href="?page={{ $page - 1 }}">← Precedente</a>
                    @else
                        <span></span>
                    @endif
                    @if(count($invoices) >= 30)
                        <a class="btn btn-sm btn-light" href="?page={{ ($page ?? 1) + 1 }}">Successiva →</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
