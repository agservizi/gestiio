<div class="card card-flush h-lg-100 mb-5 mb-xl-10">
    <!--begin::Header-->
    <div class="card-header pt-5">
        <!--begin::Title-->
        <div class="card-title d-flex flex-column">
            <!--begin::Amount-->
            <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{{\App\Models\User::agente()->count()}}</span>
            <!--end::Amount-->
            <!--begin::Subtitle-->
            <span class="text-gray-400 pt-1 fw-semibold fs-6">Agenti</span>
            <!--end::Subtitle-->
        </div>
        <!--end::Title-->
    </div>
    <!--end::Header-->
    <!--begin::Card body-->
    <div class="card-body d-flex flex-column justify-content-end pe-0">
        <!--begin::Title-->
        <span class="fs-6 fw-bolder text-gray-800 d-block mb-2">Eroi di oggi</span>
        <!--end::Title-->
        <!--begin::Users group-->
        <div class="symbol-group symbol-hover flex-nowrap">
            @foreach(\App\Models\User::agente()->contrattiOggi()->get() as $record)
                <div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" title="{{$record->nominativo()}}">
                    <span class="symbol-label bg-warning text-inverse-warning fw-bold">{{$record->iniziali()}}</span>
                </div>
            @endforeach
        </div>
        <!--end::Users group-->
    </div>
    <!--end::Card body-->
</div>
