@if($record->stato=='in_lavorazione' )

    @include('Backend._components.notice',['titolo'=>'In lavorazione','testo'=>'Il tuo ticket è in lavorazione'])

@endif
<div class="mt-2">
    <h4>Rispondi</h4>
    <form id="segnala-form" class="form-horizontal" method="POST" action="{{action([$controller,'update'],$record->id)}}">
        @csrf
        @method('PATCH')
        @php($uid=old('uid',\Illuminate\Support\Str::ulid()))
        <input type="hidden" name="uid" id="uid" value="{{$uid}}">

        <div class="mb-0">

            <textarea class="form-control form-control-solid placeholder-gray-600 fw-bolder fs-4 ps-9 pt-7" rows="6" name="messaggio"
                      placeholder="Testo del messaggio" required></textarea>
            <button type="submit" id="submit" class="btn btn-primary mt-n20 mb-20 position-relative float-end me-7">Rispondi</button>
        </div>
    </form>
    <div class="fv-row mt-2">
        <div class="dropzone" id="kt_dropzonejs_example_1">
            <div class="dz-message needsclick">
                <i class="bi bi-file-earmark-arrow-up text-primary fs-3x"></i>

                <div class="ms-4">
                    <h3 class="fs-5 fw-bolder text-gray-900 mb-1">Trascina il file qui o clicca per selezionare i files</h3>
                    <span class="fs-7 fw-bold text-gray-400">
                                            <span>Qui puoi allegare i documenti relativi al ticket</span>

                                        </span>
                </div>
            </div>
        </div>
    </div>

</div>
<script>
    formSubmitAndDelete();
    var myDropzone = new Dropzone("#kt_dropzonejs_example_1", {
        url: "{{action([$controller,'uploadAllegato'])}}", // Set the url for your upload script location
        paramName: "file", // The name that will be used to transfer the file
        maxFiles: 10,
        maxFilesize: 20, // MB
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
            const esistenti =@json(\App\Models\AllegatoMessaggioTicket::perBlade($uid,null));
            if (esistenti) {
                $.each(esistenti, function (key, value) {

                    var mockFile = {name: value.path_filename, size: value.dimensione_file, filename: value.path_filename, id: value.id};

                    thisDropzone.emit('addedfile', mockFile);
                    if (value.tipo_file === 'immagine') {
                        thisDropzone.emit('thumbnail', mockFile, "/storage/" + value.path_filename);

                    }
                    thisDropzone.emit('complete', mockFile);


                });
            }

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
                url: '{{ action([$controller,'deleteAllegato']) }}',
                data: {
                    id: file.id
                },
                success: function (data) {
                    console.log("File has been successfully removed!!");
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

</script>
