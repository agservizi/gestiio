<div class="card mb-5 mb-xl-10">
    <div class="card-body pt-9 pb-0">
        <!--begin::Details-->
        <div class="d-flex flex-wrap flex-sm-nowrap mb-3">
            <!--begin: Pic-->
            <div class="me-7 mb-4">
                <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                    <div class="symbol symbol-50px symbol-circle">
                        <span class="symbol-label bg-success text-inverse-success fw-bolder fs-2hx">{{$record->iniziali()}}</span>
                    </div>
                </div>

            </div>
            <!--end::Pic-->
            <!--begin::Info-->
            <div class="flex-grow-1">
                <!--begin::Title-->
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                    <!--begin::User-->
                    <div class="d-flex flex-column">
                        <!--begin::Name-->
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-gray-900 fs-2 fw-bold me-1">{{$record->nominativo()}}</span>
                            {!! $record->userLevel(false,$record) !!}
                        </div>
                        <!--end::Name-->
                        <!--begin::Info-->
                        <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                            <a href="tel:{{$record->telefono}}"
                               class="d-flex align-items-center text-gray-400 text-hover-primary me-5 mb-2">
                                <!--begin::Svg Icon | path: icons/duotune/communication/com006.svg-->
                                <span class="svg-icon svg-icon-4 me-1">
<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-telephone"
     viewBox="0 0 18 18">
  <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
