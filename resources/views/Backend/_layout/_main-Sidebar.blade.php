<!DOCTYPE html>
<html lang="it">
<head>
    @php($darkMode=Auth::user()->getExtra('darkMode'))
    <base href="/">
    <meta charset="utf-8"/>
    <title>{{$titoloPagina??config('configurazione.tag_title')}}</title>
    <meta name="description" content=""/>
    <meta name="keywords" content=""/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="_token" content="{{csrf_token()}}">
    <link rel="shortcut icon" href="/favicon.png"/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700"/>
    <!--8.1.4-->

    <!--begin::Global Stylesheets Bundle(used by all pages)-->
    <link href="/assets_backend/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="/assets_backend/css/style.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="/assets_backend/css-miei/mio.css?v=3" rel="stylesheet" type="text/css">
    <link href="/assets_backend/css-miei/responsive.css?v=1" rel="stylesheet" type="text/css">
    <!--end::Global Stylesheets Bundle-->

    @stack('customCss')
    @livewireStyles
</head>
<body id="kt_app_body" data-kt-app-layout="{{\App\Http\HelperForMetronic::SIDEBAR_LIGHT_DARK}}-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="false" data-kt-app-sidebar-fixed="true"
      data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true"
      data-kt-app-toolbar-enabled="true" class="app-default"
      data-kt-app-sidebar-minimize="{{Auth::user()->getExtra('aside')}}"
>
<!--begin::Theme mode setup on page load-->
<script>var defaultThemeMode = "light";
    var themeMode;
    if (document.documentElement) {
        if (document.documentElement.hasAttribute("data-theme-mode")) {
            themeMode = document.documentElement.getAttribute("data-theme-mode");
        } else {
            if (localStorage.getItem("data-theme") !== null) {
                themeMode = localStorage.getItem("data-theme");
            } else {
                themeMode = defaultThemeMode;
            }
        }
        if (themeMode === "system") {
            themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        }
        document.documentElement.setAttribute("data-theme", themeMode);
    }</script>
