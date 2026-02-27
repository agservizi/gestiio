@php
    $idPadre = $idPadre ?? $record->id;
    $downloadController = $downloadController ?? \App\Http\Controllers\Backend\ContrattoEnergiaController::class;
    $previewModalId = $previewModalId ?? ('modal_preview_allegati_energia_' . $idPadre);
@endphp

<div>
    <h4>Allegati</h4>
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
                    <tr>
                        <td class="text-break">{{$allegato->filename_originale}}</td>
                        <td class="text-end">{{\App\humanFileSize($allegato->dimensione_file)}}</td>
                        <td>{{$allegato->created_at?->format('d/m/Y H:i')}}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-light-primary me-2"
                                data-bs-toggle="modal"
                                data-bs-target="#{{$previewModalId}}"
                                data-file-url="{{$allegato->urlFile()}}"
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

    <div class="modal fade" id="{{$previewModalId}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Anteprima allegato</h5>
                    <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 fw-semibold text-break js-preview-filename"></div>

                    <img src="" alt="Anteprima immagine" class="img-fluid w-100 d-none js-preview-image">
                    <iframe src="" class="w-100 d-none js-preview-pdf" style="min-height: 75vh;" title="Anteprima PDF"></iframe>

                    <div class="alert alert-info d-none mb-0 js-preview-fallback">
                        Anteprima non disponibile per questo file.
                        <a href="#" target="_blank" class="js-preview-open-file">Apri file</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('customScript')
    <script>
        (function () {
            var modalEl = document.getElementById('{{$previewModalId}}');
            if (!modalEl) {
                return;
            }

            modalEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                var fileUrl = trigger.getAttribute('data-file-url') || '';
                var fileName = trigger.getAttribute('data-file-name') || '';
                var fileType = trigger.getAttribute('data-file-type') || '';

                var fileNameEl = modalEl.querySelector('.js-preview-filename');
                var imageEl = modalEl.querySelector('.js-preview-image');
                var pdfEl = modalEl.querySelector('.js-preview-pdf');
                var fallbackEl = modalEl.querySelector('.js-preview-fallback');
                var openLinkEl = modalEl.querySelector('.js-preview-open-file');

                fileNameEl.textContent = fileName;
                imageEl.classList.add('d-none');
                pdfEl.classList.add('d-none');
                fallbackEl.classList.add('d-none');
                imageEl.removeAttribute('src');
                pdfEl.removeAttribute('src');

                if (fileType === 'immagine') {
                    imageEl.setAttribute('src', fileUrl);
                    imageEl.classList.remove('d-none');
                    return;
                }

                if (fileType === 'pdf') {
                    pdfEl.setAttribute('src', fileUrl);
                    pdfEl.classList.remove('d-none');
                    return;
                }

                fallbackEl.classList.remove('d-none');
                openLinkEl.setAttribute('href', fileUrl);
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                var imageEl = modalEl.querySelector('.js-preview-image');
                var pdfEl = modalEl.querySelector('.js-preview-pdf');
                imageEl.removeAttribute('src');
                pdfEl.removeAttribute('src');
            });
        })();
    </script>
@endpush
