@extends('Backend._components.modal')
@section('content')
    <form id="form-aggiorna-stato"
          action="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'aggiornaStato'],$record->id)}}"
          method="POST">
        @csrf
        <input type="hidden" name="aggiorna" value="{{old('aggiorna',request()->input('aggiorna'))}}">
        <input type="hidden" name="ruolo" value="supervisore">
        <div id="form-aggiorna-stato-feedback" class="alert alert-danger d-none mb-4"></div>
        <div class="fv-row">
            <label class="d-flex align-items-center fs-5 fw-bold mb-4">
                <span class="required">Stato</span>
            </label>
            <div class="row g-3">
                @php($selected=$record->esito_id)
                @foreach($stati as $stato)
                    <div class="col-6">
                        <label class="d-flex flex-stack mb-1 cursor-pointer rounded-2"
                               style="padding: 10px; background-color: {{$stato->colore_hex}}; color: white;">
                            <span class="d-flex align-items-center me-2">
                                <span class="d-flex flex-column ">
                                    <span class="fw-bolder fs-6 " id="testo_{{$stato->id}}">{{$stato->nome}}</span>
                                </span>
                            </span>
                            <span class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input stato" type="radio" name="esito_id"
                                       value="{{$stato->id}}" {{$selected==$stato->id?'checked':''}}
                                       data-motivo="{{$stato->chiedi_motivo}}"
                                       data-esito-name="{{$stato->nome}}"
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
                <div class="d-flex flex-wrap gap-2 mt-2">
                    @foreach(\App\Models\TabMotivoKo::perModal('contratto')->get()->sortBy('nome') as $motivo)
                        <button type="button" class="btn btn-sm btn-light-primary motivo_ko">{{$motivo->nome}}</button>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="w-100 mt-6">
            @include('Backend._inputs.inputText',['campo'=>'codice_cliente','testo'=>'Codice cliente','required'=>false,'autocomplete'=>'off'])
            @include('Backend._inputs.inputText',['campo'=>'codice_contratto','testo'=>'Codice contratto','required'=>false,'autocomplete'=>'off'])
        </div>
        <div class="row">
            <div class="col-12 text-center">
                <button class="btn btn-primary mt-3" type="submit" id="btn-aggiorna-stato">
                    <span class="indicator-label">Aggiorna dati</span>
                    <span class="indicator-progress">Invio in corso...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </form>
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
                            <span>Allegati collegati allo stato della pratica telefonia.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@include('Backend._components.dropzoneUx')
@push('customScript')

    <script>
        $(function () {
            const $form = $('#form-aggiorna-stato');
            const $feedback = $('#form-aggiorna-stato-feedback');
            const $submit = $('#btn-aggiorna-stato');
            const $motivo = $('#motivo_ko');
            const $divMotivazione = $('#div_motivazione');

            impostaDivMotivazione($('.stato:checked'));
            $('.stato').click(function () {
                impostaDivMotivazione($(this));
                nascondiErrore();
            });

            $('.motivo_ko').off();
            $('.motivo_ko').click(function () {
                $motivo.val($(this).text()).trigger('input').focus();
            });

            function impostaDivMotivazione(stato) {
                const serveMotivo = Number(stato.data('motivo') || 0) === 1;
                if (serveMotivo) {
                    $divMotivazione.slideDown();
                    $motivo.attr('required', 'required');
                } else {
                    $divMotivazione.slideUp();
                    $motivo.removeAttr('required');
                }
            }

            function mostraErrore(message) {
                $feedback.removeClass('d-none').text(message);
            }

            function nascondiErrore() {
                $feedback.addClass('d-none').text('');
            }

            function setLoading(isLoading) {
                if (isLoading) {
                    $submit.attr('data-kt-indicator', 'on').prop('disabled', true);
                } else {
                    $submit.removeAttr('data-kt-indicator').prop('disabled', false);
                }
            }

            function validaClient() {
                const $selected = $('.stato:checked');
                if (!$selected.length) {
                    mostraErrore('Seleziona uno stato prima di salvare.');
                    return false;
                }
                const serveMotivo = Number($selected.data('motivo') || 0) === 1;
                if (serveMotivo && !String($motivo.val() || '').trim()) {
                    mostraErrore('La motivazione KO è obbligatoria per lo stato selezionato.');
                    return false;
                }
                return true;
            }

            $form.off().submit(function (e) {
                e.preventDefault();
                nascondiErrore();
                if (!validaClient()) {
                    return;
                }

                const url = $(this).attr('action');
                const data = $(this).serialize();
                setLoading(true);
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
                            if (typeof KTApp !== 'undefined' && typeof KTApp.createInstances === 'function') {
                                KTApp.createInstances();
                            }
                        } else {
                            mostraErrore(response.message || 'Non è stato possibile aggiornare lo stato.');
                        }
                    },
                    error: function (xhr) {
                        const response = xhr.responseJSON || {};
                        if (response.errors) {
                            const firstKey = Object.keys(response.errors)[0];
                            const firstMessage = firstKey ? response.errors[firstKey][0] : null;
                            mostraErrore(firstMessage || 'Errore di validazione.');
                            return;
                        }
                        mostraErrore(response.message || ('Errore ' + xhr.status + ' durante l\'aggiornamento.'));
                    },
                    complete: function () {
                        setLoading(false);
                    }
                });
            });
            initGestiioDropzone("#kt_dropzonejs_example_1", {
                uploadUrl: "{{action([$controller,'uploadAllegato'])}}",
                deleteUrl: "{{action([$controller,'deleteAllegato'])}}",
                csrfToken: "{{ csrf_token() }}",
                existingFiles: @json(\App\Models\AllegatoContratto::perBlade($uid,$record->id)),
                sendingData: {
                    uid: function () { return $('#uid').val(); },
                    contratto_id: {{$record->id}}
                }
            });


        });
    </script>
@endpush
