@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php
        $vecchio = $record->id;
        $destinatarioAttuale = old('destinatario', $record->destinatario ?? 'agente');
        $ruoli = [
            'admin' => ['label' => 'Admin', 'desc' => 'Solo lo staff amministrativo', 'icon' => 'ki-crown-2'],
            'agente' => ['label' => 'Agenti', 'desc' => 'Tutti gli agenti attivi', 'icon' => 'ki-user-square'],
            'operatore' => ['label' => 'Operatori', 'desc' => 'Team operativo interno', 'icon' => 'ki-user-tick'],
            'tutti' => ['label' => 'Tutti', 'desc' => 'Ogni utente con accesso al backend', 'icon' => 'ki-flag'],
        ];
    @endphp

    <div class="notifica-composer">
        <div class="notifica-composer-head mb-8">
            <div class="text-uppercase fw-semibold text-primary fs-8 mb-2">Centro comunicazioni</div>
            <h1 class="mb-1">{{ $vecchio ? 'Modifica notifica' : 'Nuova notifica broadcast' }}</h1>
            <p class="text-muted mb-0">Componi un messaggio e invialo a un ruolo specifico: arriverà come notifica nel menu, come toast a schermo e via email.</p>
        </div>

        @include('Backend._components.alertErrori')

        <form method="POST" action="{{ action([$controller, 'update'], $record->id ?? '') }}" enctype="multipart/form-data" id="formNotifica">
            @csrf
            @if($vecchio)
                @method('PATCH')
            @endif

            <div class="row g-6">
                <div class="col-xl-8">
                    <!-- Destinatari -->
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-duotone ki-people fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                                A chi la invii
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="notifica-role-grid">
                                @foreach($ruoli as $valore => $info)
                                    <label class="notifica-role-card {{ $destinatarioAttuale === $valore ? 'is-selected' : '' }}">
                                        <input type="radio" name="destinatario" value="{{ $valore }}" class="notifica-role-input"
                                               @checked($destinatarioAttuale === $valore) required>
                                        <span class="notifica-role-icon">
                                            <i class="ki-duotone {{ $info['icon'] }} fs-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="notifica-role-label">{{ $info['label'] }}</span>
                                        <span class="notifica-role-desc">{{ $info['desc'] }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                <label class="form-label" for="emails_aggiuntive">Email aggiuntive (facoltativo)</label>
                                <input type="text" name="emails_aggiuntive" id="emails_aggiuntive" class="form-control form-control-solid"
                                       value="{{ old('emails_aggiuntive') }}" placeholder="es. fornitore@esempio.it, altro@esempio.it">
                                <div class="form-text">Separate da virgola, punto e virgola o spazio. Ricevono l'email anche se non hanno un profilo interno.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenuto -->
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-duotone ki-pencil fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                                Contenuto
                            </h3>
                            <div class="card-toolbar">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="modalitaSorgente">
                                    <label class="form-check-label ms-2 fs-7" for="modalitaSorgente">HTML sorgente</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-6">
                                <label class="form-label required" for="titolo">Titolo</label>
                                <input type="text" name="titolo" id="titolo" class="form-control form-control-solid"
                                       value="{{ old('titolo', $record->titolo ?? '') }}" required maxlength="255"
                                       placeholder="es. Manutenzione programmata sabato notte">
                            </div>
                            <div>
                                <label class="form-label required" for="testo">Messaggio</label>
                                <textarea name="testo" id="testo" rows="10">{{ old('testo', $record->testo ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Immagine -->
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ki-duotone ki-picture fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                                Immagine (facoltativa)
                            </h3>
                        </div>
                        <div class="card-body">
                            <label for="immagine" class="notifica-image-drop" id="notificaImageDrop">
                                <div class="notifica-image-drop-empty" id="notificaImageEmpty" @if($record->immagine ?? null) style="display:none" @endif>
                                    <i class="ki-duotone ki-picture fs-1"><span class="path1"></span><span class="path2"></span></i>
                                    <div class="fw-bold">Trascina un'immagine qui o clicca per selezionarla</div>
                                    <div class="text-muted fs-7">JPG, PNG o WEBP — max 4MB</div>
                                </div>
                                <img id="notificaImagePreview" src="{{ $record->urlImmagine() ?? '' }}"
                                     class="notifica-image-preview" @if(!($record->immagine ?? null)) style="display:none" @endif alt="Anteprima immagine">
                            </label>
                            <input type="file" name="immagine" id="immagine" accept="image/*" class="d-none">
                            <div class="mt-3">
                                <button type="button" class="btn btn-sm btn-light-danger d-none" id="rimuoviImmagineBtn">Rimuovi immagine</button>
                                <input type="hidden" name="rimuovi_immagine" id="rimuovi_immagine" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="notifica-preview-sticky">
                        <div class="card notifica-preview-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-focus fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                                    Anteprima
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <span class="badge badge-light-primary" id="previewDestinatario">{{ $ruoli[$destinatarioAttuale]['label'] }}</span>
                                </div>
                                <div class="notifica-preview-frame">
                                    <img id="previewImg" src="{{ $record->urlImmagine() ?? '' }}" class="notifica-preview-img" @if(!($record->immagine ?? null)) style="display:none" @endif alt="">
                                    <h4 id="previewTitolo" class="mb-3">{{ $record->titolo ?? '(titolo)' }}</h4>
                                    <iframe id="previewFrame" class="notifica-preview-iframe"></iframe>
                                </div>
                            </div>
                        </div>

                        <div class="notifica-actions mt-6">
                            <button class="btn btn-primary w-100" type="submit" id="submit">
                                <i class="ki-duotone ki-send fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                                {{ $vecchio ? 'Salva modifiche' : 'Invia notifica' }}
                            </button>
                            <a href="{{ action([$controller, 'index']) }}" class="btn btn-light w-100 mt-3">Annulla</a>
                            @if($vecchio)
                                @if(($eliminabile ?? true) === true)
                                    <a class="btn btn-light-danger w-100 mt-3" id="elimina" href="{{ action([$controller, 'destroy'], $record->id) }}">Elimina</a>
                                @elseif(is_string($eliminabile ?? null))
                                    <span data-bs-toggle="tooltip" title="{{ $eliminabile }}">
                                        <a class="btn btn-light-danger w-100 mt-3 disabled" href="javascript:void(0)">Elimina</a>
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('customCss')
    <style>
        .notifica-composer-head h1 {
            font-weight: 700;
        }

        .notifica-role-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
        }

        .notifica-role-card {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
            padding: 16px;
            border: 1px solid #e4e6ef;
            border-radius: 10px;
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }

        .notifica-role-card:hover {
            border-color: #009ef7;
        }

        .notifica-role-card.is-selected {
            border-color: #009ef7;
            background: #f1faff;
            box-shadow: 0 0 0 1px #009ef7;
        }

        .notifica-role-input {
            position: absolute;
            opacity: 0;
            width: 1px;
            height: 1px;
        }

        .notifica-role-icon {
            color: #009ef7;
        }

        .notifica-role-label {
            font-weight: 700;
            color: #181c32;
        }

        .notifica-role-desc {
            font-size: 12px;
            color: #a1a5b7;
        }

        .notifica-image-drop {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 160px;
            border: 1px dashed #93c5fd;
            border-radius: 10px;
            background: #f8fbff;
            cursor: pointer;
            padding: 16px;
            text-align: center;
        }

        .notifica-image-drop.is-dragover {
            border-color: #009ef7;
            background: #eef6ff;
        }

        .notifica-image-drop-empty {
            color: #64748b;
        }

        .notifica-image-drop-empty i {
            color: #93c5fd;
            margin-bottom: 8px;
        }

        .notifica-image-preview {
            max-width: 100%;
            max-height: 260px;
            border-radius: 8px;
        }

        .notifica-preview-sticky {
            position: sticky;
            top: 20px;
        }

        .notifica-preview-frame {
            border: 1px solid #e4e6ef;
            border-radius: 10px;
            padding: 16px;
            background: #fff;
        }

        .notifica-preview-img {
            width: 100%;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .notifica-preview-iframe {
            width: 100%;
            min-height: 220px;
            border: 0;
        }
    </style>
@endpush

@push('customScript')
    <script src="/assets_backend/js-progetto/ckeditor5/build/ckeditor.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            let editorInstance = null;
            let sourceMode = false;
            const etichetteDestinatario = @json(collect($ruoli)->map(fn($r) => $r['label']));

            const decodeIfEscaped = (value) => {
                if (!value) {
                    return '';
                }
                if (value.includes('&lt;') || value.includes('&gt;') || value.includes('&amp;')) {
                    return $('<textarea/>').html(value).text();
                }
                return value;
            };

            const sanitizePreview = (value) => value.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');

            const renderPreviewFrame = (rawHtml) => {
                const html = sanitizePreview(decodeIfEscaped(rawHtml || '(testo)'));
                const frame = document.getElementById('previewFrame');
                if (!frame) return;
                frame.srcdoc = '<!doctype html><html><head><meta charset="utf-8">'
                    + '<style>body{font-family:Arial,Helvetica,sans-serif;padding:0;margin:0;font-size:14px;line-height:1.45;color:#2f3044;}table{max-width:100%;}img{max-width:100%;height:auto;}</style>'
                    + '</head><body>' + html + '</body></html>';
            };

            const createEditor = () => ClassicEditor.create(document.querySelector('#testo'))
                .then(editor => {
                    editorInstance = editor;
                    editor.model.document.on('change:data', aggiornaAnteprima);
                    aggiornaAnteprima();
                })
                .catch(error => console.error(error));

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
                const destinatario = $('input[name="destinatario"]:checked').val() || 'agente';
                const titolo = $('#titolo').val() || '(titolo)';
                const testo = editorInstance ? editorInstance.getData() : ($('#testo').val() || '(testo)');

                $('#previewDestinatario').text(etichetteDestinatario[destinatario] || destinatario);
                $('#previewTitolo').text(titolo);
                renderPreviewFrame(testo);
            };

            createEditor();

            $('input[name="destinatario"]').on('change', function () {
                $('.notifica-role-card').removeClass('is-selected');
                $(this).closest('.notifica-role-card').addClass('is-selected');
                aggiornaAnteprima();
            });
            $('#titolo').on('keyup change', aggiornaAnteprima);
            $('#testo').on('keyup change', aggiornaAnteprima);
            $('#modalitaSorgente').on('change', function () {
                setSourceMode(this.checked);
            });
            aggiornaAnteprima();

            // Immagine: drag&drop + preview + rimozione
            const inputImmagine = document.getElementById('immagine');
            const dropArea = document.getElementById('notificaImageDrop');
            const emptyState = document.getElementById('notificaImageEmpty');
            const preview = document.getElementById('notificaImagePreview');
            const previewMini = document.getElementById('previewImg');
            const rimuoviBtn = document.getElementById('rimuoviImmagineBtn');
            const rimuoviInput = document.getElementById('rimuovi_immagine');

            if (preview && preview.getAttribute('src')) {
                rimuoviBtn.classList.remove('d-none');
            }

            const showFile = (file) => {
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    previewMini.src = e.target.result;
                    previewMini.style.display = 'block';
                    emptyState.style.display = 'none';
                    rimuoviBtn.classList.remove('d-none');
                    rimuoviInput.value = '0';
                };
                reader.readAsDataURL(file);
            };

            inputImmagine.addEventListener('change', () => showFile(inputImmagine.files[0]));

            ['dragenter', 'dragover'].forEach(evt => dropArea.addEventListener(evt, (e) => {
                e.preventDefault();
                dropArea.classList.add('is-dragover');
            }));
            ['dragleave', 'drop'].forEach(evt => dropArea.addEventListener(evt, (e) => {
                e.preventDefault();
                dropArea.classList.remove('is-dragover');
            }));
            dropArea.addEventListener('drop', (e) => {
                const file = e.dataTransfer.files[0];
                if (file) {
                    inputImmagine.files = e.dataTransfer.files;
                    showFile(file);
                }
            });

            rimuoviBtn.addEventListener('click', () => {
                inputImmagine.value = '';
                preview.src = '';
                preview.style.display = 'none';
                previewMini.style.display = 'none';
                emptyState.style.display = 'block';
                rimuoviBtn.classList.add('d-none');
                rimuoviInput.value = '1';
            });
        });
    </script>
@endpush
