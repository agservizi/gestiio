@extends('Backend._layout._main')
@section('toolbar')
@endsection


@section('content')
    @include('Backend.Profilo.show.topBar')
    <div class="d-flex flex-column flex-xl-row">
        <!--begin::Sidebar-->
        <div class="flex-column flex-lg-row-auto w-100 w-xl-400px">
            @include('Backend.Profilo.show.sideBar')
        </div>
        <div class="flex-lg-row-fluid ms-lg-5">
            <div class="card card-flush h-lg-100">
                <div class="card-header">
                    <div class="card-title flex-column">
                        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6">
                            @if(Auth::user()->hasPermissionTo('servizio_contratti_telefonia'))
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#tab_mandati"
                                       data-url="">Mandati</a>
                                </li>
                            @endif

                            @if(false)
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#tab_provvigioni"
                                       data-toggle="tabajax"
                                       data-url="{{action([$controller,'tab'],[$record->id,'tab_provvigioni'])}}">Provvigioni</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#tab_portafoglio"
                                       data-toggle="tabajax"
                                       data-url="{{action([$controller,'tab'],[$record->id,'tab_portafoglio'])}}">Movimenti
                                        portafoglio</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <div class="card-toolbar">

                    </div>
                </div>


                <div class="card-body p-9 pt-5 mb-0">
                    <div class="tab-content" id="myTabContent">
                        @if(Auth::user()->hasPermissionTo('servizio_contratti_telefonia'))
                            <div class="tab-pane fade show active" id="tab_mandati" role="tabpanel">
                                @include('Backend.Profilo.show.tabMandati')
                            </div>
                        @endif
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
                    //scrollTop2.go();


                });

            }


        });
    </script>
@endpush
