<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <!--begin::Toolbar container-->
    <div id="kt_app_toolbar_container" class="app-container {{\App\Http\HelperForMetronic::ktHeaderHeader()}} d-flex flex-stack">
        <!--begin::Page title-->
        <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
            <!--begin::Title-->
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">{{$titoloPagina??''}}</h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            @includeWhen(isset($breadcrumbs),'Backend._layout.breadcrumbs')
            <!--end::Breadcrumb-->
        </div>
        <!--end::Page title-->
        <!--begin::Actions-->
        <div class="d-flex align-items-center gap-2 gap-lg-3 min-h-40px">
            @yield('toolbar')
        </div>
        <!--end::Actions-->
    </div>
    <!--end::Toolbar container-->
</div>
<!--end::Toolbar-->
