@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            @include('Backend._components.alertMessage')
            <form method="POST" action="{{action([\App\Http\Controllers\Backend\VisuraCameraleController::class,'postCercaAzienda'])}}">
                @csrf
                @method('POST')
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'ragione_sociale','testo'=>'Denominazione','required'=>true,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2',['campo'=>'provincia','testo'=>'Provincia','required'=>true,'selected'=>\App\Models\Provincia::selected(null)])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit" id="submit">Invia</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
@endsection
@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            select2UniversaleBackend('provincia', 'una provincia');

        });
    </script>
@endpush
