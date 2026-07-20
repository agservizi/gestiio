@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex flex-nowrap align-items-center gap-2">
        @can('admin')
            <form method="post" action="{{ action([$controller, 'ricalcola'], $record->id) }}" class="d-inline flex-shrink-0">
                @csrf
                <button type="button" class="btn btn-sm btn-light" onclick="return gestiioAsk(this, 'Ricalcolare la produzione di questo periodo?')">Ricalcola</button>
            </form>
            @php
                $chiudibile = \Carbon\Carbon::createFromDate($record->anno, $record->mese, 1)->lessThan(now()->startOfMonth());
            @endphp
            @if(!$record->fattura_proforma_id && $chiudibile && (float)$record->importo_totale > 0)
                <button type="button" class="btn btn-sm btn-primary flex-shrink-0"
                        onclick="return apriPreviewProforma('{{ action([$controller, 'previewProforma'], $record->id) }}')">
                    Crea proforma
                </button>
            @endif
        @endcan
        @if($record->fattura_proforma_id)
            <a class="btn btn-sm btn-light-primary flex-shrink-0" href="{{ action([\App\Http\Controllers\Backend\FatturaProformaController::class, 'show'], $record->fattura_proforma_id) }}">Vedi proforma</a>
            <a class="btn btn-sm btn-light flex-shrink-0" href="{{ action([\App\Http\Controllers\Backend\FatturaProformaController::class, 'pdf'], $record->fattura_proforma_id) }}" target="_blank" rel="noopener">PDF</a>
        @endif
    </div>
@endsection

@section('content')
    @include('Backend._components.alertMessage')
    <div class="card mb-5">
        <div class="card-body">
            <div class="row g-5">
                <div class="col-md-6">
                    <div class="text-muted fs-7">Agente</div>
                    <div class="fs-4 fw-bold">{{ optional($record->agente)->nominativo() }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-7">Periodo</div>
                    <div class="fs-4 fw-bold">{{ str_pad($record->mese, 2, '0', STR_PAD_LEFT) }}/{{ $record->anno }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-7">Totale</div>
                    <div class="fs-4 fw-bold">{{ importo($record->importo_totale, true) }}</div>
                </div>
            </div>
            <div class="separator my-6"></div>
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle">
                    <tbody>
                    <tr>
                        <td>Contratti telefonia</td>
                        <td class="text-end">{{ importo($record->importo_ordini, true) }}</td>
                    </tr>
                    <tr>
                        <td>Contratti energia</td>
                        <td class="text-end">{{ importo($record->importo_contratti_energia, true) }}</td>
                    </tr>
                    <tr>
                        <td>Servizi finanziari</td>
                        <td class="text-end">{{ importo($record->importo_servizi_finanziari ?? 0, true) }}</td>
                    </tr>
                    <tr>
                        <td>Segnalazioni</td>
                        <td class="text-end">{{ importo($record->importo_segnalazioni ?? 0, true) }}</td>
                    </tr>
                    <tr>
                        <td>Attivazioni SIM</td>
                        <td class="text-end">{{ importo($record->importo_attivazioni_sim ?? 0, true) }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Totale</td>
                        <td class="text-end fw-bolder">{{ importo($record->importo_totale, true) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('Backend.ProduzioneOperatore.partials.preview-modal')
@endsection

@push('customScript')
<script>
    window.apriPreviewProforma = function (url) {
        var modalEl = document.getElementById('modalPreviewProforma');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        $('#preview-proforma-loading').removeClass('d-none');
        $('#preview-proforma-body, #preview-proforma-error, #preview-warning-intestazione').addClass('d-none');
        modal.show();

        $.getJSON(url).done(function (data) {
            $('#preview-proforma-loading').addClass('d-none');
            if (!data.ok) {
                $('#preview-proforma-error').removeClass('d-none').text(data.error || 'Errore');
                return;
            }
            $('#preview-agente').text(data.agente || '');
            $('#preview-periodo').text(data.periodo || '');
            $('#preview-totale').text(data.totale_formattato || '');
            var $tbody = $('#preview-linee').empty();
            (data.linee || []).forEach(function (l) {
                $tbody.append('<tr><td>' + $('<div>').text(l.descrizione).html() + '</td><td class="text-end">' + $('<div>').text(l.imponibile_formattato).html() + '</td></tr>');
            });
            if (data.intestazione_incompleta) {
                $('#preview-warning-intestazione').removeClass('d-none');
            }
            $('#form-crea-proforma').attr('action', data.crea_url);
            $('#preview-proforma-body').removeClass('d-none');
        }).fail(function (xhr) {
            $('#preview-proforma-loading').addClass('d-none');
            var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Anteprima non disponibile';
            $('#preview-proforma-error').removeClass('d-none').text(msg);
        });
        return false;
    };
</script>
@endpush
