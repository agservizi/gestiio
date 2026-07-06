@extends('Backend._components.modal')
@section('content')
    <form id="form-aggiorna-stato" action="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'aggiornaStato'],$record->id)}}" method="POST">
        <input type="hidden" name="aggiorna" value="{{old('aggiorna',request()->input('aggiorna'))}}">
        <div class="fv-row">
            <label class="d-flex align-items-center fs-5 fw-bold mb-4">
                <span class="required">Stato</span>
            </label>
            <div class="row">
                @php
                    $selected = $record->esito_id;
                @endphp
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
                    @foreach(\App\Models\TabMotivoKo::perModal('caf-patronato')->get()->sortBy('nome') as $motivo)
                        <button type="button" class="motivo_ko" style="font-size: 10px;">{{$motivo->nome}}</button>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="row">
            <div class="col-12 text-center">
                <button class="btn btn-primary mt-3" type="submit">Aggiorna dati</button>
            </div>
        </div>
        <div class="row mt-6">
            <div class="col-lg-2 col-form-label text-lg-end">
                <label class="fw-bold fs-6">Allegati</label>
            </div>
            <div class="col-lg-10 fv-row fv-plugins-icon-container">
                <div class="fv-row">
                    <div class="dropzone gestiio-dropzone" id="kt_dropzonejs_example_1">
                        <div class="dz-message needsclick">
                            <span class="gestiio-dropzone-icon">
                                <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h3>Trascina i file qui o clicca per selezionarli</h3>
                                <span>Documenti finali o allegati cliente della pratica CAF/Patronato.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </form>
@endsection
@include('Backend._components.dropzoneUx')
@push('customScript')
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>

    <script>
        $(function () {
            const esiti =@json(\App\Models\EsitoCafPatronato::select('id','esito_finale')->get()->keyBy('id'));

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


                if (stato.data('motivo') == 1) {
                    $('#div_motivazione').show();
                } else {
                    $('#div_motivazione').hide();
                }
            }

            function cambiaDivMotivazione(stato) {


                if (stato.data('motivo') == 1) {
                    $('#div_motivazione').slideDown();
                } else {
                    $('#div_motivazione').slideUp();
                }
            }

            $('#form-aggiorna-stato').off().submit(function (e) {
                e.preventDefault();
                var url = $(this).attr('action');
                var data = $(this).serialize();
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
                            gestiioToast('error', response.message || 'Impossibile aggiornare lo stato.');
                        }
                    },
                    error: function (resp) {
                        if (resp.status === 422) {
                            var json = $.parseJSON(resp.responseText);
                            $.each(json.errors, function (key, value) {
                                Swal.fire(
                                    "Errore",
                                    value[0][0],
                                    "error"
                                )
                            });
                        }
                    }
                });
            });
            initGestiioDropzone("#kt_dropzonejs_example_1", {
                uploadUrl: "{{action([$controller,'uploadAllegato'])}}",
                deleteUrl: "{{action([$controller,'deleteAllegato'])}}",
                csrfToken: "{{ csrf_token() }}",
                existingFiles: @json(\App\Models\AllegatoCafPatronato::perBlade($uid,$record->id,1)),
                sendingData: {
                    per_cliente: 1,
                    caf_patronato_id: {{$record->id}}
                }
            });


        });
    </script>
@endpush
