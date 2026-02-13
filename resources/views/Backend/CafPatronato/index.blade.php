@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        @isset($testoCerca)
            @include('Backend._components.ricercaIndex')
        @endisset
        <!--begin::Filtri-->
        <div class="me-4">
            <!--begin::Menu-->
            <a href="#" class="btn btn-sm {{$conFiltro?'btn-success':'bg-body'}} btn-flex btn-light btn-active-primary fw-bolder" data-kt-menu-trigger="click"
               data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                <!--begin::Svg Icon | path: icons/duotune/general/gen031.svg-->
                <span class="svg-icon svg-icon-6 svg-icon-muted me-1">
												<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
													<path d="M19.0759 3H4.72777C3.95892 3 3.47768 3.83148 3.86067 4.49814L8.56967 12.6949C9.17923 13.7559 9.5 14.9582 9.5 16.1819V19.5072C9.5 20.2189 10.2223 20.7028 10.8805 20.432L13.8805 19.1977C14.2553 19.0435 14.5 18.6783 14.5 18.273V13.8372C14.5 12.8089 14.8171 11.8056 15.408 10.964L19.8943 4.57465C20.3596 3.912 19.8856 3 19.0759 3Z"
                                                          fill="currentColor"></path>
												</svg>
											</span>
                <!--end::Svg Icon-->Filtri</a>

            <div class="menu menu-sub menu-sub-dropdown w-250px w-md-350px" data-kt-menu="true" id="filtri-drop">
                @include('Backend.CafPatronato.indexFiltri')
            </div>
        </div>
        <!--end::Filtri-->
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
            <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax" href="{{action([$controller,'create'])}}"><span
                        class="d-md-none">+</span><span
                        class="d-none d-md-block">{{$testoNuovo}}</span></a>
        @endisset
    </div>
@endsection
@section('content')

    @include('Backend._components.alertMessage')

    <div class="row g-4 mb-6">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-flush h-100">
                <div class="card-body py-4">
                    <div class="text-muted fs-7">In lavorazione</div>
                    <div class="fs-2 fw-bold text-warning">{{$kpiInLavorazione ?? 0}}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-flush h-100">
                <div class="card-body py-4">
                    <div class="text-muted fs-7">Bloccate</div>
                    <div class="fs-2 fw-bold text-danger">{{$kpiBloccate ?? 0}}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-flush h-100">
                <div class="card-body py-4">
                    <div class="text-muted fs-7">In scadenza (7 gg)</div>
                    <div class="fs-2 fw-bold text-info">{{$kpiInScadenza ?? 0}}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-flush h-100">
                <div class="card-body py-4">
                    <div class="text-muted fs-7">Concluse</div>
                    <div class="fs-2 fw-bold text-success">{{$kpiConcluse ?? 0}}</div>
                </div>
            </div>
        </div>
    </div>

    @if(($praticheFermiCount ?? 0) > 0)
        <div class="alert alert-warning d-flex align-items-center p-4 mb-6">
            <span class="svg-icon svg-icon-2hx svg-icon-warning me-4">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.3" d="M12 3C7.03 3 3 7.03 3 12C3 16.97 7.03 21 12 21C16.97 21 21 16.97 21 12C21 7.03 16.97 3 12 3Z" fill="currentColor"/>
                    <path d="M11 10H13V16H11V10ZM11 7H13V9H11V7Z" fill="currentColor"/>
                </svg>
            </span>
            <div class="d-flex flex-column">
                <h4 class="mb-1 text-warning">Pratiche ferme rilevate</h4>
                <span>
                    Ci sono <strong>{{$praticheFermiCount}}</strong> pratiche in stato bozza/da gestire da almeno <strong>{{$giorniFermo ?? 7}}</strong> giorni.
                    <a class="fw-bold ms-1" href="{{request()->fullUrlWithQuery(['solo_fermi' => 1, 'giorni_fermo' => $giorniFermo ?? 7])}}">Vedi solo pratiche ferme</a>
                </span>
            </div>
        </div>
    @endif

    <div class="card card-flush pt-4">
        <div class="card-body pt-0 pb-5 fs-6" id="tabella">
            @include('Backend.CafPatronato.tabella')
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        var indexUrl = '{{action([$controller,'index'])}}';

        $(function () {
            searchHandler();
        });
    </script>
@endpush
