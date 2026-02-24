@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1 gap-2 flex-wrap">
        @include('Backend._components.ricercaIndex')

        <select id="filter_tipo_file" class="form-select form-select-sm w-auto">
            <option value="">Tutti i tipi</option>
            <option value="pdf">PDF</option>
            <option value="doc">Word</option>
            <option value="xls">Excel</option>
            <option value="jpg">Immagini</option>
            <option value="zip">Archivi</option>
        </select>

        <select id="filter_order_by" class="form-select form-select-sm w-auto">
            <option value="recenti">Più recenti</option>
            <option value="nome">Nome (A-Z)</option>
            <option value="dimensione">Più pesanti</option>
        </select>

        <select id="filter_scope" class="form-select form-select-sm w-auto">
            <option value="current">Solo cartella corrente</option>
            <option value="all">Tutte le cartelle</option>
        </select>

        <select id="filter_categoria_documentale" class="form-select form-select-sm w-auto">
            <option value="">Tutte le categorie</option>
            @foreach(($categorieDocumentali ?? []) as $categoria)
                <option value="{{ $categoria }}">{{ $categoria }}</option>
            @endforeach
        </select>

        <input id="filter_tag_documentale" type="text" class="form-control form-control-sm w-auto" placeholder="Tag"/>
        <input id="filter_data_da" type="date" class="form-control form-control-sm w-auto" title="Da"/>
        <input id="filter_data_a" type="date" class="form-control form-control-sm w-auto" title="A"/>

        @if($canUploadFiles)
            <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax"
               id="btn-upload-documenti"
               href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['upload-documento',$cartellaId])}}">
                Upload
            </a>
        @endif

        @if($canManageFolders)
            <a class="btn btn-sm btn-light-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax"
               id="btn-nuova-cartella"
               href="{{action([$controller,'create'],$cartellaId)}}">{{ $testoNuovo }}</a>
            <button type="button" id="btn-share-current-folder" class="btn btn-sm btn-light-success fw-bold">
                Share cartella
            </button>
            <a id="btn-audit-export" class="btn btn-sm btn-light-dark fw-bold"
               href="{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'exportAuditCsv']) }}">
                Export audit CSV
            </a>
        @endif

        <button type="button" id="download-multiplo-btn" class="btn btn-sm btn-light-success fw-bold" disabled>
            Scarica selezionati (0)
        </button>
    </div>
@endsection

