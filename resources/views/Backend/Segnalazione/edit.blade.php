@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}">
                @csrf
                @method($record->id?'PATCH':'POST')

                @if(Auth::user()->hasAnyPermission(['admin','operatore']))
                    <div class="row">
                        <div class="col-md-6">
                            @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                        </div>
                    </div>
                @else
                    <input type="hidden" id="agente_id" name="agente_id" value="{{old('agente_id',$record->agente_id)}}">
                @endif

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'nome_azienda','testo'=>'Nome azienda','required'=>true,'autocomplete'=>'off'])
                    </div>

                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'partita_iva','testo'=>'Partita iva','required'=>true,'autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'indirizzo','testo'=>'Indirizzo','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2',['campo'=>'citta','testo'=>'Citta','selected'=>\App\Models\Comune::selected(old('citta',$record->citta))])
                    </div>

                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'cap','testo'=>'Cap','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'telefono','testo'=>'Telefono','required'=>true,'autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'nome_referente','testo'=>'Nome referente','required'=>true,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'cognome_referente','testo'=>'Cognome referente','required'=>true,'autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'email_referente','testo'=>'Email referente','required'=>true,'autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'fatturato','testo'=>'Fatturato','array'=>['<10M'=>'<10M','>10M'=>'<10M','non so'=>'Non so']])
                    </div>

                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'settore','testo'=>'Settore','array'=>\App\Models\Segnalazione::SETTORI])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2',['campo'=>'provincia','testo'=>'Provincia','autocomplete'=>'off','selected'=>\App\Models\Provincia::selected(old('provincia',$record->provincia))])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'forma_giuridica','testo'=>'Forma giuridica','array'=>\App\Models\Segnalazione::NATURE_GIURIDICHE])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit" id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\Segnalazione::NOME_SINGOLARE}}</button>
                    </div>
                    @if($vecchio)
                        <div class="col-md-4 text-end">
                            @if($eliminabile===true)
                                <a class="btn btn-danger mt-3" id="elimina" href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
                            @elseif(is_string($eliminabile))
                                <span data-bs-toggle="tooltip" title="{{$eliminabile}}">
                                    <a class="btn btn-danger mt-3 disabled" href="javascript:void(0)">Elimina</a>
                                </span>
                            @endif
                        </div>
                    @endif
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
            if ($('#agente_id').is("select")) {
                select2UniversaleBackend('agente_id', 'un agente', 1);
            }


            $('#citta').select2({
                placeholder: 'Seleziona una città',
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

            }).on('select2:open', function () {

            });
            $('#provincia').select2({
                placeholder: 'Seleziona una provincia',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/select2?provincia",
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
            });

        });
    </script>
@endpush
