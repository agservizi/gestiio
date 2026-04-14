@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio = $record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}">
                @csrf
                @method($record->id ? 'PATCH' : 'POST')

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'cognome','testo'=>'Cognome','required'=>true,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'nome','testo'=>'Nome','required'=>true,'autocomplete'=>'off'])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'codice_fiscale','testo'=>'Codice Fiscale','autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'telefono','testo'=>'Telefono','autocomplete'=>'off'])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'email','testo'=>'Email','autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'carta','testo'=>'Carta prepagata (tipo / numero)','autocomplete'=>'off'])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'iban','testo'=>'IBAN','required'=>true,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'intestatario_iban','testo'=>'Intestatario IBAN','autocomplete'=>'off'])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        @include('Backend._inputs.inputTextArea',['campo'=>'note','testo'=>'Note'])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit" id="submit">
                            {{$vecchio ? 'Salva modifiche' : 'Salva IBAN'}}
                        </button>
                    </div>
                    @if($vecchio)
                        <div class="col-md-4 text-end">
                            @if(isset($eliminabile) && $eliminabile === true)
                                <a class="btn btn-danger mt-3" id="elimina"
                                   href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
                            @endif
                        </div>
                    @endif
                </div>

            </form>
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        $(function () {
            eliminaHandler('Questo IBAN verrà eliminato definitivamente');

            // Formatta IBAN in maiuscolo durante la digitazione
            $('#iban').on('input', function () {
                var val = $(this).val().replace(/\s+/g, '').toUpperCase();
                $(this).val(val);
            });
        });
    </script>
@endpush
