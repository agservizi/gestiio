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
                    <div class="col-md-6 mb-8">
                        <label class="form-label required">Destinatari principali</label>
                        <select name="destinatario" id="destinatario" class="form-select">
                            <option value="agente" @selected(old('destinatario', $record->destinatario ?? 'agente')==='agente')>Agenti</option>
                            <option value="operatore" @selected(old('destinatario', $record->destinatario ?? 'agente')==='operatore')>Operatori</option>
                            <option value="admin" @selected(old('destinatario', $record->destinatario ?? 'agente')==='admin')>Admin</option>
                            <option value="tutti" @selected(old('destinatario', $record->destinatario ?? 'agente')==='tutti')>Tutti gli utenti</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-8">
                        <label class="form-label">Email aggiuntive</label>
                        <input type="text" name="emails_aggiuntive" id="emails_aggiuntive" class="form-control"
                               value="{{ old('emails_aggiuntive') }}" placeholder="esempio@dominio.it; altro@dominio.it">
                        <div class="form-text">Separate da virgola, punto e virgola o spazio.</div>
                    </div>
                    <div class="col-md-12 mb-12">
                        @include('Backend._inputs.inputText',['campo'=>'titolo','testo'=>'Titolo','required'=>true,'col'=>2])
                    </div>
                    <div class="col-md-12 mb-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label mb-0">Contenuto notifica</label>
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" id="modalitaSorgente">
                                <label class="form-check-label ms-2" for="modalitaSorgente">Modalità sorgente HTML</label>
                            </div>
                        </div>
                        @include('Backend._inputs.inputTextAreaCol',['campo'=>'testo','testo'=>'Testo','col'=>2])
                    </div>
                </div>

                <div class="separator my-8"></div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-bordered">
                            <div class="card-header">
                                <h3 class="card-title">Anteprima notifica</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3"><strong>Target:</strong> <span id="previewDestinatario">-</span></div>
                                <h4 id="previewTitolo" class="mb-4">(titolo)</h4>
                                <iframe id="previewFrame" style="width:100%; min-height:260px; border:1px solid #e4e6ef; border-radius:6px; background:#fff;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit" id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\Notifica::NOME_SINGOLARE}}</button>
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
    <script src="/assets_backend/js-progetto/ckeditor5/build/ckeditor.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            let editorInstance = null;
            let sourceMode = false;
            const etichetteDestinatario = {
                agente: 'Agenti',
                operatore: 'Operatori',
                admin: 'Admin',
                tutti: 'Tutti gli utenti'
            };

            const decodeIfEscaped = (value) => {
                if (!value) {
                    return '';
                }

                if (value.includes('&lt;') || value.includes('&gt;') || value.includes('&amp;')) {
                    return $('<textarea/>').html(value).text();
                }

                return value;
            };

            const sanitizePreview = (value) => {
                return value.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
            };

            const renderPreviewFrame = (rawHtml) => {
                const html = sanitizePreview(decodeIfEscaped(rawHtml || '(testo)'));
                const frame = document.getElementById('previewFrame');

                if (!frame) {
                    return;
                }

                frame.srcdoc = '<!doctype html><html><head><meta charset="utf-8">'
                    + '<style>body{font-family:Arial,Helvetica,sans-serif;padding:12px;margin:0;font-size:14px;line-height:1.45;color:#2f3044;}table{max-width:100%;}img{max-width:100%;height:auto;}</style>'
                    + '</head><body>' + html + '</body></html>';
            };

            const createEditor = () => {
                return ClassicEditor
                    .create(document.querySelector('#testo'))
                    .then(editor => {
                        editorInstance = editor;
                        editor.model.document.on('change:data', aggiornaAnteprima);
                        aggiornaAnteprima();
                    })
                    .catch(error => {
                        console.error(error);
                    });
            };

            const setSourceMode = async (enabled) => {
                sourceMode = enabled;

                if (enabled) {
                    if (editorInstance) {
                        const html = editorInstance.getData();
                        await editorInstance.destroy();
                        editorInstance = null;
                        $('#testo').val(html).show();
                    }
                } else {
                    await createEditor();
                }

                aggiornaAnteprima();
            };

            const aggiornaAnteprima = () => {
                const destinatario = $('#destinatario').val() || 'agente';
                const titolo = $('#titolo').val() || '(titolo)';
                const testo = editorInstance ? editorInstance.getData() : ($('#testo').val() || '(testo)');

                $('#previewDestinatario').text(etichetteDestinatario[destinatario] || destinatario);
                $('#previewTitolo').text(titolo);
                renderPreviewFrame(testo);
            };

            createEditor();

            $('#destinatario, #titolo').on('change keyup', aggiornaAnteprima);
            $('#testo').on('keyup change', aggiornaAnteprima);
            $('#modalitaSorgente').on('change', function () {
                setSourceMode(this.checked);
            });
            aggiornaAnteprima();


        });
    </script>
@endpush
