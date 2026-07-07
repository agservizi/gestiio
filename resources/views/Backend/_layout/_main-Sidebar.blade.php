<!DOCTYPE html>
<html lang="it">
<head>
    @include('Backend._layout.partials.head')
</head>
<body id="kt_app_body" data-kt-app-layout="{{\App\Http\HelperForMetronic::SIDEBAR_LIGHT_DARK}}-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="false" data-kt-app-sidebar-fixed="true"
      data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true"
      data-kt-app-toolbar-enabled="true" class="app-default"
      data-kt-app-sidebar-minimize="{{Auth::user()->getExtra('aside')}}"
>
@include('Backend._layout.partials.theme-mode')

<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <div id="kt_app_header" class="app-header">
            <div class="app-container {{\App\Http\HelperForMetronic::ktHeaderHeader()}} d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
                @if(\App\Http\HelperForMetronic::SIDEBAR)
                    <div class="d-flex align-items-center d-lg-none ms-n2 me-2" title="Show sidebar menu">
                        <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
                            <span class="svg-icon svg-icon-1">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 7H3C2.4 7 2 6.6 2 6V4C2 3.4 2.4 3 3 3H21C21.6 3 22 3.4 22 4V6C22 6.6 21.6 7 21 7Z" fill="currentColor"/>
                                    <path opacity="0.3"
                                          d="M21 14H3C2.4 14 2 13.6 2 13V11C2 10.4 2.4 10 3 10H21C21.6 10 22 10.4 22 11V13C22 13.6 21.6 14 21 14ZM22 20V18C22 17.4 21.6 17 21 17H3C2.4 17 2 17.4 2 18V20C2 20.6 2.4 21 3 21H21C21.6 21 22 20.6 22 20Z"
                                          fill="currentColor"/>
                                </svg>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
                        <a href="/" class="d-lg-none">
                            <img alt="Logo" src="/loghi/logo.png" class="h-30px"/>
                        </a>
                    </div>
                @else
                    <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-lg-15">
                        <a href="/">
                            <img alt="Logo" src="/loghi/logo.png" class="h-20px h-lg-30px app-sidebar-logo-default"/>
                        </a>
                    </div>
                @endif

                <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">
                    <div class="app-header-menu app-header-mobile-drawer align-items-stretch" data-kt-drawer="true" data-kt-drawer-name="app-header-menu"
                         data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="250px" data-kt-drawer-direction="end"
                         data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true" data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
                         data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
                        @include('Backend._layout.app-header-menu')
                    </div>
                    @include('Backend._layout.app-navbar')
                </div>
            </div>
        </div>

        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            @include('Backend._layout.app-sidebar')

            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    @unless($nascondiToolbar ?? false)
                        @include('Backend._layout.app-toolbar')
                    @endunless
                    <div id="kt_app_content" class="app-content flex-column-fluid">
                        <div id="kt_app_content_container" class="app-container {{\App\Http\HelperForMetronic::ktHeaderHeader()}}">
                            @yield('content')
                        </div>
                    </div>
                </div>
                @include('Backend._layout.app-footer')
            </div>
        </div>
    </div>
</div>

@include('Backend._layout.modal')
@include('Backend._components.imageZoomModal')
@include('Backend._layout.gestiioAiWidget')
@include('Backend._layout.partials.scrolltop')
@include('Backend._layout.partials.scripts', ['includeSegnalaChat' => true, 'includeSidebarEvents' => true, 'includeSidebarToggle' => true])
</body>
</html>
