@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    <div class="d-flex flex-column flex-center">
        <div class="card mw-650px w-100">
            <div class="card-body p-10 p-lg-15">
                @include('Backend._components.alertMessage')
                @include('Backend._components.alertErrori')

                <div class="text-center mb-8">
                    <span class="svg-icon svg-icon-3tx svg-icon-primary mb-4 d-inline-block">
                        <i class="ki-duotone ki-rocket fs-3tx text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </span>
                    <h1 class="fw-bolder text-gray-900 mb-3">Il tuo account non ha ancora servizi attivi</h1>
                    <div class="text-gray-600 fs-6">
                        Per iniziare a lavorare seleziona almeno un servizio: verrà attivato subito sul tuo profilo, senza bisogno di attendere un amministratore.
                    </div>
                </div>

                <form method="POST" action="{{ route('attiva-servizio.richiedi') }}">
                    @csrf
                    <div class="row g-3 mb-8">
                        @foreach($servizi as $nome => $etichetta)
                            <div class="col-md-6">
                                <label class="form-check form-check-custom form-check-solid border rounded p-4 d-flex align-items-center cursor-pointer">
                                    <input class="form-check-input me-3" type="checkbox" name="servizi[]" value="{{ $nome }}">
                                    <span class="form-check-label fw-semibold">{{ $etichetta }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="ki-duotone ki-rocket fs-3 me-2"><span class="path1"></span><span class="path2"></span></i>
                            Attiva i servizi selezionati
                        </button>
                    </div>
                </form>

                <div class="text-center text-muted fs-7 mt-8">
                    Potrai gestire o modificare i servizi attivi in qualsiasi momento dal tuo profilo.
                </div>
            </div>
        </div>
    </div>
@endsection
