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
                                <div id="previewTesto" class="text-gray-800">(testo)</div>
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
            const etichetteDestinatario = {
                agente: 'Agenti',
                operatore: 'Operatori',
                admin: 'Admin',
                tutti: 'Tutti gli utenti'
            };

            const aggiornaAnteprima = () => {
                const destinatario = $('#destinatario').val() || 'agente';
                const titolo = $('#titolo').val() || '(titolo)';
                const testo = editorInstance ? editorInstance.getData() : ($('#testo').val() || '(testo)');

                $('#previewDestinatario').text(etichetteDestinatario[destinatario] || destinatario);
                $('#previewTitolo').text(titolo);
                $('#previewTesto').html(testo || '(testo)');
            };


            ClassicEditor
                .create( document.querySelector( '#testo' ) )
                .then(editor => {
                    editorInstance = editor;
                    editor.model.document.on('change:data', aggiornaAnteprima);
                    aggiornaAnteprima();
                })
                .catch( error => {
                    console.error( error );
                } );

            $('#destinatario, #titolo').on('change keyup', aggiornaAnteprima);
            aggiornaAnteprima();


        });
    </script>
@endpush
