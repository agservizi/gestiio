@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}" enctype="multipart/form-data">
                @csrf
                @method($record->id?'PATCH':'POST')
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'nome','testo'=>'Nome','required'=>true,'autocomplete'=>'off'])
                    </div>
                    @if(Auth::id()==1)
                        <div class="col-md-6">
                            @include('Backend._inputs.inputText',['campo'=>'model_prodotto','testo'=>'Model prodotto','autocomplete'=>'off'])
                        </div>
                    @endif
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'url','testo'=>'Url','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputColor',['campo'=>'colore_hex','testo'=>'Colore',])
                    </div>
                    <div class="col-md-6 text-center">
                        @include("Backend._inputs.inputLogo",["campo"=>"logo","testo"=>"Logo","required"=>false,"autocomplete"=>"off",'immagine'=>$record->logo?$record->immagineLogo():null])
                    </div>
                </div>

                <h5>Email al gestore</h5>
                <div class="row">
                    <div class="col-md-12">
                        @include('Backend._inputs.inputText',['campo'=>'email_notifica_a_gestore','testo'=>'Indirizzo email notifica a gestore','autocomplete'=>'off','help'=>'Separare gli indirizzi con ;'])
                    </div>
                    <div class="col-md-12">
                        @include('Backend._inputs.inputText',['campo'=>'titolo_notifica_a_gestore','testo'=>'Titolo email notifica a gestore','autocomplete'=>'off','help'=>'Al testo inserito verrà aggiunto: - [nome tipo contratto] per [cognome nome]'])
                    </div>
                    <div class="col-md-12">
                        @include('Backend._inputs.inputText',['campo'=>'testo_notifica_a_gestore','testo'=>'Testo email notifica a gestore','autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6 offset-2">
                        @include('Backend._inputs.inputSwitch',['campo'=>'includi_dati_contratto','testo'=>'Includi dati contratto',])
                    </div>
                </div>

                <h5>Provvigioni agenti</h5>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'importo_contratto','testo'=>'Importo a contratto','required' => true,'classe' => 'importo'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'importo_pagamento_bollettino','testo'=>'Importo a contratto bollettino','classe' => 'importo'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSwitch',['campo'=>'attivo','testo'=>'Attivo',])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit" id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\Gestore::NOME_SINGOLARE}}</button>
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
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
            autonumericImporto('importo');
        });
    </script>
@endpush
