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
        @endif

        <div class="d-inline-flex align-items-center gap-2 flex-nowrap text-nowrap">
            @if($canManageFolders)
                <button type="button" id="btn-share-current-folder" class="btn btn-sm btn-icon btn-light-success"
                        data-bs-toggle="tooltip" data-bs-placement="top" title="Share cartella">
                    <span class="svg-icon svg-icon-4 m-0">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 6C17.6569 6 19 4.65685 19 3C19 1.34315 17.6569 0 16 0C14.3431 0 13 1.34315 13 3C13 3.35064 13.0602 3.6872 13.1707 4L7.82929 7C7.30627 6.38625 6.52847 6 5.66667 6C4.08985 6 2.8 7.28985 2.8 8.86667C2.8 10.4435 4.08985 11.7333 5.66667 11.7333C6.52847 11.7333 7.30627 11.3471 7.82929 10.7333L13.1707 13.7333C13.0602 14.0461 13 14.3826 13 14.7333C13 16.3902 14.3431 17.7333 16 17.7333C17.6569 17.7333 19 16.3902 19 14.7333C19 13.0765 17.6569 11.7333 16 11.7333C15.1382 11.7333 14.3604 12.1196 13.8374 12.7333L8.496 9.73333C8.60649 9.42053 8.66667 9.08397 8.66667 8.73333C8.66667 8.3827 8.60649 8.04613 8.496 7.73333L13.8374 4.73333C14.3604 5.34708 15.1382 5.73333 16 5.73333V6Z" fill="currentColor"/>
                        </svg>
                    </span>
                </button>
                <a id="btn-audit-export" class="btn btn-sm btn-icon btn-light-dark"
                   href="{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'exportAuditCsv']) }}"
                   data-bs-toggle="tooltip" data-bs-placement="top" title="Export audit CSV">
                    <span class="svg-icon svg-icon-4 m-0">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.3" d="M6 2H14L20 8V20C20 21.1046 19.1046 22 18 22H6C4.89543 22 4 21.1046 4 20V4C4 2.89543 4.89543 2 6 2Z" fill="currentColor"/>
                            <path d="M14 2V8H20M12 11V17M12 17L9.5 14.5M12 17L14.5 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </a>
            @endif

            <button type="button" id="download-multiplo-btn" class="btn btn-sm btn-icon btn-light-success" disabled
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Scarica selezionati">
                <span class="svg-icon svg-icon-4 m-0">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 3V13M12 13L8.5 9.5M12 13L15.5 9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 15V18C4 19.1046 4.89543 20 6 20H18C19.1046 20 20 19.1046 20 18V15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
            </button>
        </div>
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
                const tooltipText = 'Scarica selezionati (' + count + ')';
                $downloadBtn.attr('title', tooltipText);
                $downloadBtn.attr('data-bs-original-title', tooltipText);
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    const instance = bootstrap.Tooltip.getInstance($downloadBtn[0]);
                    if (instance && typeof instance.setContent === 'function') {
                        instance.setContent({'.tooltip-inner': tooltipText});
                    }
                }
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
