@extends('Backend._layout._main')
@section('content')
    <div class="row  ">
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3  ">
            <!--begin::Card widget 16-->
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-center border-0 0 mb-5 " style="background-color: #080655">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Amount-->
                        <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{app()->version()}}</span>
                        <!--end::Amount-->
                        <!--begin::Subtitle-->
                        <span class="text-white opacity-50 pt-1 fw-semibold fs-6">Versione Laravel</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
            </div>
        </div>
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3  ">
            <!--begin::Card widget 16-->
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-center border-0  mb-5 " style="background-color: #080655">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Amount-->
                        <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{phpversion()}}</span>
                        <!--end::Amount-->
                        <!--begin::Subtitle-->
                        <span class="text-white opacity-50 pt-1 fw-semibold fs-6">Versione Php</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
            </div>
        </div>
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3  ">
            <!--begin::Card widget 16-->
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-center border-0  mb-5 " style="background-color: #080655">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Amount-->
                        <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{in_array('two-factor-authentication',config('fortify.features'))?'Abilitata':'Disabilitata'}}</span>
                        <!--end::Amount-->
                        <!--begin::Subtitle-->
                        <span class="text-white opacity-50 pt-1 fw-semibold fs-6">Autenticazione Sms</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
            </div>
        </div>
    </div>
    <div class="row  mt-6">
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3  ">
            <!--begin::Card widget 16-->
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-center border-0  mb-5 " style="background-color: #080655">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Amount-->
                        <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{\App\humanFileSize($stat['allegati_telefonia'])}}</span>
                        <!--end::Amount-->
                        <!--begin::Subtitle-->
                        <span class="text-white opacity-50 pt-1 fw-semibold fs-6">Spazio occupato allegati contratti telefonia</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
            </div>
        </div>
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3  ">
            <!--begin::Card widget 16-->
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-center border-0  mb-5 " style="background-color: #080655">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Amount-->
                        <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{\App\humanFileSize($stat['allegati_energia'])}}</span>
                        <!--end::Amount-->
                        <!--begin::Subtitle-->
                        <span class="text-white opacity-50 pt-1 fw-semibold fs-6">Spazio occupato allegati contratti energia</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
            </div>
        </div>
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3  ">
            <!--begin::Card widget 16-->
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-center border-0  mb-5 " style="background-color: #080655">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Amount-->
                        <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{\App\humanFileSize($stat['allegati_caf_patronato'])}}</span>
                        <!--end::Amount-->
                        <!--begin::Subtitle-->
                        <span class="text-white opacity-50 pt-1 fw-semibold fs-6">Spazio occupato allegati caf patronato</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
            </div>
        </div>
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3  ">
            <!--begin::Card widget 16-->
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-center border-0  mb-5 " style="background-color: #080655">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Amount-->
                        <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{\App\humanFileSize( $stat['allegati_visure'])}}</span>
                        <!--end::Amount-->
                        <!--begin::Subtitle-->
                        <span class="text-white opacity-50 pt-1 fw-semibold fs-6">Spazio occupato allegati visure</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
            </div>
        </div>
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3  ">
            <!--begin::Card widget 16-->
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-center border-0  mb-5 " style="background-color: #080655">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Amount-->
                        <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{\App\humanFileSize(\App\Models\File::sum('dimensione_file'))}}</span>
                        <!--end::Amount-->
                        <!--begin::Subtitle-->
                        <span class="text-white opacity-50 pt-1 fw-semibold fs-6">Spazio occupato documenti</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
            </div>
        </div>
    </div>
@endsection
@push('customScript')

@endpush
