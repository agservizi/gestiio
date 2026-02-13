@can('admin')
    <livewire:ricerca></livewire:ricerca>
@endcan
<div class="menu menu-rounded menu-column menu-lg-row my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0" id="kt_app_header_menu" data-kt-menu="true">
    @if(Auth::user()->hasAnyPermission(['agente','operatore']))
        <div class="menu-item me-lg-1">
            <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'])}}">
                <span class="menu-title">Dashboard</span>
            </a>
        </div>
    @endcan
</div>