</svg>																</span>
                                <!--end::Svg Icon-->{!! $record->telefono !!}</a>

                            <a href="mailto:{{$record->email}}"
                               class="d-flex align-items-center text-gray-400 text-hover-primary mb-2">
                                <!--begin::Svg Icon | path: icons/duotune/communication/com011.svg-->
                                <span class="svg-icon svg-icon-4 me-1">
																	<svg width="24" height="24" viewBox="0 0 24 24"
                                                                         fill="none" xmlns="http://www.w3.org/2000/svg">
																		<path opacity="0.3"
                                                                              d="M21 19H3C2.4 19 2 18.6 2 18V6C2 5.4 2.4 5 3 5H21C21.6 5 22 5.4 22 6V18C22 18.6 21.6 19 21 19Z"
                                                                              fill="currentColor"/>
																		<path d="M21 5H2.99999C2.69999 5 2.49999 5.10005 2.29999 5.30005L11.2 13.3C11.7 13.7 12.4 13.7 12.8 13.3L21.7 5.30005C21.5 5.10005 21.3 5 21 5Z"
                                                                              fill="currentColor"/>
																	</svg>
																</span>
                                <!--end::Svg Icon-->{{$record->email}}</a>
                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::User-->
                    <!--begin::Actions-->
                    <div class="d-flex my-4">

                    </div>
                    <!--end::Actions-->
                </div>
                <!--end::Title-->

                <!--begin::Stats-->
                <div class="d-flex flex-wrap flex-stack">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column flex-grow-1 pe-8">
                        <!--begin::Stats-->
                        <div class="d-flex flex-wrap">
                            @can('servizio_contratti_telefonia')
                                <!--begin::Stat-->
                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-2 mb-3">
                                    <!--begin::Number-->
                                    <div class="d-flex align-items-center">
                                        <div class="fs-2 fw-bold" data-kt-countup="true"
                                             data-kt-countup-value="{{\App\Models\ContrattoTelefonia::count()}}"
                                             data-kt-countup-prefix="">0
                                        </div>
                                    </div>
                                    <!--end::Number-->
                                    <!--begin::Label-->
                                    <div class="fw-semibold fs-6 text-gray-400">Contratti telefonia</div>
                                    <!--end::Label-->
                                </div>
                                <!--end::Stat-->
                            @endcan
                            @can('servizio_contratti_energia')
                                <!--begin::Stat-->
                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-2 mb-3">
                                    <!--begin::Number-->
                                    <div class="d-flex align-items-center">
                                        <div class="fs-2 fw-bold" data-kt-countup="true"
                                             data-kt-countup-value="{{\App\Models\ContrattoEnergia::count()}}"
                                             data-kt-countup-prefix="">0
                                        </div>
                                    </div>
                                    <!--end::Number-->
                                    <!--begin::Label-->
                                    <div class="fw-semibold fs-6 text-gray-400">Contratti energia</div>
                                    <!--end::Label-->
                                </div>
                                <!--end::Stat-->
                            @endcan
                            @can('servizio_attivazioni_sim')
                                <!--begin::Stat-->
                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-2 mb-3">
                                    <!--begin::Number-->
                                    <div class="d-flex align-items-center">
                                        <div class="fs-2 fw-bold" data-kt-countup="true"
                                             data-kt-countup-value="{{\App\Models\AttivazioneSim::count()}}"
                                             data-kt-countup-prefix="">0
                                        </div>
                                    </div>
                                    <!--end::Number-->
                                    <!--begin::Label-->
                                    <div class="fw-semibold fs-6 text-gray-400">Attivazioni sim</div>
                                    <!--end::Label-->
                                </div>
                                <!--end::Stat-->
                            @endcan
                            @can('servizio_caf_patronato')
                                <!--begin::Stat-->
                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-2 mb-3">
                                    <!--begin::Number-->
                                    <div class="d-flex align-items-center">
                                        <div class="fs-2 fw-bold" data-kt-countup="true"
                                             data-kt-countup-value="{{\App\Models\CafPatronato::count()}}"
                                             data-kt-countup-prefix="">0
                                        </div>
                                    </div>
                                    <!--end::Number-->
                                    <!--begin::Label-->
                                    <div class="fw-semibold fs-6 text-gray-400">Caf Patronato</div>
                                    <!--end::Label-->
                                </div>
                                <!--end::Stat-->
                            @endcan
                            @can('servizio_spedizioni')
                                <!--begin::Stat-->
                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-2 mb-3">
                                    <!--begin::Number-->
                                    <div class="d-flex align-items-center">
                                        <div class="fs-2 fw-bold" data-kt-countup="true"
                                             data-kt-countup-value="{{\App\Models\SpedizioneBrt::count()}}"
                                             data-kt-countup-prefix="">0
                                        </div>
                                    </div>
                                    <!--end::Number-->
                                    <!--begin::Label-->
                                    <div class="fw-semibold fs-6 text-gray-400">Spedizioni</div>
                                    <!--end::Label-->
                                </div>
                                <!--end::Stat-->
                            @endcan
                        </div>
                        <!--end::Stats-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Progress-->
                    <div class="d-flex justify-content-end mt-3">
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 mb-3 me-2">
                            <!--begin::Number-->
                            <div class="d-flex align-items-center">
                                <div class="fs-2 fw-bold" data-kt-countup="true"
                                     data-kt-countup-value="{{$record->agente->portafoglio_servizi}}"
                                     data-kt-countup-prefix="€">0
                                </div>
                            </div>
                            <!--end::Number-->
                            <!--begin::Label-->
                            <div class="fw-semibold fs-6 text-gray-400">Portafoglio Servizi</div>
                            <!--end::Label-->
                        </div>
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 mb-3">
                            <!--begin::Number-->
                            <div class="d-flex align-items-center">
                                <div class="fs-2 fw-bold" data-kt-countup="true"
                                     data-kt-countup-value="{{$record->agente->portafoglio_spedizioni}}"
                                     data-kt-countup-prefix="€">0
                                </div>
                            </div>
                            <!--end::Number-->
                            <!--begin::Label-->
                            <div class="fw-semibold fs-6 text-gray-400">Portafoglio Spedizioni</div>
                            <!--end::Label-->
                        </div>
                        @if(false)
                            <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                                <span class="fw-semibold fs-6 text-gray-400">Profile Compleation</span>
                                <span class="fw-bold fs-6">50%</span>
                            </div>
                            <div class="h-5px mx-3 w-100 bg-light mb-3">
                                <div class="bg-success rounded h-5px" role="progressbar" style="width: 50%;"
                                     aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        @endif
                    </div>
                    <!--end::Progress-->
                </div>
                <!--end::Stats-->

            </div>
            <!--end::Info-->
        </div>
        <!--end::Details-->
    </div>
</div>
