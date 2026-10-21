@extends('Backend._layout._main')

@section('content')
    @include('Backend._components.alertMessage')
    <div class="text-muted fs-7 mb-6">Referral peer-to-peer · bonus separato dal plafond</div>

    <div class="row g-5 mb-7">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted fs-7 text-uppercase fw-semibold mb-2">Il tuo codice invite</div>
                    <div class="fs-2hx fw-bold text-primary mb-4">{{ $inviteCode }}</div>
                    <label class="form-label fw-semibold">Link da condividere</label>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-solid" id="invite-url" readonly value="{{ $inviteUrl }}">
                        <button type="button" class="btn btn-primary" id="btn-copy-invite">Copia</button>
                    </div>
                    <div class="text-muted fs-8 mt-3">
                        Bonus previsto: {{ number_format($bonusDefault, 2, ',', '.') }} € quando il nuovo affiliato ha almeno
                        <strong>2 contratti energia OK</strong> e <strong>1 telefonia OK</strong> (esito finale ok / liquidabili).
                        Alla registrazione solo il legame; sotto soglia non si aggancia. Pending fino a conferma admin
                        (ricontrollo soglia). Non va sul portafoglio/plafond.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">Referral attribuiti</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gs-0 gy-3">
                            <thead>
                            <tr class="fw-bold text-muted">
                                <th>Affiliato</th>
                                <th>Importo</th>
                                <th>Stato</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($bonuses as $b)
                                <tr>
                                    <td>
                                        {{ trim(($b->referred->nome ?? '').' '.($b->referred->cognome ?? '')) ?: ($b->referred->email ?? '#'.$b->referred_user_id) }}
                                    </td>
                                    <td>{{ number_format((float)$b->importo, 2, ',', '.') }} €</td>
                                    <td><span class="badge badge-light-{{ $b->stato === 'credited' ? 'success' : 'warning' }}">{{ $b->stato }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">Ancora nessun referral.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('customScript')
<script>
document.getElementById('btn-copy-invite')?.addEventListener('click', function () {
    var el = document.getElementById('invite-url');
    if (!el) return;
    navigator.clipboard.writeText(el.value).then(function () {
        toastr && toastr.success('Link copiato');
    });
});
</script>
@endpush