<!--end::Theme mode setup on page load-->
<!--begin::App-->
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <!--begin::Page-->
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <!--begin::Header-->
        <div id="kt_app_header" class="app-header">
            <!--begin::Header container-->
            <div class="app-container {{\App\Http\HelperForMetronic::ktHeaderHeader()}} d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
                @if(\App\Http\HelperForMetronic::SIDEBAR)

                    <!--begin::sidebar mobile toggle-->
                    <div class="d-flex align-items-center d-lg-none ms-n2 me-2" title="Show sidebar menu">
                        <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
                            <!--begin::Svg Icon | path: icons/duotune/abstract/abs015.svg-->
                            <span class="svg-icon svg-icon-1">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M21 7H3C2.4 7 2 6.6 2 6V4C2 3.4 2.4 3 3 3H21C21.6 3 22 3.4 22 4V6C22 6.6 21.6 7 21 7Z" fill="currentColor"/>
										<path opacity="0.3"
                                              d="M21 14H3C2.4 14 2 13.6 2 13V11C2 10.4 2.4 10 3 10H21C21.6 10 22 10.4 22 11V13C22 13.6 21.6 14 21 14ZM22 20V18C22 17.4 21.6 17 21 17H3C2.4 17 2 17.4 2 18V20C2 20.6 2.4 21 3 21H21C21.6 21 22 20.6 22 20Z"
                                              fill="currentColor"/>
									</svg>
								</span>
                            <!--end::Svg Icon-->
                        </div>
                    </div>
                    <!--end::sidebar mobile toggle-->
                    <!--begin::Mobile logo-->
                    <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
                        <a href="/" class="d-lg-none">
                            <img alt="Logo" src="/loghi/logo.png" class="h-30px"/>
                        </a>
                    </div>
                    <!--end::Mobile logo-->
                @else
                    <!--begin::Logo-->
                    <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-lg-15">
                        <a href="/">
                            <img alt="Logo" src="/loghi/logo.png" class="h-20px h-lg-30px app-sidebar-logo-default"/>
                        </a>
                    </div>
                    <!--end::Logo-->
                @endif
                <!--begin::Header wrapper-->
                <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">
                    <!--begin::Menu wrapper-->
                    <div class="app-header-menu app-header-mobile-drawer align-items-stretch" data-kt-drawer="true" data-kt-drawer-name="app-header-menu"
                         data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="250px" data-kt-drawer-direction="end"
                         data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true" data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
                         data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
                        <!--begin::Menu-->
                        @include('Backend._layout.app-header-menu')

                        <!--end::Menu-->
                    </div>
                    <!--end::Menu wrapper-->
                    <!--begin::Navbar-->
                    @include('Backend._layout.app-navbar')
                    <!--end::Navbar-->
                </div>
                <!--end::Header wrapper-->
            </div>
            <!--end::Header container-->
        </div>
        <!--end::Header-->
        <!--begin::Wrapper-->
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <!--begin::Sidebar-->
            @include('Backend._layout.app-sidebar')
            <!--begin::Main-->
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <!--begin::Content wrapper-->
                <div class="d-flex flex-column flex-column-fluid">
                    @include('Backend._layout.app-toolbar')
                    <!--begin::Content-->
                    <div id="kt_app_content" class="app-content flex-column-fluid">
                        <!--begin::Content container-->
                        <div id="kt_app_content_container" class="app-container {{\App\Http\HelperForMetronic::ktHeaderHeader()}}">
                            @yield('content')
                        </div>
                        <!--end::Content container-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Content wrapper-->
                <!--begin::Footer-->
                @include('Backend._layout.app-footer')
                <!--end::Footer-->
            </div>
            <!--end:::Main-->
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::Page-->
</div>
<!--end::App-->
<!--begin::Drawers-->
<!--end::Drawers-->
<!--begin::Engage drawers-->
<!--end::Engage drawers-->
<!--begin::Engage toolbar-->
<!--end::Engage toolbar-->
<!--begin::Scrolltop-->
<div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
    <!--begin::Svg Icon | path: icons/duotune/arrows/arr066.svg-->
    <span class="svg-icon">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect opacity="0.5" x="13" y="6" width="13" height="2" rx="1" transform="rotate(90 13 6)" fill="currentColor"/>
					<path d="M12.5657 8.56569L16.75 12.75C17.1642 13.1642 17.8358 13.1642 18.25 12.75C18.6642 12.3358 18.6642 11.6642 18.25 11.25L12.7071 5.70711C12.3166 5.31658 11.6834 5.31658 11.2929 5.70711L5.75 11.25C5.33579 11.6642 5.33579 12.3358 5.75 12.75C6.16421 13.1642 6.83579 13.1642 7.25 12.75L11.4343 8.56569C11.7467 8.25327 12.2533 8.25327 12.5657 8.56569Z"
                          fill="currentColor"/>
				</svg>
			</span>
    <!--end::Svg Icon-->
</div>
<!--end::Scrolltop-->
<!--begin::Modals-->
@include('Backend._layout.modal')
<!--end::Modals-->

<!--end::Modals-->
<!--begin::Javascript-->
<script>var hostUrl = "assets/";</script>
<!--begin::Global Javascript Bundle(used by all pages)-->
<!--8.1.4-->
<script src="/assets_backend/plugins/global/plugins.bundle.js"></script>
<script src="/assets_backend/js/scripts.bundle.js"></script>
<script src="/assets_backend/js-miei/mieiScript.js?v=10"></script>
<script src="/assets_backend/js-miei/html2canvas.min.js"></script>
<!--end::Global Javascript Bundle-->
@include('Backend._components.flashToast')
@stack('customScript')
<script>

    window.addEventListener('nuove-notifiche', event => {
        $('#nuove').show();
    });
    window.addEventListener('no-notifiche', event => {
        $('#nuove').hide();
    });

    window.addEventListener('beep', event => {
        Swal.fire('Nuove notifiche', 'Hai nuove notifiche da leggere', "success");
    });


    $(function () {

        modalAjax();
        $('.menu-click').click(function () {
            location.href = $(this).attr('href');
        });
        $('#kt_user_menu_dark_mode_toggle').change(function (e) {
            window.location.href = $(this).data('kt-url');
        });
        $('#kt_app_sidebar_toggle').click(function () {
            const active = $(this).hasClass('active');
            $.ajax({
                type: 'GET',
                url: '/metronic/aside',
                success: function (data) {

                },
                error: function (e) {
                    console.log(e);
                }
            });
        });

    });
</script>
@livewireScripts
<!--end::Javascript-->
</body>
</html>
