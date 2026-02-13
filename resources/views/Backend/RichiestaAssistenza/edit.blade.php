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
                        @include('Backend._inputs.inputSelect2',['campo'=>'cliente_id','testo'=>'Cliente','required'=>true,'selected'=>\App\Models\ClienteAssistenza::selected(old('prodotto_assistenza_id',$record->prodotto_assistenza_id))])
                    </div>
                    @if(!$record->id)
                        <div class="col-md-6">
                            <a class="btn btn-sm btn-primary fw-bold" data-targetZ="kt_modal" data-toggleZ="modal-ajax" href="{{action([\App\Http\Controllers\Backend\ClienteAssistenzaController::class,'create'])}}"><span class="d-md-none">+</span><span
                                        class="d-none d-md-block">Nuovo cliente</span></a>

                        </div>
                    @endif
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2',['campo'=>'prodotto_assistenza_id','testo'=>'Prodotto assistenza','required'=>true,'selected'=>\App\Models\ProdottoAssistenza::selected(old('prodotto_assistenza_id',$record->prodotto_assistenza_id))])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'nome_utente','testo'=>'Nome utente','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'password','testo'=>'Password','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'pin','testo'=>'Pin','autocomplete'=>'off'])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit"
                                id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\RichiestaAssistenza::NOME_SINGOLARE}}</button>
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
        urlSelect2 = '{{action([\App\Http\Controllers\Backend\Select2::class,'response'])}}';
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            $('#cliente_id').select2({
                placeholder: 'Seleziona un cliente',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: urlSelect2 + "?cliente_assistenza",
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

            $('#prodotto_assistenza_id').select2({
                placeholder: 'Seleziona una prodotto assistenza',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: urlSelect2 + "?prodotto_assistenza",
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

        });
    </script>
@endpush
