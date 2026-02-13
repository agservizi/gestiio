@extends('Backend._layout._main')
@section('toolbar')
    @if( $record->id>1)
        <div class="me-0">
            <a href="#" class="btn btn-sm btn-flex bg-body btn-color-gray-700 btn-active-color-primary fw-bold"
               data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"
               data-kt-menu-flip="top-end">Azioni
                <!--begin::Svg Icon | path: icons/duotone/Navigation/Angle-down.svg-->
                <span class="svg-icon svg-icon-5 m-0">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px"
                     height="24px" viewBox="0 0 24 24" version="1.1">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <polygon points="0 0 24 0 24 24 0 24"/>
                        <path
                                d="M6.70710678,15.7071068 C6.31658249,16.0976311 5.68341751,16.0976311 5.29289322,15.7071068 C4.90236893,15.3165825 4.90236893,14.6834175 5.29289322,14.2928932 L11.2928932,8.29289322 C11.6714722,7.91431428 12.2810586,7.90106866 12.6757246,8.26284586 L18.6757246,13.7628459 C19.0828436,14.1360383 19.1103465,14.7686056 18.7371541,15.1757246 C18.3639617,15.5828436 17.7313944,15.6103465 17.3242754,15.2371541 L12.0300757,10.3841378 L6.70710678,15.7071068 Z"
                                fill="#000000" fill-rule="nonzero"
                                transform="translate(12.000003, 11.999999) rotate(-180.000000) translate(-12.000003, -11.999999)"/>
                    </g>
                </svg>
            </span>
                <!--end::Svg Icon-->
            </a>
            <!--begin::Menu-->
            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-200px py-4"
                 data-kt-menu="true">
                <!--begin::Heading-->
                <!--end::Heading-->
                <div class="menu-item px-3">
                    <a href="{{action([$controller,'edit'],$record->id)}}"
                       class="menu-link px-3">Modifica</a>
                </div>
                <div class="menu-item px-3">
                    <a href="{{action([\App\Http\Controllers\Backend\RicaricaPlafonController::class,'show'],['agente_id'=>$record->id])}}"
                       class="menu-link px-3">Ricarica Portafoglio</a>
                </div>
                <div class="separator mt-3 mb-3 opacity-75"></div>
                <!--begin::Menu item-->
                <div class="menu-item px-3">
                    <a href="{{action([$controller,'azioni'],['id'=>$record->id,'azione'=>'impersona'])}}"
                       class="menu-link px-3 azione">Impersona Agente</a>
                </div>
                <div class="menu-item px-3">
                    <a href="{{action([$controller,'azioni'],['id'=>$record->id,'azione'=>'resetta-password'])}}"
                       class="menu-link px-3 azione">Resetta password 123456</a>
                </div>
                <div class="menu-item px-3">
                    <a href="{{action([$controller,'azioni'],['id'=>$record->id,'azione'=>'invia-mail-password-reset'])}}"
                       class="menu-link px-3 azione">Invia mail password reset</a>
                </div>

                <div class="separator mt-3 mb-3 opacity-75"></div>
                <div class="menu-item px-3">
                    <a href="{{action([$controller,'azioni'],['id'=>$record->id,'azione'=>'abilita-sms'])}}"
                       class="menu-link px-3 azione">Abilita autenticazione 2fa</a>
                </div>
                <div class="menu-item px-3">
                    <a href="{{action([$controller,'azioni'],['id'=>$record->id,'azione'=>'disabilita-sms'])}}"
                       class="menu-link px-3 azione">Disabilita autenticazione 2fa</a>
                </div>
            </div>
            <!--end::Menu 3-->
        </div>
    @endif
@endsection


@section('content')
    @include('Backend.Agente.show.topBar')
    <div class="d-flex flex-column flex-xl-row">
        <!--begin::Sidebar-->
        <div class="flex-column flex-lg-row-auto w-100 w-xl-400px mb-10">
            @include('Backend.Agente.show.sideBar')
        </div>
        <div class="flex-lg-row-fluid ms-lg-5">
            <div class="card card-flush h-lg-100">
                <div class="card-header">
                    <div class="card-title flex-column">
                        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#tab_produzione"
                                   data-toggle="tabajax"
                                   data-url="{{action([$controller,'tab'],[$record->id,'tab_produzione'])}}">Produzione</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link " data-bs-toggle="tab" href="#tab_login" data-toggle="tabajax"
                                   data-url="{{action([$controller,'tab'],[$record->id,'tab_login'])}}">Logins</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab_mandati" data-toggle="tabajax"
                                   data-url="{{action([$controller,'tab'],[$record->id,'tab_mandati'])}}">Mandati</a>
                            </li>
                            @if(false)
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#tab_provvigioni"
                                       data-toggle="tabajax"
                                       data-url="{{action([$controller,'tab'],[$record->id,'tab_provvigioni'])}}">Provvigioni</a>
                                </li>
                            @endif
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab_portafoglio" data-toggle="tabajax"
                                   data-url="{{action([$controller,'tab'],[$record->id,'tab_portafoglio'])}}">Movimenti
                                    portafoglio</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-toolbar">
                    </div>
                </div>
                <div class="card-body p-9 pt-5 mb-0">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="tab_produzione" role="tabpanel">
                            @include('Backend.Agente.show.tabProduzione')
                        </div>
                        <div class="tab-pane fade show" id="tab_login" role="tabpanel">
                        </div>
                        <div class="tab-pane fade show" id="tab_mandati" role="tabpanel">
                        </div>
                        <div class="tab-pane fade show" id="tab_provvigioni" role="tabpanel">
                        </div>
                        <div class="tab-pane fade show" id="tab_portafoglio" role="tabpanel">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('customScript')
    <script>
        $(function () {
            tabAjax();
            $('.azione').click(function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                ajaxAzione(url);
            });

            $(document).on('click', 'a.page-link', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                const container = $(this).closest('.tab-pane').attr('id');
                console.log(container);
                console.log(url);
                ajaxPagination(url, '#' + container);
            });

            function ajaxPagination(url, content) {
                $.ajax({
                    url: url,
                    error: function (xhr, ajaxOptions, thrownError) {
                        var err = eval("(" + xhr.responseText + ")");
                        Swal.fire(
                            "Errore " + xhr.status,
                            err.message,
                            "error"
                        )
                    }
                }).done(function (data) {
                    $(content).html(data);
                    console.log('scrollo su');
                    var scrollElement = document.querySelector(content);
                    new KTScrolltop(scrollElement, {speed: 200, easing: 'easeInOutCubic'}).go();
                });
            }

        });
    </script>
@endpush
