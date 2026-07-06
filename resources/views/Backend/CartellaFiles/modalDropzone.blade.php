@extends('Backend._components.modal')
@section('content')
    <div class="row g-3 mb-6">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Categoria documentale</label>
            <input type="text" id="categoria_documentale" class="form-control form-control-sm" placeholder="Es. Contratti, Fatture, Privacy"/>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Tag (separati da virgola)</label>
            <input type="text" id="tags_documentali" class="form-control form-control-sm" placeholder="es. urgente, cliente, firmato"/>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Scadenza documento (opzionale)</label>
            <input type="date" id="expires_at" class="form-control form-control-sm"/>
        </div>
    </div>

    <div class="fv-row">
        <div class="dropzone gestiio-dropzone" id="kt_dropzonejs_example_1">
            <div class="dz-message needsclick">
                <span class="gestiio-dropzone-icon">
                    <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                </span>
                <div>
                    <h3>Trascina i file qui o clicca per selezionarli</h3>
                    <span>Carica documenti nella cartella selezionata con categoria, tag e scadenza.</span>
                </div>
            </div>
        </div>
    </div>
@endsection
@include('Backend._components.dropzoneUx')
@push('customScript')
    <script>
        var canDeleteFiles = @json($canDeleteFiles ?? false);
        initGestiioDropzone("#kt_dropzonejs_example_1", {
            uploadUrl: "{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'upload'],$id)}}",
            deleteUrl: "{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'cancellaFile'])}}",
            csrfToken: "{{ csrf_token() }}",
            maxFiles: 10,
            maxFilesize: 50,
            addRemoveLinks: canDeleteFiles,
            sendingData: {
                uid: function () {
                    return $('#uid').val();
                },
                categoria_documentale: function () {
                    return $('#categoria_documentale').val();
                },
                tags_documentali: function () {
                    return $('#tags_documentali').val();
                },
                expires_at: function () {
                    return $('#expires_at').val();
                }
            },
            onSuccess: function () {
                reloadFiles();
            },
            onRemoved: function () {
                reloadFiles();
            },
        });


        function reloadFiles() {
            $.ajax({
                url: '{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class,'show'],$id) }}',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {

                        $('#elenco-files').html(base64_decode(response.html));
                    } else {
                        gestiioToast('error', response.message || 'Impossibile aggiornare i file.');
                    }

                }
            });

        }

    </script>
@endpush
