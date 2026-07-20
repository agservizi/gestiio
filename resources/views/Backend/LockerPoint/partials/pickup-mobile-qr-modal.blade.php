@php($modalId = 'pickupMobileModal-'.$package->id)
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fs-5" id="{{ $modalId }}Label">Ritiro mobile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body text-center pt-2 pb-5">
                <p class="text-muted fs-7 mb-4">
                    Scansiona con lo <strong>smartphone dello sportello</strong> per aprire la pagina di ritiro.
                    Poi completa la consegna al destinatario.
                </p>
                <div class="d-inline-block p-3 bg-white border border-gray-300 rounded">
                    {!! \App\Http\Support\LuggageQrCode::svg($package->pickupUrl(), 240) !!}
                </div>
                <div class="fw-bold mt-4">{{ $package->code }}</div>
                <div class="text-muted fs-7">{{ $package->recipient_name }}@if($package->carrier) · {{ $package->carrier }}@endif</div>
            </div>
        </div>
    </div>
</div>
