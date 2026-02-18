@if(Auth::user()->agente)
    @php($saldoServizi = (float)(Auth::user()->agente->portafoglio_servizi ?? 0))
    @php($saldoSpedizioni = (float)(Auth::user()->agente->portafoglio_spedizioni ?? 0))
    @php($saldoTotale = $saldoServizi + $saldoSpedizioni)
    <div class="d-flex align-items-center d-none d-md-inline-flex position-relative me-6"
         onmouseenter="this.querySelector('.plafond-hover-panel').classList.remove('d-none')"
         onmouseleave="this.querySelector('.plafond-hover-panel').classList.add('d-none')">
        <span class="badge badge-danger p-2">Plafond {{\App\importo($saldoTotale,true)}}</span>
        <div class="plafond-hover-panel d-none card shadow-sm border border-gray-300 rounded p-4 position-absolute end-0" style="z-index: 105; min-width: 280px; top: calc(100% + 8px);">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-semibold fs-7 text-gray-600">Portafoglio Servizi</span>
                <span class="fw-bold fs-6">{{\App\importo($saldoServizi,true)}}</span>
            </div>
            <div class="separator separator-dashed my-2"></div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold fs-7 text-gray-600">Portafoglio Spedizioni</span>
                <span class="fw-bold fs-6">{{\App\importo($saldoSpedizioni,true)}}</span>
            </div>
        </div>
    </div>
@endif
<div class="d-flex align-items-center ms-1 ms-lg-3" id="kt_header_user_menu_toggle">
    <div class="cursor-pointer" data-kt-menu-trigger="hover" data-kt-menu-attach="parent"
         data-kt-menu-placement="bottom-end"
         data-kt-menu-flip="bottom">
        <div class="">
            <span class="badge bg-success text-inverse-success fw-bolder d-none d-md-block ">{{\Illuminate\Support\Facades\Auth::user()->alias}}</span>
            <div class="symbol symbol-35px symbol-circle d-block d-md-none">
                <span class="symbol-label bg-success text-inverse-success fw-bolder">{{\Illuminate\Support\Facades\Auth::user()->iniziali()}}</span>
            </div>
        </div>
    </div>
    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-primary fw-bold py-4 fs-6 w-275px"
         data-kt-menu="true">
        <div class="menu-item px-3">
            <div class="menu-content d-flex align-items-center px-3">
                <div class="d-flex flex-column">
                    @auth
                        <div class="fw-bolder d-flex align-items-center fs-5"><span
                                    class="pe-2">{{\Illuminate\Support\Facades\Auth::user()->nominativo()}}</span>
                            {!! \App\Http\HelperForMetronic::userLevel(true) !!}
                        </div>
                        <a href="#"
                           class="fw-bold text-muted text-hover-primary fs-7">{!! \Illuminate\Support\Facades\Auth::user()->email !!}</a>
                    @else
                        <div class="fw-bolder d-flex align-items-center fs-5">Nominativo
                            <span class="badge badge-light-success fw-bolder fs-8 px-2 py-1 ms-2">Livello</span></div>
                        <a href="#" class="fw-bold text-muted text-hover-primary fs-7">email</a>
                    @endauth
                </div>
            </div>
        </div>
        <div class="separator my-2"></div>
        <div class="menu-item px-5">
            <a href="/dati-utente" class="menu-link px-5">I tuoi dati</a>
        </div>
        @can('agente')
            <div class="menu-item px-5">
                <a href="{{action([\App\Http\Controllers\Backend\ProfiloController::class,'show'])}}"
                   class="menu-link px-5">Profilo</a>
            </div>
        @endcan
        <div class="separator my-2"></div>
        <div class="menu-item px-5">
            <a href="/logout" class="menu-link px-5">Esci</a>
        </div>
    </div>
</div>
