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

        @if($canUploadFiles && $cartellaId)
            <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax"
               href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['upload-documento',$cartellaId])}}">
                Upload
            </a>
        @endif

        @if($canManageFolders)
            <a class="btn btn-sm btn-light-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax"
               href="{{action([$controller,'create'],$cartellaId)}}">{{ $testoNuovo }}</a>
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
            const selectedFileIds = new Set();

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

            function syncSelectionState() {
                $('.file-select').each(function () {
                    const id = Number($(this).val());
                    $(this).prop('checked', selectedFileIds.has(id));
                });
                updateDownloadButton();
            }

            function refreshElenco(url = baseDocumentiUrl) {
                $.ajax({
                    url,
                    type: 'GET',
                    dataType: 'json',
                    data: currentFilters(),
                    success: function (response) {
                        $('#elenco-files').html(base64_decode(response.html));
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

            syncSelectionState();
        });
    </script>
@endpush
