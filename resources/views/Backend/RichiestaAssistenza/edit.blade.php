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
                @php($clienteInline=$clienteInline??new \App\Models\ClienteAssistenza())
                @php($showClienteInline = old('cliente_codice_fiscale') || old('cliente_nome') || old('cliente_cognome') || old('cliente_email') || old('cliente_telefono'))
                <div class="row" id="cliente-esistente-section">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2',['campo'=>'cliente_id','testo'=>'Cliente (se già presente)','selected'=>\App\Models\ClienteAssistenza::selected(old('cliente_id',$record->cliente_id))])
                        <div class="form-text" id="cliente-esistente-hint" style="display:none">Modalità nuovo cliente attiva: questo campo è disabilitato.</div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check form-check-custom form-check-solid mt-4">
                            <input class="form-check-input" type="checkbox" id="cliente_non_presente" @if($showClienteInline) checked @endif>
                            <label class="form-check-label ms-2" for="cliente_non_presente">Cliente non presente: crea nuovo cliente</label>
                        </div>
                    </div>
                </div>
                <div class="row" id="cliente-inline-hint" @if(!$showClienteInline) style="display:none" @endif>
                    <div class="col-md-12">
                        <div class="form-text mb-3">Compila almeno Codice Fiscale, Nome e Cognome.</div>
                    </div>
                </div>

                <div id="cliente-inline-section" @if(!$showClienteInline) style="display:none" @endif>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info py-3">
                                I dati cliente inseriti qui sotto verranno creati o aggiornati automaticamente al salvataggio.
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            @include('Backend._inputs.inputText',['campo'=>'cliente_codice_fiscale','testo'=>'Codice fiscale cliente','autocomplete'=>'off','record'=>(object)['cliente_codice_fiscale'=>old('cliente_codice_fiscale',$clienteInline->codice_fiscale)]])
                        </div>
                        <div class="col-md-6">
                            @include('Backend._inputs.inputText',['campo'=>'cliente_telefono','testo'=>'Telefono cliente','autocomplete'=>'off','record'=>(object)['cliente_telefono'=>old('cliente_telefono',$clienteInline->telefono)]])
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            @include('Backend._inputs.inputText',['campo'=>'cliente_nome','testo'=>'Nome cliente','autocomplete'=>'off','record'=>(object)['cliente_nome'=>old('cliente_nome',$clienteInline->nome)]])
                        </div>
                        <div class="col-md-6">
                            @include('Backend._inputs.inputText',['campo'=>'cliente_cognome','testo'=>'Cognome cliente','autocomplete'=>'off','record'=>(object)['cliente_cognome'=>old('cliente_cognome',$clienteInline->cognome)]])
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            @include('Backend._inputs.inputText',['campo'=>'cliente_email','testo'=>'Email cliente','autocomplete'=>'off','record'=>(object)['cliente_email'=>old('cliente_email',$clienteInline->email)]])
                        </div>
                    </div>
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
                                id="submit">{{$vecchio?'Salva richiesta assistenza':'Crea richiesta assistenza'}}</button>
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

            function syncClienteInlineSection() {
                const useInline = $('#cliente_non_presente').is(':checked');

                $('#cliente-inline-section').toggle(useInline);
                $('#cliente-inline-hint').toggle(useInline);
                $('#cliente_codice_fiscale, #cliente_nome, #cliente_cognome').prop('required', useInline);
                $('#cliente_id').prop('disabled', useInline);
                $('#cliente_id').trigger('change.select2');
                $('#cliente-esistente-section').toggleClass('opacity-50', useInline);
                $('#cliente-esistente-hint').toggle(useInline);
            }

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
                $('#cliente_non_presente').prop('checked', false);
                syncClienteInlineSection();

            }).on('select2:open', function () {

            }).on('select2:clear', function () {
                syncClienteInlineSection();
            });

            $('#cliente_non_presente').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#cliente_id').val(null).trigger('change');
                }
                syncClienteInlineSection();
            });

            $('#prodotto_assistenza_id').select2({
                placeholder: 'Seleziona un prodotto assistenza',
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

            syncClienteInlineSection();

        });
    </script>
@endpush
