@extends('Backend._layout._main')
@section('toolbar')
@endsection
@section('content')

    <div id="" class="app-container container-xxl">
        <!-- begin::Invoice 3-->
        <div class="card">
            <!-- begin::Body-->
            <div class="card-body py-20">
                <!-- begin::Wrapper-->
                <div class="mw-lg-950px mx-auto w-100">
                    <!-- begin::Header-->
                    <div class="d-flex justify-content-between flex-column flex-sm-row mb-19">
                        <div class="">
                            <!--begin::Logo-->
                            <img alt="Logo" src="/loghi/logo-aziendale.png" class="h-60px"/>
                            <!--end::Logo-->

                            <!--begin::Text-->
                            <div class="ms-2 fw-bolder fs-2 text-gray-800 mt-2">
                                <div>Gestiio</div>

                            </div>
                            <!--end::Text-->
                        </div>
                        <!--end::Logo-->
                        <div class="text-sm-end">
                            <!--begin::Text-->
                            <div class="text-sm-end mt-2 ">
                                <div class="text-gray-800 fs-1 fw-bolder">PRO FORMA #{{$record->numero}}</div>
                                <div class="text-gray-700 fs-2 fw-bold">Data: {{$record->data->format('d/m/Y')}}</div>
                            </div>
                            <!--end::Text-->
                        </div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Body-->
                    <div class="pb-12">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column gap-7 gap-md-10">
                            <!--begin::Separator-->
                            <div class="separator"></div>
                            <!--begin::Separator-->

                            <!--begin::Billing & shipping-->
                            <div class="d-flex flex-column flex-sm-row gap-7 gap-md-10 fw-bold">
                                <div class="flex-root d-flex flex-column">
                                    <span class="text-gray-700">Spett.le</span>
                                    <span class="fs-6">{{$record->intestazione->denominazione}}
																<br/>{{$record->intestazione->codice_fiscale}}
                                        @if(false)
                                            <br/>Victoria,
                                            <br/>Australia.
                                        @endif
                                    </span>
                                </div>
                                <div class="flex-root d-flex flex-column">

                                </div>
                            </div>
                            <!--end::Billing & shipping-->
                            <!--begin:Order summary-->
                            <div class="d-flex justify-content-between flex-column">
                                <!--begin::Table-->
                                <div class="table-responsive border-bottom mb-9">
                                    <table class="table  table-row-dashed fs-6 gy-5 mb-0">
                                        <thead>
                                        <tr class="border-bottom fs-6 fw-bold text-gray-700">
                                            <th class="min-w-175px pb-2">Descrizione</th>
                                            <th class="min-w-80px text-end pb-2">Quantità</th>
                                            <th class="min-w-100px text-end pb-2">Totale</th>
                                        </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600">
                                        <!--begin::Products-->
                                        @foreach($record->righe as $riga)

                                            <tr>
                                                <!--begin::Product-->
                                                <td>
                                                    <!--begin::Title-->
                                                    <div class="">
                                                        <div class="fw-bold">{{$riga->descrizione}}</div>
                                                        <div class="fs-7 text-gray-700">
                                                            @if($riga->classe)
                                                                @php
                                                                    $var=$riga->classe;
                                                                    $contratti=$var::where('fattura_proforma_id',$record->id)->get();
                                                                @endphp
                                                                @foreach($contratti as $contratto)
                                                                    {{$contratto->nominativo()}}<br>
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <!--end::Title-->
                                                </td>
                                                <!--end::Product-->
                                                <!--begin::SKU-->
                                                <!--end::SKU-->
                                                <!--begin::Quantity-->
                                                <td class="text-end">{{$riga->quantita}}</td>
                                                <!--end::Quantity-->
                                                <!--begin::Total-->
                                                <td class="text-end">{{$riga->imponibile}}</td>
                                                <!--end::Total-->
                                            </tr>
                                        @endforeach
                                        @if(false)
                                            <!--end::Products-->
                                            <!--begin::Subtotal-->
                                            <tr>
                                                <td colspan="2" class="text-end">Imponibile</td>
                                                <td class="text-end">{{\App\importo($record->totale_imponibile)}}</td>
                                            </tr>
                                            <!--end::Subtotal-->
                                            <!--begin::VAT-->
                                            <tr>
                                                <td colspan="2" class="text-end">Iva {{$record->aliquota_iva}}%</td>
                                                <td class="text-end">{{\App\calcolaImposta($record->totale_imponibile,$record->aliquota_iva)}}</td>
                                            </tr>
                                            <!--end::VAT-->
                                            <!--begin::Shipping-->
                                            <tr>
                                                <td colspan="2" class="text-end">Shipping Rate</td>
                                                <td class="text-end">$5.00</td>
                                            </tr>
                                            <!--end::Shipping-->
                                        @endif
                                        <!--begin::Grand total-->
                                        <tr>
                                            <td colspan="2" class="fs-3 text-dark fw-bold text-end">Totale</td>
                                            <td class="text-dark fs-3 fw-bolder text-end">{{\App\importo($record->totale_con_iva)}}</td>
                                        </tr>
                                        <!--end::Grand total-->
                                        </tbody>
                                    </table>
                                </div>
                                <!--end::Table-->
                            </div>
                            <!--end:Order summary-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Body-->
                    <!-- begin::Footer-->
                    <div class="d-flex flex-stack flex-wrap mt-lg-20 pt-13">
                        <!-- begin::Actions-->
                        <div class="my-1 me-5">
                            <!-- begin::Pint-->
                            <button type="button" class="btn btn-success my-1 me-12" onclick="window.print();">Print Invoice</button>
                            <!-- end::Pint-->
                            <!-- begin::Download-->
                            <button type="button" class="btn btn-light-success my-1">Download</button>
                            <!-- end::Download-->
                        </div>
                        <!-- end::Actions-->
                    </div>
                    <!-- end::Footer-->
                </div>
                <!-- end::Wrapper-->
            </div>
            <!-- end::Body-->
        </div>
        <!-- end::Invoice 1-->
    </div>
@endsection
