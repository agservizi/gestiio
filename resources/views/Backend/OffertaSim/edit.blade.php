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
                        @include('Backend._inputs.inputSelect2',['campo'=>'gestore_id','testo'=>'Gestore','required'=>true,'selected'=>\App\Models\GestoreAttivazioniSim::selected(old('gestore_id',$record->gestore_id))])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'nome','testo'=>'Nome','required'=>true,'autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'prezzo','testo'=>'Prezzo','required'=>true,"classe"=>"autonumericImporto"])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        @include('Backend._inputs.inputTextAreaCol',['campo'=>'descrizione','testo'=>'Descrizione','col'=>2])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSwitch',['campo'=>'attiva','testo'=>'Attiva',])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit" id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\OffertaSim::NOME_SINGOLARE}}</button>
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
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script src="/assets_backend/plugins/custom/ckeditor/ckeditor-classic.bundle.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            autonumericImporto('autonumericImporto');
            select2UniversaleBackend('gestore_id','un gestore',-1,'gestore_sim_id')
            ClassicEditor
                .create(document.querySelector('#descrizione'))
                .then(editor => {
                    console.log(editor);
                })
                .catch(error => {
                    console.error(error);
                });
        });
    </script>
@endpush
