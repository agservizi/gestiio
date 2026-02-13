@extends('Backend._layout._main')
@section('toolbar')
@endsection
@section('content')
    <div class="card pt-4">
        <div class="card-body pt-0 pb-5 fs-6" id="tabella">
            <div class="table-responsive">
                <form method="POST"
                      action="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'updateRicaricoAgenti'])}}">
                    @csrf
                    @method('POST')
                    <table class="table table-row-bordered" id="tabella-elenco">
                        <thead>
                        <tr class="fw-bolder fs-6 text-gray-800">

                            <th class="">Agente</th>
                            <th class="">Variazione prezzo %</th>

                        </tr>
                        </thead>
                        <tbody>
                        @foreach($records as $record)
                            <tr class="">
                                <td class="">{{$record->aliasAgente()}}</td>
                                <td class=""><input type="text"
                                                    class="form-control form-control-solid importo form-control-sm mw-175px"
                                                    name="prezzo{{$record->id}}"
                                                    value="{{$record->agente->variazione_prezzi_spedizioni}}"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div class="w-100 text-center">
                        <button class="btn btn-primary mt-3" type="submit" id="submit">Salva modifiche</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
@push('customScript')
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>

    <script>
        var indexUrl = '{{action([$controller,'index'])}}';
        var array = [];

        $(function () {
            autonumericImporto('importo');
        });
    </script>
@endpush
