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

    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700"/>
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="/assets_backend/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="/assets_backend/css/style10.bundle.css" rel="stylesheet" type="text/css"/>
    <!--end::Global Stylesheets Bundle-->
    @if(env('APP_ENV')=='production' && \Illuminate\Support\Facades\Auth::id()!=1 && config('configurazione.log_rocket'))
        <script src="https://cdn.lr-in.com/LogRocket.min.js" crossorigin="anonymous"></script>
        <script>
            var nominativo = '{{Auth::user()->nominativo()}}';
            var userId = '{{Auth::id()}}';
            //LogRocket
            window.LogRocket && window.LogRocket.init('{{config('configurazione.log_rocket')}}');
            LogRocket.identify(userId, {
                name: nominativo
            });
        </script>
    @endif
    @stack('customCss')
    @livewireStyles
</head>
<!--end::Head-->
<!--begin::Body-->
<body id="kt_body" class="page-bg">
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
<!--begin::Main-->
<!--begin::Root-->
<div class="d-flex flex-column flex-root">
    <!--begin::Page-->
    <div class="page launcher sidebar-enabled d-flex flex-row flex-column-fluid me-lg-5" id="kt_page">
        <!--begin::Content-->
        <div class="d-flex flex-row-fluid">
            <!--begin::Container-->
            <div class="d-flex flex-column flex-row-fluid align-items-center">
                <!--begin::Menu-->
                <div class="d-flex flex-column flex-column-fluid mb-5 mb-lg-10">
                    <!--begin::Brand-->
                    <div class="d-flex flex-center pt-10 pt-lg-0 mb-10 mb-lg-0 h-lg-225px">
                        @if(false)
                            <!--begin::Sidebar toggle-->
                            <div class="btn btn-icon btn-active-color-primary w-30px h-30px d-lg-none me-4 ms-n15" id="kt_sidebar_toggle">
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
                            <!--end::Sidebar toggle-->
                        @endif
                        <!--begin::Logo-->
                        <a href="/">
                            <img alt="Logo" src="/loghi/logo.png" class="h-70px"/>
                        </a>
                        <!--end::Logo-->
                    </div>
                    <!--end::Brand-->

                    <!--begin::Row-->
                    @yield('content')
                    <!--end::Row-->
                </div>
                <!--end::Menu-->
                <!--begin::Footer-->
                <div class="d-flex flex-column-auto flex-center">
                    <!--begin::Navs-->
                    <ul class="menu fw-semibold order-1">
                        <li class="menu-item">
                            <a href="/policies"
                               class="menu-link text-white opacity-50 opacity-100-hover px-3">Termini e condizioni</a>
                        </li>
                        @if(false)
                            <li class="menu-item">
                                <a href="https://preview.keenthemes.com/html/metronic/docs" target="_blank"
                                   class="menu-link text-white opacity-50 opacity-100-hover px-3">Documentation</a>
                            </li>
                            <li class="menu-item">
                                <a href="https://devs.keenthemes.com" target="_blank" class="menu-link text-white opacity-50 opacity-100-hover px-3">Support</a>
                            </li>
                        @endif
                        <li class="menu-item">
                            <a href="https://www.agenziaplinio.it" target="_blank" class="menu-link text-white opacity-50 opacity-100-hover px-3">AG SERVIZI VIA PLINIO 72</a>
                        </li>
                    </ul>
                    <!--end::Navs-->
                </div>
                <!--end::Footer-->
            </div>
            <!--begin::Content-->
        </div>
        <!--begin::Content-->
        <!--begin::Sidebar-->
        @yield('sidebar')
        <!--end::Sidebar-->
    </div>
    <!--end::Page-->
</div>
<!--end::Root-->
<!--end::Main-->
@if(true)
    <div id="kt_account" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="explore" data-kt-drawer-activate="true" data-kt-drawer-overlay="true"
         data-kt-drawer-width="{default:'350px', 'lg': '350px'}" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_account_toggle"
         data-kt-drawer-close="#kt_engage_demos_close">
        @include('Frontend.AreaUtente.drawerAccount')
    </div>

    <!--begin::Engage drawers-->
    <!--begin::Demos drawer-->
    @include('Frontend.AreaUtente.elencoContratti')
    <!--end::Demos drawer-->
    <!--begin::Help drawer-->
    <div id="kt_help" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="help" data-kt-drawer-activate="true" data-kt-drawer-overlay="true"
         data-kt-drawer-width="{default:'350px', 'md': '525px'}" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_help_toggle" data-kt-drawer-close="#kt_help_close">

        @include('Frontend.AreaUtente.elencoTickets')
    </div>

    <!--end::Help drawer-->
    <!--end::Engage drawers-->
    <!--begin::Engage toolbar-->
    <div class="engage-toolbar d-flex position-fixed px-5 fw-bold zindex-2 top-50 end-0 transform-90 mt-5 mt-lg-20 gap-2">
        <button id="kt_account_toggle" class="engage-help-toggle btn engage-btn shadow-sm px-5 rounded-top-0" data-bs-placement="left"
                data-bs-dismiss="click" data-bs-trigger="hover">Account
        </button>

        <!--begin::Demos drawer toggle-->
        <button id="kt_engage_demos_toggle" class="engage-demos-toggle engage-btn btn shadow-sm fs-6 px-4 rounded-top-0" title="I tuoi contratti" data-bs-toggle="tooltip"
                data-bs-placement="left" data-bs-dismiss="click" data-bs-trigger="hover">
            <span id="kt_engage_demos_label">Contratti</span>
        </button>
        <!--end::Demos drawer toggle-->
        <!--begin::Help drawer toggle-->
        <button id="kt_help_toggle" class="engage-help-toggle btn engage-btn shadow-sm px-5 rounded-top-0" title="I tuoi tickets" data-bs-toggle="tooltip" data-bs-placement="left"
                data-bs-dismiss="click" data-bs-trigger="hover">Tickets
        </button>
        <!--end::Help drawer toggle-->
    </div>
@endif
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
<!--begin::Javascript-->
<script>var hostUrl = "/assets_backend/";</script>
<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script src="/assets_backend/plugins/global/plugins.bundle.js"></script>
<script src="/assets_backend/js/scripts.bundle.js"></script>
<!--end::Global Javascript Bundle-->
<script src="/assets_backend/js-miei/mieiScript.js?v=5"></script>
@stack('customScript')
<script>
    $(function () {
        modalAjax();
    });
</script>
<!--end::Javascript-->
</body>
<!--end::Body-->
</html>
