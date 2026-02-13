@extends('Backend._layout._main')
@section('toolbar')
    <select name="mese" id="mese" data-control="select2" data-hide-search="true"
            class="form-select form-select-solid form-select-sm fw-bolder w-200px">
        @foreach($elencoMesi as $key=>$value)
            <option value="{{$key}}" @selected($key==$mese)>{{$value}}</option>
        @endforeach
    </select>
@endsection

@section('content')
    <div class="row g-5 g-xl-10 ">
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-10">
            <!--begin::Card widget 20-->
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end mb-2 mb-xl-5 h-lg-50"
                 style="background-color: #F1416C;background-image:url('/assets_backend/media/patterns/vector-1.png')">
                <!--begin::Header-->

                @php($percentuale=\App\percentuale($produzioneMese?->conteggio_ordini_in_lavorazione,$produzioneMese?->conteggio_ordini))
                <div class="card-header pt-5">
                    <div class="card-title d-flex flex-column">
                        <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{$produzioneMese?->conteggio_ordini}}</span>
                        <span class="text-white opacity-75 pt-1 fw-semibold fs-6">I tuoi contratti</span>
                    </div>
                    @if($produzioneMese)
                        <div class="d-flex flex-column">
                            <!--begin::Amount-->
                            <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{\App\importo($produzioneMese->importo_totale,true)}}</span>
                            <!--end::Amount-->
                            <!--begin::Subtitle-->
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Il tuo guadagno</span>
                            <!--end::Subtitle-->
                        </div>
                    @endif
                    <!--end::Title-->
                </div>

                <!--end::Header-->
                <!--begin::Card body-->
                <div class="card-body d-flex align-items-end pt-0">
                    <!--begin::Progress-->

                    <div class="d-flex align-items-center flex-column mt-3 w-100">
                        <div class="d-flex justify-content-between fw-bold fs-6 text-white opacity-75 w-100 mt-auto mb-2">
                            <span>{{$produzioneMese?->conteggio_ordini_in_lavorazione}} in lavorazione</span>
                            <span>{{$percentuale}}%</span>
                        </div>
                        <div class="h-8px mx-3 w-100 bg-white bg-opacity-50 rounded">
                            <div class="bg-white rounded h-8px" role="progressbar" style="width: {{$percentuale}}%;"
                                 aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <!--end::Progress-->
                </div>
                <!--end::Card body-->
            </div>

            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end mb-2 mb-xl-5"
                 style="background-color: #F1416C;background-image:url('/assets_backend/media/patterns/vector-1.png')">
                <!--begin::Header-->
                @php($guadagno=\App\Models\GuadagnoAgenzia::firstOrNew(['mese'=>$filtroMese,'anno'=>$filtroAnno]))

                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Amount-->
                        <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{\App\importo($guadagno->entrate,true)}}</span>
                        <!--end::Amount-->
                        <!--begin::Subtitle-->
                        <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Entrate</span>
                        <!--end::Subtitle-->
                        <!--end::Subtitle-->
                    </div>
                    <div class="d-flex flex-column">
                        <!--begin::Amount-->
                        <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{\App\importo($guadagno->uscite,true)}}</span>
                        <!--end::Amount-->
                        <!--begin::Subtitle-->
                        <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Uscite</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
                <!--begin::Card body-->
                <div class="card-body d-flex align-items-end pt-0">
                    <!--begin::Progress-->
                    @php($percentuale=\App\percentuale($guadagno->utile,$guadagno->entrate))
                    <div class="d-flex align-items-center flex-column mt-3 w-100">
                        <div class="d-flex justify-content-between fw-bold fs-6 text-white  w-100 mt-auto mb-2">
                            <span class="fs-3">{{\App\importo($guadagno->utile,true)}} utile</span>
                            <span class="opacity-75">{{$percentuale}}%</span>
                        </div>
                        <div class="h-8px mx-3 w-100 bg-white bg-opacity-50 rounded">
                            <div class="bg-white rounded h-8px" role="progressbar" style="width: {{$percentuale}}%;"
                                 aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <!--end::Progress-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card widget 20-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-10">
            <div class="card card-flush mb-5 mb-xl-10 h-lg-100">
                <div class="card-header mt-6">
                    <div class="card-title flex-column">
                        <h3 class="fw-bolder mb-1">Esito finale</h3>
                        <div class="fs-6 fw-bold text-gray-400">Tutti i contratti</div>
                    </div>
                    <div class="card-toolbar">
                    </div>
                </div>
                <div class="card-body p-9 pt-5">
                    <div class="d-flex flex-wrap">
                        <div class="position-relative d-flex flex-center h-150px w-150px me-5 mb-7">
                            <div class="position-absolute translate-middle start-50 top-50 d-flex flex-column flex-center">
                                <span class="fs-2qx fw-bolder">{{$datiTortaEsiti['totale']}}</span>
                                <span class="fs-6 fw-bold text-gray-400">Ordini</span>
                            </div>
                            <canvas id="kt_card_widget_17_chart"></canvas>
                        </div>
                        <div class="d-flex flex-column justify-content-center flex-row-fluid pe-5 mb-5">
                            @for($n=0;$n<count($datiTortaEsiti['labels']);$n++)
                                <div class="d-flex fs-6 fw-bold align-items-center mb-3">
                                    <div class="bullet me-3 h-5px w-15px"
                                         style="background-color: {{$datiTortaEsiti['backgroundColor'][$n]}};"></div>
                                    <div class="text-gray-400">{{$datiTortaEsiti['labels'][$n]}}</div>
                                    <div class="ms-auto fw-bolder text-gray-700">{{$datiTortaEsiti['data'][$n]}}</div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-10">
            @include('Backend.Dashboard.admin.ticket',['records'=>$tikets])
        </div>
        <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-10">
            @include('Backend.Dashboard.linksGestori',['altezza'=>'h-lg-100'])
        </div>
    </div>


    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">


        <div class="col-xxl-6">
            <!--begin::Engage widget 10-->
            <div class="card card-flush h-md-75">
                <div class="card-header border-0 pt-5"><h3 class="card-title align-items-start flex-column"><span
                                class="card-label fw-bold fs-3 mb-1">Contratti recenti</span></h3>
                    <div class="card-toolbar">
                        <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax"
                           href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'create'])}}"><span
                                    class="d-md-none">+</span><span
                                    class="d-none d-md-block">Nuovo contratto</span></a>
                    </div>
                </div>
                <div class="card-body card-scroll py-3">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                            <tr class="fw-bold text-muted">
                                <th class="">Data</th>
                                <th class="min-w-150px">Agente</th>
                                <th class="min-w-140px">Prodotto</th>
                                <th class="min-w-120px text-center">Esito</th>
                                <th class="min-w-100px text-end"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @include('Backend.Dashboard.admin.contratti',['records'=>$contratti])
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-6">
            <div class="card card-flush h-md-75">
                <div class="card-header border-0 pt-5"><h3 class="card-title align-items-start flex-column"><span
                                class="card-label fw-bold fs-3 mb-1">Caf / Patronato</span></h3>
                    <div class="card-toolbar">
                        <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax"
                           href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'create'])}}"><span
                                    class="d-md-none">+</span><span
                                    class="d-none d-md-block">Nuova pratica caf patronato</span></a>
                    </div>
                </div>
                <div class="card-body card-scroll py-3">
                    <div class="table-responsive">
                        <table class="table table-row-bordered" id="tabella-elenco">
                            <thead>
                            <tr class="fw-bolder fs-6 text-gray-800">
                                <th class="">Data</th>
                                <th class="">Tipo pratica</th>
                                <th class="">Esito</th>
                                <th class="">Nominativo</th>
                                <th class="text-center">Azioni</th>
                            </tr>
                            </thead>
                            <tbody>
                            @include('Backend.Dashboard.admin.cafPatronato',['records' => $servizi,'puoModificareEsito'=>\App\Models\CafPatronato::puoModificareEsito(),'puoModificare'=>\App\Models\CafPatronato::puoModificare(),'controller'=>\App\Http\Controllers\Backend\CafPatronatoController::class])
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        $(function () {
            $('#mese').on('select2:select', function (e) {
                location.href = location.pathname + '?mese=' + $(this).val();
            });

            var KTCardsWidget17 = {
                init: function () {
                    !function () {

                        var target = document.getElementById("kt_card_widget_17_chart");
                        if (target) {
                            var datiTortaEsiti =@json($datiTortaEsiti);

                            var s = target.getContext("2d");
                            new Chart(s, {
                                type: "doughnut",
                                data: {
                                    datasets: [{
                                        data: datiTortaEsiti['data'],
                                        backgroundColor: datiTortaEsiti['backgroundColor']
                                    }], labels: datiTortaEsiti['labels']
                                },
                                options: {
                                    chart: {fontFamily: "inherit"},
                                    cutoutPercentage: 75,
                                    responsive: !0,
                                    maintainAspectRatio: !1,
                                    cutout: "75%",
                                    title: {display: !1},
                                    animation: {animateScale: !0, animateRotate: !0},
                                    tooltips: {
                                        enabled: !0,
                                        intersect: !1,
                                        mode: "nearest",
                                        bodySpacing: 5,
                                        yPadding: 10,
                                        xPadding: 10,
                                        caretPadding: 0,
                                        displayColors: !1,
                                        backgroundColor: "#20D489",
                                        titleFontColor: "#ffffff",
                                        cornerRadius: 4,
                                        footerSpacing: 0,
                                        titleSpacing: 0
                                    },
                                    plugins: {legend: {display: !1}}
                                }
                            })
                        }
                    }()
                }
            };
            "undefined" != typeof module && (module.exports = KTCardsWidget17), KTUtil.onDOMContentLoaded((function () {
                KTCardsWidget17.init()
            }));
        });
    </script>
@endpush