@section('content')
    <form id="download-multiplo-form" method="POST" action="{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'downloadMultiplo']) }}" class="d-none">
        @csrf
        <div id="download-multiplo-inputs"></div>
    </form>
    <input type="file" id="file-version-input" class="d-none"/>

    <div class="card card-flush">
        <div class="card-header py-5">
            <div>
                <h3 class="card-title fw-bold mb-1">Archivio documenti</h3>
                <div class="text-muted fs-7">Vista ottimizzata per admin, agente, operatore e supervisore</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge badge-light-primary">Cartella: {{ $stats['cartella'] ?? 'Root' }}</span>
                <span class="badge badge-light">Cartelle: {{ $stats['conteggio_cartelle'] ?? 0 }}</span>
                <span class="badge badge-light">File: {{ $stats['conteggio_file'] ?? 0 }}</span>
                <span class="badge badge-light-success">Peso: {{ $stats['dimensione_totale'] ?? '0 B' }}</span>
            </div>
        </div>

        <div class="card-body pt-0" id="elenco-files">
            @include('Backend.CartellaFiles.elenchi')
        </div>
    </div>

    <div class="card card-flush mt-5">
        <div class="card-header py-4">
            <h3 class="card-title fw-bold">Audit documenti (upload/cancellazioni/download zip)</h3>
        </div>
        <div class="card-body pt-0">
            @if(($auditRecenti ?? collect())->isEmpty())
                <div class="text-muted py-6">Nessuna attività registrata.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                            <th>Quando</th>
                            <th>Utente</th>
                            <th>Azione</th>
                            <th>File</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($auditRecenti as $audit)
                            <tr>
                                <td>{{ $audit->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $audit->utente?->name ?? 'Utente #' . ($audit->user_id ?? '-') }}</td>
                                <td><span class="badge badge-light-primary">{{ $audit->azione }}</span></td>
                                <td>{{ $audit->filename_originale }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('customScript')
    <script>
        const baseDocumentiUrl = '{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'index'], $cartellaId) }}';
        const createCartellaUrlTemplate = '{{ action([$controller, 'create'], ['cartellaId' => '__CARTELLA_ID__']) }}';
        const uploadDocumentoUrlTemplate = '{{ action([\App\Http\Controllers\Backend\ModalController::class, 'show'], ['upload-documento', '__CARTELLA_ID__']) }}';
        const moveFileUrl = '{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'moveFile']) }}';
        const moveFolderUrl = '{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'moveFolder']) }}';
        const renameFileUrl = '{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'renameFile']) }}';
        const setExpiryUrl = '{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'setExpiry']) }}';
        const createShareUrl = '{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'createShareLink']) }}';
        const uploadVersionUrlTemplate = '{{ url('/backend/documento/__ID__/versione') }}';
        const rollbackVersionUrlTemplate = '{{ url('/backend/documento/__ID__/versione/__VERSION_ID__/rollback') }}';
        const setFolderVisibilityUrlTemplate = '{{ url('/backend/documenti/__ID__/visibilita') }}';
        const folderOptions = @json($folderOptions ?? []);
        const shareBaseUrl = @json($shareBaseUrl ?? '');
        const auditExportBaseUrl = '{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'exportAuditCsv']) }}';

        $(function () {
            const $filterSearch = $('#filter_search');
            const $filterTipo = $('#filter_tipo_file');
            const $filterDataDa = $('#filter_data_da');
            const $filterDataA = $('#filter_data_a');
            const $filterOrderBy = $('#filter_order_by');
            const $filterScope = $('#filter_scope');
            const $filterCategoria = $('#filter_categoria_documentale');
            const $filterTag = $('#filter_tag_documentale');
            const $downloadBtn = $('#download-multiplo-btn');
            const $btnNuovaCartella = $('#btn-nuova-cartella');
            const $btnUploadDocumento = $('#btn-upload-documenti');
            const $btnShareCurrentFolder = $('#btn-share-current-folder');
            const $btnAuditExport = $('#btn-audit-export');
            const $versionFileInput = $('#file-version-input');
            const selectedFileIds = new Set();
            let currentDocumentiUrl = baseDocumentiUrl;
            let pendingVersionFileId = null;

            function currentFilters() {
                return {
                    cerca: $filterSearch.val() || '',
                    tipo_file: $filterTipo.val() || '',
                    data_da: $filterDataDa.val() || '',
                    data_a: $filterDataA.val() || '',
                    order_by: $filterOrderBy.val() || 'recenti',
                    scope: $filterScope.val() || 'current',
                    categoria_documentale: $filterCategoria.val() || '',
                    tag_documentale: $filterTag.val() || '',
                };
            }

            function updateDownloadButton() {
                const count = selectedFileIds.size;
                $downloadBtn.prop('disabled', count === 0);
                $downloadBtn.text('Scarica selezionati (' + count + ')');
            }

            function resolveCartellaIdFromUrl(url) {
                try {
                    const parsed = new URL(url, window.location.origin);
                    const cleanPath = (parsed.pathname || '').replace(/\/+$/, '');
                    const match = cleanPath.match(/\/documenti\/(\d+)$/);
                    return match ? Number(match[1]) : 0;
                } catch (e) {
                    return 0;
                }
            }

            function updateContextActions(cartellaId) {
                const id = Number.isFinite(cartellaId) ? cartellaId : 0;
                if ($btnNuovaCartella.length) {
                    $btnNuovaCartella.attr('href', createCartellaUrlTemplate.replace('__CARTELLA_ID__', String(id)));
                }
                if ($btnUploadDocumento.length) {
                    $btnUploadDocumento.attr('href', uploadDocumentoUrlTemplate.replace('__CARTELLA_ID__', String(id)));
                }
                if ($btnShareCurrentFolder.length) {
                    $btnShareCurrentFolder.data('cartella-id', id);
                }
            }

            function csrfHeaders() {
                return {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')};
            }

            function folderHint() {
                const entries = Object.entries(folderOptions).slice(0, 30).map(([id, name]) => id + ': ' + name);
                return entries.join('\n');
            }

            function currentCartellaId() {
                return resolveCartellaIdFromUrl(currentDocumentiUrl);
            }

            function syncSelectionState() {
                $('.file-select').each(function () {
                    const id = Number($(this).val());
                    $(this).prop('checked', selectedFileIds.has(id));
                });
                updateDownloadButton();
            }

            function initTooltips() {
                if (typeof KTApp !== 'undefined' && KTApp && typeof KTApp.createInstances === 'function') {
                    KTApp.createInstances();
                    return;
                }
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    const nodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    nodes.forEach(function (el) {
                        const current = bootstrap.Tooltip.getInstance(el);
                        if (current) {
                            current.dispose();
                        }
                        new bootstrap.Tooltip(el);
                    });
                }
            }

            function refreshElenco(url = currentDocumentiUrl) {
                const targetUrl = url || currentDocumentiUrl;
                const targetCartellaId = resolveCartellaIdFromUrl(targetUrl);
                $.ajax({
                    url: targetUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: currentFilters(),
                    success: function (response) {
                        currentDocumentiUrl = targetUrl;
                        updateContextActions(targetCartellaId);
                        $('#elenco-files').html(base64_decode(response.html));
                        initTooltips();
                        syncSelectionState();
                    },
                    error: function (xhr) {
                        const err = xhr.responseJSON || {};
                        Swal.fire('Errore ' + xhr.status, err.message || 'Operazione non riuscita', 'error');
                    }
                });
            }

            let timer = null;
            $filterSearch.on('keyup', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    refreshElenco();
                }, 300);
            });

            $filterTipo.on('change', function () { refreshElenco(); });
            $filterDataDa.on('change', function () { refreshElenco(); });
            $filterDataA.on('change', function () { refreshElenco(); });
            $filterOrderBy.on('change', function () { refreshElenco(); });
            $filterScope.on('change', function () { refreshElenco(); });
            $filterCategoria.on('change', function () { refreshElenco(); });
            $filterTag.on('keyup', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    refreshElenco();
                }, 300);
            });

            $(document).on('click', '.cartella', function (e) {
                e.preventDefault();
                refreshElenco($(this).attr('href'));
            });

            $(document).on('click', '.elimina-file', function (e) {
                e.preventDefault();
                const url = $(this).attr('href');
                Swal.fire({
                    title: 'Sei sicuro?',
                    text: 'Il file verrà eliminato definitivamente',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Si, elimina',
                    cancelButtonText: 'Annulla',
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-light'
                    }
                }).then(function (result) {
                    if (result.value) {
                        elimina(url);
                        const id = Number(url.split('id=').pop());
                        selectedFileIds.delete(id);
                        updateDownloadButton();
                    }
                });
            });

            $(document).on('change', '#select-files-page', function () {
                const checked = $(this).is(':checked');
                $('.file-select').each(function () {
                    const id = Number($(this).val());
                    if (checked) {
                        selectedFileIds.add(id);
                    } else {
                        selectedFileIds.delete(id);
                    }
                });
                syncSelectionState();
            });

            $(document).on('change', '.file-select', function () {
                const id = Number($(this).val());
                if ($(this).is(':checked')) {
                    selectedFileIds.add(id);
                } else {
                    selectedFileIds.delete(id);
                }
                updateDownloadButton();
            });

            $downloadBtn.on('click', function () {
                if (selectedFileIds.size === 0) {
                    Swal.fire('Nessun file selezionato', 'Seleziona almeno un file per creare lo ZIP.', 'warning');
                    return;
                }
                const $inputs = $('#download-multiplo-inputs');
                $inputs.empty();
                Array.from(selectedFileIds).forEach(function (id) {
                    $inputs.append('<input type="hidden" name="file_ids[]" value="' + id + '">');
                });
                $('#download-multiplo-form').trigger('submit');
            });

            $btnAuditExport.on('click', function (e) {
                e.preventDefault();
                const params = new URLSearchParams(currentFilters());
                window.open(auditExportBaseUrl + '?' + params.toString(), '_blank');
            });

            $btnShareCurrentFolder.on('click', function () {
                const cartellaId = Number($(this).data('cartella-id') || 0);
                Swal.fire({
                    title: 'Share cartella',
                    html: '<input id=\"share-folder-exp\" type=\"date\" class=\"swal2-input\" placeholder=\"Scadenza\">' +
                        '<input id=\"share-folder-pass\" type=\"text\" class=\"swal2-input\" placeholder=\"Password (opzionale)\">' +
                        '<input id=\"share-folder-max\" type=\"number\" class=\"swal2-input\" placeholder=\"Max download (opzionale)\">',
                    showCancelButton: true,
                    confirmButtonText: 'Crea link',
                    preConfirm: function () {
                        return {
                            expires_at: $('#share-folder-exp').val() || null,
                            password: $('#share-folder-pass').val() || null,
                            max_downloads: $('#share-folder-max').val() || null
                        };
                    }
                }).then(function (result) {
                    if (!result.value) {
                        return;
                    }
                    const payload = Object.assign({cartella_id: cartellaId}, result.value);
                    $.ajax({
                        url: createShareUrl,
                        type: 'POST',
                        headers: csrfHeaders(),
                        data: payload,
                        success: function (response) {
                            Swal.fire('Link creato', '<a href=\"' + response.url + '\" target=\"_blank\">' + response.url + '</a>', 'success');
                        }
                    });
                });
            });

            $(document).on('click', '.file-rename', function () {
                const id = Number($(this).data('file-id'));
                const name = String($(this).data('file-name') || '');
                Swal.fire({
                    title: 'Rinomina file',
                    input: 'text',
                    inputValue: name,
                    showCancelButton: true,
                    confirmButtonText: 'Salva'
                }).then(function (result) {
                    if (!result.value) {
                        return;
                    }
                    $.ajax({
                        url: renameFileUrl,
                        type: 'POST',
                        headers: csrfHeaders(),
                        data: {id: id, filename_originale: result.value},
                        success: function () { refreshElenco(); }
                    });
                });
            });

            $(document).on('click', '.file-move', function () {
                const id = Number($(this).data('file-id'));
                Swal.fire({
                    title: 'Sposta file',
                    input: 'text',
                    inputLabel: 'ID cartella destinazione (0 per Root)',
                    inputPlaceholder: 'Es: 15',
                    footer: '<pre style=\"text-align:left;max-height:160px;overflow:auto\">' + folderHint() + '</pre>',
                    showCancelButton: true,
                    confirmButtonText: 'Sposta'
                }).then(function (result) {
                    if (result.value === undefined) {
                        return;
                    }
                    const target = String(result.value).trim();
                    $.ajax({
                        url: moveFileUrl,
                        type: 'POST',
                        headers: csrfHeaders(),
                        data: {id: id, target_cartella_id: target === '' || target === '0' ? null : Number(target)},
                        success: function () { refreshElenco(); }
                    });
                });
            });

            $(document).on('click', '.folder-move', function () {
                const id = Number($(this).data('folder-id'));
                Swal.fire({
                    title: 'Sposta cartella',
                    input: 'text',
                    inputLabel: 'ID cartella padre destinazione (0 per Root)',
                    inputPlaceholder: 'Es: 20',
                    footer: '<pre style=\"text-align:left;max-height:160px;overflow:auto\">' + folderHint() + '</pre>',
                    showCancelButton: true,
                    confirmButtonText: 'Sposta'
                }).then(function (result) {
                    if (result.value === undefined) {
                        return;
                    }
                    const target = String(result.value).trim();
                    $.ajax({
                        url: moveFolderUrl,
                        type: 'POST',
                        headers: csrfHeaders(),
                        data: {id: id, target_parent_id: target === '' || target === '0' ? null : Number(target)},
                        success: function () { refreshElenco(); }
                    });
                });
            });

            $(document).on('click', '.folder-visibility', function () {
                const id = Number($(this).data('folder-id'));
                const existing = String($(this).data('folder-roles') || '');
                Swal.fire({
                    title: 'Visibilità cartella',
                    input: 'text',
                    inputLabel: 'Ruoli CSV (admin,agente,operatore,supervisore) - vuoto=tutti',
                    inputValue: existing,
                    showCancelButton: true,
                    confirmButtonText: 'Salva'
                }).then(function (result) {
                    if (result.value === undefined) {
                        return;
                    }
                    const raw = String(result.value || '').trim();
                    const roles = raw ? raw.split(',').map(function (r) { return r.trim(); }).filter(Boolean) : [];
                    $.ajax({
                        url: setFolderVisibilityUrlTemplate.replace('__ID__', String(id)),
                        type: 'POST',
                        headers: csrfHeaders(),
                        data: {ruoli: roles},
                        success: function () { refreshElenco(); }
                    });
                });
            });

            $(document).on('click', '.file-expiry', function () {
                const id = Number($(this).data('file-id'));
                const current = String($(this).data('file-expiry') || '');
                Swal.fire({
                    title: 'Scadenza documento',
                    input: 'date',
                    inputValue: current,
                    showCancelButton: true,
                    confirmButtonText: 'Salva'
                }).then(function (result) {
                    if (result.value === undefined) {
                        return;
                    }
                    $.ajax({
                        url: setExpiryUrl,
                        type: 'POST',
                        headers: csrfHeaders(),
                        data: {id: id, expires_at: result.value || null},
                        success: function () { refreshElenco(); }
                    });
                });
            });

            $(document).on('click', '.file-share', function () {
                const fileId = Number($(this).data('file-id'));
                Swal.fire({
                    title: 'Share file',
                    html: '<input id=\"share-file-exp\" type=\"date\" class=\"swal2-input\" placeholder=\"Scadenza\">' +
                        '<input id=\"share-file-pass\" type=\"text\" class=\"swal2-input\" placeholder=\"Password (opzionale)\">' +
                        '<input id=\"share-file-max\" type=\"number\" class=\"swal2-input\" placeholder=\"Max download (opzionale)\">',
                    showCancelButton: true,
                    confirmButtonText: 'Crea link',
                    preConfirm: function () {
                        return {
                            expires_at: $('#share-file-exp').val() || null,
                            password: $('#share-file-pass').val() || null,
                            max_downloads: $('#share-file-max').val() || null
                        };
                    }
                }).then(function (result) {
                    if (!result.value) {
                        return;
                    }
                    const payload = Object.assign({file_id: fileId}, result.value);
                    $.ajax({
                        url: createShareUrl,
                        type: 'POST',
                        headers: csrfHeaders(),
                        data: payload,
                        success: function (response) {
                            Swal.fire('Link creato', '<a href=\"' + response.url + '\" target=\"_blank\">' + response.url + '</a>', 'success');
                        }
                    });
                });
            });

            $(document).on('click', '.file-version', function () {
                pendingVersionFileId = Number($(this).data('file-id'));
                $versionFileInput.val('');
                $versionFileInput.trigger('click');
            });

            $(document).on('click', '.file-rollback', function () {
                const fileId = Number($(this).data('file-id'));
                Swal.fire({
                    title: 'Rollback versione',
                    input: 'text',
                    inputLabel: 'Inserisci ID versione o numero versione',
                    inputPlaceholder: 'Es. 3',
                    showCancelButton: true,
                    confirmButtonText: 'Ripristina'
                }).then(function (result) {
                    const v = String(result.value || '').trim();
                    if (!v) {
                        return;
                    }
                    const url = rollbackVersionUrlTemplate
                        .replace('__ID__', String(fileId))
                        .replace('__VERSION_ID__', encodeURIComponent(v));
                    $.ajax({
                        url: url,
                        type: 'POST',
                        headers: csrfHeaders(),
                        success: function () { refreshElenco(); }
                    });
                });
            });

            $versionFileInput.on('change', function () {
                if (!pendingVersionFileId || !this.files || !this.files[0]) {
                    return;
                }
                const fd = new FormData();
                fd.append('file', this.files[0]);
                $.ajax({
                    url: uploadVersionUrlTemplate.replace('__ID__', String(pendingVersionFileId)),
                    type: 'POST',
                    headers: csrfHeaders(),
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function () {
                        pendingVersionFileId = null;
                        refreshElenco();
                    }
                });
            });

            updateContextActions(resolveCartellaIdFromUrl(currentDocumentiUrl));
            initTooltips();
            syncSelectionState();
        });
    </script>
@endpush
