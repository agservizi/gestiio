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
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'nome','testo'=>'Nome','required'=>true,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2',['campo'=>'gestore_id','testo'=>'Gestore','required'=>true,'selected'=>\App\Models\Gestore::selected(old('gestore_id',$record->gestore_id))])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'durata_contratto','testo'=>'Durata contratto','classe' => 'intero','help'=>'Durata in mesi'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'email_notifica_gestore','testo'=>'Email notifica gestore','help'=>'Se presente ha priorità su quella gestore'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSwitch',['campo'=>'attivo','testo'=>'Attivo',])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'prodotto','testo'=>'Prodotto','array'=>['ProdottoWindtre'=>'ProdottoWindtre'],'altro' => 'data-allow-clear="true"'])
                    </div>
                </div>
                @if(Auth::id()==1)
                    <h4>Hidden</h4>
                    <div class="row">

                        <div class="col-md-6">
                            @include('Backend._inputs.inputText',['campo'=>'pda','testo'=>'pda','autocomplete'=>'off'])
                        </div>
                        <div class="col-md-6">
                            @include('Backend._inputs.inputSwitch',['campo'=>'crea_in_bozza','testo'=>'Crea in bozza'])
                        </div>
                    </div>
                @endif
                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit" id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\TipoContratto::NOME_SINGOLARE}}</button>
                    </div>
                    @if($vecchio)
                        <div class="col-md-4 text-end">
                            @if($eliminabile===true)
                                <a class="btn btn-danger mt-3" id="elimina" href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
                            @elseif(is_string($eliminabile))
                                <span data-bs-toggle="tooltip" title="{{$eliminabile}}" aria-label="{{$eliminabile}}">
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
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
            select2UniversaleBackend('gestore_id', ' un gestore', -1);
            autonumericIntero('intero');
        });
    </script>
@endpush
