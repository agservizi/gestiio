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
        <div class="dropzone" id="kt_dropzonejs_example_1">
            <div class="dz-message needsclick">
                <i class="bi bi-file-earmark-arrow-up text-primary fs-3x"></i>

                <div class="ms-4">
                    <h3 class="fs-5 fw-bolder text-gray-900 mb-1">Trascina il file qui o clicca per selezionare i files</h3>
                    <span class="fs-7 fw-bold text-gray-400">
                                            <span>Qui puoi allegare i documenti relativi al contratto</span>

                                        </span>
                </div>
            </div>
        </div>
    </div>

    <script>
        var canDeleteFiles = @json($canDeleteFiles ?? false);
        var myDropzone = new Dropzone("#kt_dropzonejs_example_1", {
            url: "{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'upload'],$id)}}", // Set the url for your upload script location
            paramName: "file", // The name that will be used to transfer the file
            maxFiles: 10,
            maxFilesize: 50, // MB
            addRemoveLinks: canDeleteFiles,
            //acceptedFiles: "image/*",
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            init: function () {
                thisDropzone = this;
                this.on("sending", function (file, xhr, formData) {
                    formData.append("uid", $('#uid').val());
                    formData.append("categoria_documentale", $('#categoria_documentale').val());
                    formData.append("tags_documentali", $('#tags_documentali').val());
                    formData.append("expires_at", $('#expires_at').val());
                });

            },
            accept: function (file, done) {
                if (file.name == "q") {
                    done("Naha, you don't.");
                } else {
                    done();
                }
            },
            success: function (file, response) {
                file.filename = response.filename;
                file.id = response.id;
                console.dir(file);


                reloadFiles();


            },
            removedfile: function (file) {
                if (!canDeleteFiles) {
                    return;
                }
                console.dir(file);
                var name = file.filename;
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'DELETE',
                    url: '{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'cancellaFile'])}}',
                    data: {
                        id: file.id
                    },
                    success: function (data) {
                        reloadFiles();

                    },
                    error: function (e) {
                    }
                });
                var fileRef;
                return (fileRef = file.previewElement) != null ?
                    fileRef.parentNode.removeChild(file.previewElement) : void 0;
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
                        alert(response.message);
                    }

                }
            });

        }

    </script>
@endsection
