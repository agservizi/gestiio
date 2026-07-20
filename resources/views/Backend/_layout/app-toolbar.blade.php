<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <!--begin::Toolbar container-->
    <div id="kt_app_toolbar_container" class="app-container {{\App\Http\HelperForMetronic::ktHeaderHeader()}} d-flex align-items-center justify-content-between flex-nowrap gap-3">
        <!--begin::Page title-->
        <div class="page-title d-flex align-items-center flex-shrink-1 min-w-0 me-2">
            <!--begin::Title-->
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 align-items-center my-0 text-truncate">{{$titoloPagina??''}}</h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            @includeWhen(isset($breadcrumbs),'Backend._layout.breadcrumbs')
            <!--end::Breadcrumb-->
        </div>
        <!--end::Page title-->
        <!--begin::Actions-->
        <div class="ui-toolbar-actions min-h-40px flex-shrink-0">
            @yield('toolbar')
        </div>
        <!--end::Actions-->
    </div>
    <!--end::Toolbar container-->
</div>
<!--end::Toolbar-->
