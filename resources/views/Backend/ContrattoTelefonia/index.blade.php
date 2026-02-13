@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        @include('Backend._components.ricercaIndex')
        @if(Auth::user()->hasAnyPermission(['admin','agente','operatore','supervisore']))
            <!--begin::Filtri-->
            <div class="me-4">
                <!--begin::Menu-->
                <a href="#"
                   class="btn btn-sm {{$conFiltro?'btn-success':'bg-body'}} btn-flex btn-light btn-active-primary fw-bolder"
                   data-kt-menu-trigger="click"
                   data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                    <!--begin::Svg Icon | path: icons/duotune/general/gen031.svg-->
                    <span class="svg-icon svg-icon-6 svg-icon-muted me-1">
												<svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                     xmlns="http://www.w3.org/2000/svg">
													<path d="M19.0759 3H4.72777C3.95892 3 3.47768 3.83148 3.86067 4.49814L8.56967 12.6949C9.17923 13.7559 9.5 14.9582 9.5 16.1819V19.5072C9.5 20.2189 10.2223 20.7028 10.8805 20.432L13.8805 19.1977C14.2553 19.0435 14.5 18.6783 14.5 18.273V13.8372C14.5 12.8089 14.8171 11.8056 15.408 10.964L19.8943 4.57465C20.3596 3.912 19.8856 3 19.0759 3Z"
                                                          fill="currentColor"></path>
												</svg>
											</span>
                    <!--end::Svg Icon-->Filtri</a>

                <div class="menu menu-sub menu-sub-dropdown w-250px w-md-350px" data-kt-menu="true" id="filtri-drop">
                    @include('Backend.ContrattoTelefonia.indexFiltri')
                </div>
            </div>
            <!--end::Filtri-->
            @isset($ordinamenti)
                <div class="me-4 d-none d-md-block">
                    <button class="btn btn-sm btn-icon bg-body btn-color-gray-700 btn-active-primary"
                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"
                            data-kt-menu-flip="top-end">
                        <i class="bi bi-sort-down fs-3"></i>
                    </button>
                    <!--begin::Menu 3-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-bold w-200px py-3"
                         data-kt-menu="true">
                        <!--begin::Heading-->
                        <div class="menu-item px-3">
                            <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Ordinamento</div>
                        </div>
                        @foreach($ordinamenti as $key=>$ordinamento)
                            <div class="menu-item px-3">
                                <a href="{{Request::url()}}?orderBy={{$key}}"
                                   class="menu-link flex-stack px-3">{{$ordinamento['testo']}}
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
                <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax"
                   href="{{action([$controller,'create'])}}"><span
                            class="d-md-none">+</span><span
                            class="d-none d-md-block">{{$testoNuovo}}</span></a>
            @endisset
        @endif
    </div>
@endsection
@section('content')
    @if(($contrattiFermiCount ?? 0) > 0)
        <div class="alert alert-warning d-flex align-items-center mb-5">
            <span class="svg-icon svg-icon-2hx svg-icon-warning me-4">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.3" d="M2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12Z" fill="currentColor"/>
                    <path d="M12 7C12.5523 7 13 7.44772 13 8V13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13V8C11 7.44772 11.4477 7 12 7Z" fill="currentColor"/>
                    <path d="M12 16C12.5523 16 13 16.4477 13 17C13 17.5523 12.5523 18 12 18C11.4477 18 11 17.5523 11 17C11 16.4477 11.4477 16 12 16Z" fill="currentColor"/>
                </svg>
            </span>
            <div class="d-flex flex-column flex-grow-1">
                <span class="fw-bold">{{ number_format($contrattiFermiCount) }} contratti fermi da almeno {{ $giorniFermo }} giorni</span>
                <span class="text-muted">Stato bozza o da gestire.</span>
            </div>
            <a href="{{ request()->fullUrlWithQuery(['solo_fermi' => 1, 'giorni_fermo' => $giorniFermo]) }}" class="btn btn-sm btn-warning">Vedi solo fermi</a>
        </div>
    @endif

    <div class="card card-flush pt-4">
        <div class="card-body pt-0 pb-5 fs-6" id="tabella">
            @include('Backend.ContrattoTelefonia.tabella')
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        var indexUrl = '{{action([$controller,'index'])}}';

        $(function () {
            searchHandler();
            select2UniversaleBackend('agente', 'un agente', 1, 'agente_id');
            select2UniversaleBackend('gestore', 'un gestore', 1, 'gestore_id');
            $(document).on("click", ".duplica", function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                Swal.fire({
                    title: "Sei sicuro?",
                    text: 'Sei sicuro di voler duplicare questo contratto?',
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Si!",
                    cancelButtonText: "No",
                    reverseButtons: true,
                    customClass: {
                        confirmButton: "btn btn-success",
                        cancelButton: "btn btn-danger"
                    }
                }).then(function (result) {
                    if (result.value) {

                        location.href = url;

                        return true;


                    } else if (result.dismiss === "cancel") {

                    }
                });
            });
        });
    </script>
@endpush
