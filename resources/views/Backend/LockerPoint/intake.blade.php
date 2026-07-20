@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <a href="{{ action([$controller, 'index']) }}" class="btn btn-sm btn-light">Elenco</a>
    </div>
@endsection

@section('content')
    <div class="card mb-6">
        <div class="card-body">
            <form method="get" action="{{ url('/backend/locker-point/accetta') }}" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Codice pacco</label>
                    <input type="text" name="code" class="form-control form-control-solid" placeholder="LP-XXXXXX" value="{{ request('code') }}">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100">Cerca</button>
                </div>
            </form>
        </div>
    </div>

    @if($package ?? null)
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">{{ $package->code }}</h3>
                <span class="badge {{ $package->status->badgeClass() }}">{{ $package->status->label() }}</span>
            </div>
            <div class="card-body">
                <div class="row g-4 mb-4">
                    <div class="col-6"><div class="text-muted fs-7">Destinatario</div><div class="fw-bold">{{ $package->recipient_name }}</div></div>
                    <div class="col-6"><div class="text-muted fs-7">Mittente</div><div class="fw-bold">{{ $package->sender_name ?: '—' }}</div></div>
                    <div class="col-6"><div class="text-muted fs-7">Corriere</div><div class="fw-bold">{{ $package->carrier ?: '—' }}</div></div>
                    <div class="col-6"><div class="text-muted fs-7">Ritiro previsto</div><div class="fw-bold">{{ $package->expected_pickup_date?->format('d/m/Y') }}</div></div>
                </div>

                @if(in_array($package->status->value, ['PRENOTATO', 'NO_SHOW']))
                    <form method="post"
                          action="{{ action([$controller, 'storeIntake'], $package->id) }}"
                          enctype="multipart/form-data"
                          class="intake-form">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label required fw-bold">Foto del pacco</label>
                            <p class="text-muted fs-7 mb-3">Scatta una foto con la fotocamera posteriore per documentare l'accettazione in giacenza.</p>
                            <input type="file"
                                   name="photo"
                                   accept="image/*"
                                   capture="environment"
                                   class="form-control form-control-lg"
                                   required>
                            @error('photo')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <div id="photo-preview" class="mb-5 d-none">
                            <img src="" alt="Anteprima foto" class="img-fluid rounded border w-100" style="max-height:320px">
                        </div>
                        <button type="submit" class="btn btn-warning btn-lg w-100">Conferma accettazione in giacenza</button>
                    </form>
                @else
                    <div class="alert alert-light-warning mb-0">Questo pacco non è in stato prenotato o no-show.</div>
                @endif
            </div>
        </div>
    @endif

    <div class="card mt-8">
        <div class="card-header"><h3 class="card-title">Prenotati in attesa</h3></div>
        <div class="card-body pt-0">
            @forelse($prenotati ?? [] as $item)
                <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                    <div>
                        <strong>{{ $item->code }}</strong> — {{ $item->recipient_name }}
                        @if($item->carrier)
                            <span class="text-muted">({{ $item->carrier }})</span>
                        @endif
                    </div>
                    <a href="{{ action([$controller, 'intake'], $item->id) }}" class="btn btn-sm btn-light">Seleziona</a>
                </div>
            @empty
                <p class="text-muted py-5 mb-0">Nessun pacco in attesa di accettazione.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('customScript')
<script>
    (function () {
        const input = document.querySelector('input[name="photo"]');
        const preview = document.getElementById('photo-preview');
        if (!input || !preview) return;

        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) {
                preview.classList.add('d-none');
                return;
            }
            const img = preview.querySelector('img');
            img.src = URL.createObjectURL(file);
            preview.classList.remove('d-none');
        });
    })();
</script>
@endpush
