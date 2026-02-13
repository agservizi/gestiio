@extends('Backend._components.modal')
@section('content')
    <form id="form-aggiorna-stato" action="{{action([\App\Http\Controllers\Backend\ServizioFinanziarioController::class,'aggiornaStato'],$record->id)}}" method="POST">
        <input type="hidden" name="aggiorna" value="{{old('aggiorna',request()->input('aggiorna'))}}">
        <div class="fv-row">
            <label class="d-flex align-items-center fs-5 fw-bold mb-4">
                <span class="required">Stato</span>
            </label>
            <div class="row">
                @php($selected=$record->esito_id)
                @foreach($stati as $stato)
                    <div class="col-6">
                        <label class="d-flex flex-stack mb-1 cursor-pointer rounded-2" style="padding: 10px; background-color: {{$stato->colore_hex}}; color: white;">
                            <span class="d-flex align-items-center me-2">
                                <span class="d-flex flex-column ">
                                    <span class="fw-bolder fs-6 " id="testo_{{$stato->id}}">{{$stato->nome}}</span>
                                </span>
                            </span>
                            <span class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input stato" type="radio" name="esito_id" value="{{$stato->id}}" {{$selected==$stato->id?'checked':''}}
                                data-motivo="{{$stato->chiedi_motivo}}"
                                >
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
        <div id="div_motivazione" class="mb-2">
            @include('Backend._inputs.inputTextArea',['campo'=>'motivo_ko','testo'=>'Motivazione ko','required'=>false,'autocomplete'=>'off'])
            @if(!$record->motivo_ko)
                <div style="margin-top: -15px;">
                    @foreach(\App\Models\TabMotivoKo::perModal('servizio-finanziario')->get()->sortBy('nome') as $motivo)
                        <button type="button" class="motivo_ko" style="font-size: 10px;">{{$motivo->nome}}</button>
                    @endforeach
                </div>
            @endif
        </div>
        <div id="pagamento">
            @include('Backend._inputs.inputSwitch',['campo'=>'pagato','testo'=>'Pagato'])
            <div class="row">
                <div class="col-md-6">  @include('Backend._inputs_v.inputText',['campo'=>'provvigione_agenzia','testo'=>'Provvigione agenzia','classe'=>'importo'])</div>
                @if($record->agente_id!=Auth::id())
                    <div class="col-md-6"> @include('Backend._inputs_v.inputText',['campo'=>'provvigione_agente','testo'=>'Provvigione agente','classe'=>'importo'])</div>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-12 text-center">
                <button class="btn btn-primary mt-3" type="submit">Aggiorna dati</button>
            </div>
        </div>
    </form>
@endsection
@push('customScript')
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>

    <script>
        $(function () {
            const esiti =@json(\App\Models\EsitoServizioFinanziario::select('id','esito_finale')->get()->keyBy('id'));

            autonumericImporto('importo');
            impostaDivMotivazione($('.stato:checked'));
            $('.stato').click(function () {
                cambiaDivMotivazione($(this));
            });

            $('.motivo_ko').off();
            $('.motivo_ko').click(function () {
                $('#motivo_ko').val($(this).text());
                if ($('#motivo_ko').val() === '') {

                }
            });

            function impostaDivMotivazione(stato) {
                if (esiti[stato.val()].esito_finale == 'ko') {
                    $('#pagamento').hide();
                } else {
                    $('#pagamento').show();
                }


                if (stato.data('motivo') == 1) {
                    $('#div_motivazione').show();
                } else {
                    $('#div_motivazione').hide();
                }
            }
            function cambiaDivMotivazione(stato) {
                if (esiti[stato.val()].esito_finale == 'ko') {
                    $('#pagamento').slideUp();
                } else {
                    $('#pagamento').slideDown();
                }


                if (stato.data('motivo') == 1) {
                    $('#div_motivazione').slideDown();
                } else {
                    $('#div_motivazione').slideUp();
                }
            }

            $('#form-aggiorna-stato').off().submit(function (e) {
                e.preventDefault();
                var url = $(this).attr('action');
                console.log(url);
                var data = $(this).serialize();
                console.log(data);
                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: data,
                    success: function (response) {
                        if (response.success) {
                            $('#tr_' + response.id).replaceWith(base64_decode(response.html));
                            $('#kt_modal').modal('hide');
                            modalAjax();
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });

        });
    </script>
@endpush
