@extends('Backend._components.modal')
@section('content')
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
        var myDropzone = new Dropzone("#kt_dropzonejs_example_1", {
            url: "{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'upload'],$id)}}", // Set the url for your upload script location
            paramName: "file", // The name that will be used to transfer the file
            maxFiles: 10,
            maxFilesize: 50, // MB
            addRemoveLinks: true,
            //acceptedFiles: "image/*",
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            init: function () {
                thisDropzone = this;
                this.on("sending", function (file, xhr, formData) {
                    formData.append("uid", $('#uid').val());
                    console.log(formData)
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
                console.dir(file);
                var name = file.filename;
                console.log(name);
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
                        console.log(e);
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
