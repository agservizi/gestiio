@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([\App\Http\Controllers\Backend\VisuraCameraleController::class,'postCercaAzienda'])}}">
                @csrf
                @method('POST')

                <div class="table-responsive">
                    <table class="table table-row-bordered ">
                        <thead>
                        <tr class="fw-bolder fs-6 text-gray-800">
                            <th>Denominazione</th>
                            <th>Comune</th>
                            <th>Natura giuridica</th>
                            <th class="text-end">
                            </th>
                        </tr>
                        </thead>
                        <tbody id="t-body">
                        @foreach($imprese as $impresa)
                            <tr>

                                <td>
                                    {{$impresa->denominazione}}
                                </td>
                                <td>
                                    {{$impresa->comune}}
                                </td>
                                <td>
                                    {{$impresa->codice_natura_giuridica}}
                                </td>
                            </tr>

                        @endforeach
                        </tbody>
                    </table>
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

            $('#citta').select2({
                placeholder: 'Seleziona una citta',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/select2?citta",
                    dataType: 'json',
                    data: function (term, page) {
                        return {
                            term: term.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    }
                }
            }).on('select2:select', function (e) {
                // Access to full data
                //$("#cap").val(e.params.data.cap);

            });

        });
    </script>
@endpush
