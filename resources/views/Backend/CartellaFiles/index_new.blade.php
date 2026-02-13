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
                @if($cartellaId)
                    <a class="btn btn-sm btn-primary fw-bold me-2" data-target="kt_modal" data-toggle="modal-ajax"
                       href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['upload-documento',$cartellaId])}}"><span
                                class="d-md-none">+</span><span
                                class="d-none d-md-block">Upload</span></a>
                @endif
                <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax" href="{{action([$controller,'create'],$cartellaId)}}"><span
                            class="d-md-none">+</span><span
                            class="d-none d-md-block">{{$testoNuovo}}</span></a>
            @endcan
        @endisset
    </div>
@endsection
@section('content')

    <div class="card card-flush">
        @if(false)

            <div class="card-header pt-8">
                <div class="card-title">
                    <!--begin::Search-->
                    <div class="d-flex align-items-center position-relative my-1">
                        <!--begin::Svg Icon | path: icons/duotune/general/gen021.svg-->
                        <span class="svg-icon svg-icon-1 position-absolute ms-6">
														<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
															<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)"
                                                                  fill="currentColor"/>
															<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z"
                                                                  fill="currentColor"/>
														</svg>
													</span>
                        <!--end::Svg Icon-->
                        <input type="text" data-kt-filemanager-table-filter="search" class="form-control form-control-solid w-250px ps-15" placeholder="Search Files & Folders"/>
                    </div>
                    <!--end::Search-->
                </div>
                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-end" data-kt-filemanager-table-toolbar="base">
                        <!--begin::Back to folders-->
                        <a href="../../demo1/dist/apps/file-manager/folders.html" class="btn btn-icon btn-light-primary me-3">
                            <!--begin::Svg Icon | path: icons/duotune/arrows/arr078.svg-->
                            <span class="svg-icon svg-icon-2">
															<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
																<rect opacity="0.3" x="12.75" y="4.25" width="12" height="2" rx="1" transform="rotate(90 12.75 4.25)"
                                                                      fill="currentColor"/>
																<path d="M12.0573 6.11875L13.5203 7.87435C13.9121 8.34457 14.6232 8.37683 15.056 7.94401C15.4457 7.5543 15.4641 6.92836 15.0979 6.51643L12.4974 3.59084C12.0996 3.14332 11.4004 3.14332 11.0026 3.59084L8.40206 6.51643C8.0359 6.92836 8.0543 7.5543 8.44401 7.94401C8.87683 8.37683 9.58785 8.34458 9.9797 7.87435L11.4427 6.11875C11.6026 5.92684 11.8974 5.92684 12.0573 6.11875Z"
                                                                      fill="currentColor"/>
																<path opacity="0.3"
                                                                      d="M18.75 8.25H17.75C17.1977 8.25 16.75 8.69772 16.75 9.25C16.75 9.80228 17.1977 10.25 17.75 10.25C18.3023 10.25 18.75 10.6977 18.75 11.25V18.25C18.75 18.8023 18.3023 19.25 17.75 19.25H5.75C5.19772 19.25 4.75 18.8023 4.75 18.25V11.25C4.75 10.6977 5.19771 10.25 5.75 10.25C6.30229 10.25 6.75 9.80228 6.75 9.25C6.75 8.69772 6.30229 8.25 5.75 8.25H4.75C3.64543 8.25 2.75 9.14543 2.75 10.25V19.25C2.75 20.3546 3.64543 21.25 4.75 21.25H18.75C19.8546 21.25 20.75 20.3546 20.75 19.25V10.25C20.75 9.14543 19.8546 8.25 18.75 8.25Z"
                                                                      fill="currentColor"/>
															</svg>
														</span>
                            <!--end::Svg Icon-->
                        </a>
                        <!--end::Back to folders-->
                        <!--begin::Export-->
                        <button type="button" class="btn btn-light-primary me-3" id="kt_file_manager_new_folder">
                            <!--begin::Svg Icon | path: icons/duotune/files/fil013.svg-->
                            <span class="svg-icon svg-icon-2">
														<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
															<path opacity="0.3" d="M10 4H21C21.6 4 22 4.4 22 5V7H10V4Z" fill="currentColor"/>
															<path d="M10.4 3.60001L12 6H21C21.6 6 22 6.4 22 7V19C22 19.6 21.6 20 21 20H3C2.4 20 2 19.6 2 19V4C2 3.4 2.4 3 3 3H9.2C9.7 3 10.2 3.20001 10.4 3.60001ZM16 12H13V9C13 8.4 12.6 8 12 8C11.4 8 11 8.4 11 9V12H8C7.4 12 7 12.4 7 13C7 13.6 7.4 14 8 14H11V17C11 17.6 11.4 18 12 18C12.6 18 13 17.6 13 17V14H16C16.6 14 17 13.6 17 13C17 12.4 16.6 12 16 12Z"
                                                                  fill="currentColor"/>
															<path opacity="0.3"
                                                                  d="M11 14H8C7.4 14 7 13.6 7 13C7 12.4 7.4 12 8 12H11V14ZM16 12H13V14H16C16.6 14 17 13.6 17 13C17 12.4 16.6 12 16 12Z"
                                                                  fill="currentColor"/>
														</svg>
													</span>
                            <!--end::Svg Icon-->New Folder
                        </button>
                        <!--end::Export-->
                        <!--begin::Add customer-->
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_upload">
                            <!--begin::Svg Icon | path: icons/duotune/files/fil018.svg-->
                            <span class="svg-icon svg-icon-2">
														<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
															<path opacity="0.3" d="M10 4H21C21.6 4 22 4.4 22 5V7H10V4Z" fill="currentColor"/>
															<path d="M10.4 3.60001L12 6H21C21.6 6 22 6.4 22 7V19C22 19.6 21.6 20 21 20H3C2.4 20 2 19.6 2 19V4C2 3.4 2.4 3 3 3H9.20001C9.70001 3 10.2 3.20001 10.4 3.60001ZM16 11.6L12.7 8.29999C12.3 7.89999 11.7 7.89999 11.3 8.29999L8 11.6H11V17C11 17.6 11.4 18 12 18C12.6 18 13 17.6 13 17V11.6H16Z"
                                                                  fill="currentColor"/>
															<path opacity="0.3" d="M11 11.6V17C11 17.6 11.4 18 12 18C12.6 18 13 17.6 13 17V11.6H11Z" fill="currentColor"/>
														</svg>
													</span>
                            <!--end::Svg Icon-->Upload Files
                        </button>
                        <!--end::Add customer-->
                    </div>
                    <!--end::Toolbar-->
                    <!--begin::Group actions-->
                    <div class="d-flex justify-content-end align-items-center d-none" data-kt-filemanager-table-toolbar="selected">
                        <div class="fw-bold me-5">
                            <span class="me-2" data-kt-filemanager-table-select="selected_count"></span>Selected
                        </div>
                        <button type="button" class="btn btn-danger" data-kt-filemanager-table-select="delete_selected">Delete Selected</button>
                    </div>
                    <!--end::Group actions-->
                </div>
                <!--end::Card toolbar-->
            </div>
        @endif

        <!--begin::Card body-->
        <div class="card-body" id="elenco-files">
            @include('Backend.CartellaFiles.elenchi')
        </div>
        <!--end::Card body-->
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
                    url: '{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'index'],0)}}',
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
