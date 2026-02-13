@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        @include('Backend._components.ricercaIndex')
        @isset($ordinamenti)
            <div class="me-4 d-none d-md-block">
                <button class="btn btn-sm btn-icon bg-body btn-color-gray-700 btn-active-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"
                        data-kt-menu-flip="top-end">
                    <i class="bi bi-sort-down fs-3"></i>
                </button>
                <!--begin::Menu 3-->
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-bold w-200px py-3" data-kt-menu="true">
                    <!--begin::Heading-->
                    <div class="menu-item px-3">
                        <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Ordinamento</div>
                    </div>
                    @foreach($ordinamenti as $key=>$ordinamento)
                        <div class="menu-item px-3">
                            <a href="{{Request::url()}}?orderBy={{$key}}" class="menu-link flex-stack px-3">{{$ordinamento['testo']}}
                                @if($key==$orderBy)
                                    <i class="fas fa-check ms-2 fs-7"></i>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endisset
        @isset($testoNuovo)
            @can('admin')
                <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax" href="{{action([$controller,'create'])}}"><span
                            class="d-md-none">+</span><span
                            class="d-none d-md-block">{{$testoNuovo}}</span></a>
            @endcan
        @endisset
    </div>
@endsection
@section('content')

    <div class="row g-6 g-xl-9 mb-6 mb-xl-9" id="cartelle">
        @include('Backend.CartellaFiles.elencoCartelle')
    </div>
    <div id="elenco-files">
        @include('Backend.CartellaFiles.elencoFiles',['files'=>\App\Models\File::orderBy('filename_originale')->get()])
    </div>

@endsection
@push('customScript')
    <script>
        var indexUrl = '{{action([$controller,'index'])}}';

        $(function () {

            const $filterSearch = $('#filter_search');
            const $searchSpinner = $('#search-spinner');
            const $serachClear = $('#search-clear');
            $filterSearch.keyup(function () {
                console.log(indexUrl);
                var term = $(this).val();
                $searchSpinner.removeClass('d-none');
                cerca(term);
            });

            $('#search-clear').click(function () {
                cerca('');
                $filterSearch.val('');
                $serachClear.addClass('d-none');
            });

            function cerca(term) {
                $.ajax({
                    url: '{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'show'],0)}}',
                    type: 'GET',
                    dataType: 'json',
                    data: {cerca: term},
                    success: function (response) {
                        $('#elenco-files').html(base64_decode(response.html));

                    }
                });

            }


            handlerCartelle();
            eliminaFileHandler();
        });


        function eliminaFileHandler() {
            $('.elimina-file').click(function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                Swal.fire({
                    title: "Sei sicuro?",
                    text: 'Il file verrà eliminato definitivamente',
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Si, ELIMINA!",
                    cancelButtonText: "No, non eliminare!",
                    reverseButtons: true,
                    customClass: {
                        confirmButton: "btn btn-danger",
                        cancelButton: "btn btn-success"
                    }
                }).then(function (result) {
                    if (result.value) {

                        elimina(url);

                        return true;


                    } else if (result.dismiss === "cancel") {

                    }
                });

            });
        }

        function handlerCartelle() {
            $('.cartella').click(function (e) {
                e.preventDefault();
                const url = $(this).attr('href');
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            $('.nome-cartella').removeClass('text-green-jungle');
                            $(this).find('div.nome-cartella').addClass('text-green-jungle');

                            $('#elenco-files').html(base64_decode(response.html));
                        } else {
                            alert(response.message);
                        }

                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        var err = eval("(" + xhr.responseText + ")");
                        Swal.fire(
                            "Errore " + xhr.status,
                            err.message,
                            "error"
                        )
                    }

                });

            });

        }

    </script>
@endpush
