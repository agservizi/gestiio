<!DOCTYPE html>
<html lang="it">
<head>
    @include('Backend._layout.partials.head')
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true"
      data-kt-app-sidebar-fixed="false" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true"
      data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" data-kt-app-toolbar-fixed="true" class="app-default">

@include('Backend._layout.partials.theme-mode')

<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <div id="kt_app_header" class="app-header">
            <div class="app-container {{\App\Http\HelperForMetronic::ktHeaderHeader()}} d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
                <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-lg-15">
                    <a href="/">
                        <img alt="Logo" src="/loghi/logo.png" class="h-20px h-lg-35px app-sidebar-logo-default"/>
                    </a>
                </div>

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
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    @unless($nascondiToolbar ?? false)
                        @include('Backend._layout.app-toolbar')
                    @endunless
                    <div id="kt_app_content" class="app-content flex-column-fluid">
                        <div id="kt_app_content_container" class="app-container {{$container??\App\Http\HelperForMetronic::ktHeaderHeader()}}">
                            @yield('content')
                        </div>
                    </div>
                </div>
                @unless($nascondiFooter ?? false)
                    @include('Backend._layout.app-footer')
                @endunless
            </div>
        </div>
    </div>
</div>

@include('Backend._layout.modal')
@include('Backend._components.imageZoomModal')
@include('Backend._layout.gestiioAiWidget')
@include('Backend._layout.partials.scrolltop')
@include('Backend._layout.partials.scripts')
</body>
</html>
