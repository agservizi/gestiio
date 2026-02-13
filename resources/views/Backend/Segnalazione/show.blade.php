@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            @csrf
            @method($record->id?'PATCH':'POST')

            @if(Auth::user()->hasAnyPermission(['admin','operatore']))
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'valore'=>$record->agente->nominativo()])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'caricato_da_user_id','testo'=>'Caricato da','valore'=>$record->caricatoDa?->nominativo()])
                </div>
            @else
                <input type="hidden" id="agente_id" name="agente_id" value="{{old('agente_id',$record->agente_id)}}">
            @endif

            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'nome_azienda','testo'=>'Nome azienda','required'=>true,'autocomplete'=>'off'])
                </div>

                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'partita_iva','testo'=>'Partita iva','required'=>true,'autocomplete'=>'off'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo','testo'=>'Indirizzo','autocomplete'=>'off'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'citta','testo'=>'Citta','valore'=>\App\Models\Comune::find($record->citta)?->comuneConTarga()])
                </div>

                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'cap','testo'=>'Cap','autocomplete'=>'off'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'telefono','testo'=>'Telefono','required'=>true,'autocomplete'=>'off'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'nome_referente','testo'=>'Nome referente','required'=>true,'autocomplete'=>'off'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'cognome_referente','testo'=>'Cognome referente','required'=>true,'autocomplete'=>'off'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'email_referente','testo'=>'Email referente','required'=>true,'autocomplete'=>'off'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'fatturato','testo'=>'Fatturato','array'=>['<10M'=>'<10M','>10M'=>'<10M','non so'=>'Non so']])
                </div>

                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'settore','testo'=>'Settore','array'=>\App\Models\Segnalazione::SETTORI])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'provincia','testo'=>'Provincia','valore'=>\App\Models\Provincia::find($record->provincia)?->provincia])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'forma_giuridica','testo'=>'Forma giuridica','array'=>\App\Models\Segnalazione::NATURE_GIURIDICHE])
                </div>
            </div>

        </div>
    </div>
@endsection
@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {


        });
    </script>
@endpush
