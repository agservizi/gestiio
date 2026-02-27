@php
    $idPadre = $idPadre ?? $record->id;
    $downloadController = $downloadController ?? \App\Http\Controllers\Backend\ContrattoTelefoniaController::class;
@endphp

<div>
    @if($record->allegati->isEmpty())
        <div class="text-muted">Nessun allegato disponibile.</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-7 gy-3">
                <thead>
                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                    <th>Nome</th>
                    <th class="text-end">Dimensione</th>
                    <th>Data caricamento</th>
                    <th class="text-end">Azioni</th>
                </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                @foreach($record->allegati as $allegato)
                    @php($previewUrl = action([$downloadController, 'downloadAllegato'], [$idPadre, $allegato->id]) . '?anteprima=1')
                    <tr>
                        <td class="text-break">{{$allegato->filename_originale}}</td>
                        <td class="text-end">{{\App\humanFileSize($allegato->dimensione_file)}}</td>
                        <td>{{$allegato->created_at?->format('d/m/Y H:i')}}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-light-primary me-2 js-open-attachment-preview"
                                data-file-url="{{$previewUrl}}"
                                data-download-url="{{action([$downloadController, 'downloadAllegato'], [$idPadre, $allegato->id])}}"
                                data-file-name="{{$allegato->filename_originale}}"
                                data-file-type="{{$allegato->tipo_file}}"
                            >
                                Anteprima
                            </button>
                            <a href="{{action([$downloadController, 'downloadAllegato'], [$idPadre, $allegato->id])}}"
                               class="btn btn-sm btn-primary">
                                Scarica
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('customScript')
    <script>
        (function () {
            if (window.__attachmentPreviewInit) {
                return;
            }
            window.__attachmentPreviewInit = true;

            var modalId = 'global_attachment_preview_modal';
            var modalEl = document.getElementById(modalId);
            if (!modalEl) {
                document.body.insertAdjacentHTML('beforeend', '' +
                    '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-hidden="true">' +
                    '  <div class="modal-dialog modal-xl modal-dialog-centered">' +
                    '    <div class="modal-content">' +
                    '      <div class="modal-header">' +
                    '        <h5 class="modal-title">Anteprima allegato</h5>' +
                    '        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>' +
                    '      </div>' +
                    '      <div class="modal-body">' +
                    '        <div class="mb-3 fw-semibold text-break js-preview-filename"></div>' +
                    '        <div class="d-flex align-items-center justify-content-center py-10 js-preview-loading d-none">' +
                    '          <div class="text-center">' +
                    '            <span class="spinner-border spinner-border-sm me-2"></span>' +
                    '            <span class="text-muted fw-semibold">Caricamento anteprima...</span>' +
                    '          </div>' +
                    '        </div>' +
                    '        <img src="" alt="Anteprima immagine" class="img-fluid w-100 d-none js-preview-image">' +
                    '        <iframe src="" class="w-100 d-none js-preview-pdf" style="min-height: 75vh;" title="Anteprima PDF"></iframe>' +
                    '        <div class="border rounded bg-light-primary p-8 text-center d-none js-preview-empty">' +
                    '          <div class="mb-3"><i class="fas fa-file-circle-exclamation fs-2x text-primary"></i></div>' +
                    '          <div class="fw-bold fs-6 mb-1">Anteprima non disponibile</div>' +
                    '          <div class="text-muted mb-5">Il file potrebbe essere stato spostato o rimosso. Puoi comunque provare a scaricarlo.</div>' +
                    '          <a href="#" class="btn btn-sm btn-primary me-2 js-preview-download-file">Scarica file</a>' +
                    '          <a href="#" target="_blank" class="btn btn-sm btn-light-primary js-preview-open-file">Apri in nuova scheda</a>' +
                    '        </div>' +
                    '      </div>' +
                    '    </div>' +
                    '  </div>' +
                    '</div>'
                );
                modalEl = document.getElementById(modalId);
            }
            var activeObjectUrl = null;
            var previewToken = 0;

            var inferType = function (fileType, fileName) {
                var normalizedType = (fileType || '').toLowerCase();
                if (normalizedType === 'pdf' || normalizedType === 'immagine') {
                    return normalizedType;
                }

                var ext = '';
                if (fileName && fileName.indexOf('.') !== -1) {
                    ext = fileName.split('.').pop().toLowerCase();
                }

                if (ext === 'pdf') {
                    return 'pdf';
                }
                if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].indexOf(ext) !== -1) {
                    return 'immagine';
                }

                return normalizedType;
            };

            document.addEventListener('click', function (event) {
                var trigger = event.target.closest('.js-open-attachment-preview');
                if (!trigger) {
                    return;
                }

                var fileUrl = trigger.getAttribute('data-file-url') || '';
                var downloadUrl = trigger.getAttribute('data-download-url') || fileUrl;
                var fileName = trigger.getAttribute('data-file-name') || '';
                var fileType = trigger.getAttribute('data-file-type') || '';
                var tipoPreview = inferType(fileType, fileName);
                var myToken = ++previewToken;

                var fileNameEl = modalEl.querySelector('.js-preview-filename');
                var imageEl = modalEl.querySelector('.js-preview-image');
                var pdfEl = modalEl.querySelector('.js-preview-pdf');
                var loadingEl = modalEl.querySelector('.js-preview-loading');
                var emptyEl = modalEl.querySelector('.js-preview-empty');
                var openLinkEl = modalEl.querySelector('.js-preview-open-file');
                var downloadLinkEl = modalEl.querySelector('.js-preview-download-file');

                fileNameEl.textContent = fileName;
                imageEl.classList.add('d-none');
                pdfEl.classList.add('d-none');
                emptyEl.classList.add('d-none');
                loadingEl.classList.remove('d-none');
                imageEl.removeAttribute('src');
                pdfEl.removeAttribute('src');
                openLinkEl.setAttribute('href', fileUrl);
                downloadLinkEl.setAttribute('href', downloadUrl);

                if (activeObjectUrl) {
                    URL.revokeObjectURL(activeObjectUrl);
                    activeObjectUrl = null;
                }

                var showEmpty = function () {
                    if (myToken !== previewToken) {
                        return;
                    }
                    loadingEl.classList.add('d-none');
                    imageEl.classList.add('d-none');
                    pdfEl.classList.add('d-none');
                    emptyEl.classList.remove('d-none');
                };

                var showPreview = function (blob) {
                    if (myToken !== previewToken) {
                        return;
                    }
                    loadingEl.classList.add('d-none');
                    activeObjectUrl = URL.createObjectURL(blob);
                    if (tipoPreview === 'immagine') {
                        imageEl.setAttribute('src', activeObjectUrl);
                        imageEl.classList.remove('d-none');
                        return;
                    }
                    if (tipoPreview === 'pdf') {
                        var pdfBlob = blob.type === 'application/pdf' ? blob : new Blob([blob], {type: 'application/pdf'});
                        if (pdfBlob !== blob) {
                            URL.revokeObjectURL(activeObjectUrl);
                            activeObjectUrl = URL.createObjectURL(pdfBlob);
                        }
                        pdfEl.setAttribute('src', activeObjectUrl);
                        pdfEl.classList.remove('d-none');
                        return;
                    }
                    showEmpty();
                };

                if (tipoPreview !== 'immagine' && tipoPreview !== 'pdf') {
                    showEmpty();
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    return;
                }

                bootstrap.Modal.getOrCreateInstance(modalEl).show();

                fetch(fileUrl, {credentials: 'same-origin'})
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('preview_not_available');
                        }
                        return response.blob();
                    })
                    .then(showPreview)
                    .catch(showEmpty);
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                var imageEl = modalEl.querySelector('.js-preview-image');
                var pdfEl = modalEl.querySelector('.js-preview-pdf');
                var emptyEl = modalEl.querySelector('.js-preview-empty');
                var loadingEl = modalEl.querySelector('.js-preview-loading');
                imageEl.removeAttribute('src');
                pdfEl.removeAttribute('src');
                imageEl.classList.add('d-none');
                pdfEl.classList.add('d-none');
                emptyEl.classList.add('d-none');
                loadingEl.classList.add('d-none');
                if (activeObjectUrl) {
                    URL.revokeObjectURL(activeObjectUrl);
                    activeObjectUrl = null;
                }
            });
        })();
    </script>
@endpush
