<!DOCTYPE html>
<html lang="it" data-theme-mode="">

<head>
    <base href="/">
    <title>{{$titoloPagina??config('configurazione.tag_title')}}</title>
    <meta charset="utf-8"/>
    <meta name="description" content=""/>
    <meta name="keywords" content=""/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700"/>
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(used by all pages)-->
    <link href="/assets_backend/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="/assets_backend/css/style.bundle.css" rel="stylesheet" type="text/css"/>
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

</head>
<!--end::Head-->
<!--begin::Body-->
<body id="kt_body" class="app-blank">
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
    <!--begin::Authentication - Sign-up -->
    <div class="d-flex flex-column flex-lg-row flex-column-fluid">
        <!--begin::Body-->
        <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
            <!--begin::Form-->
            <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                <div class="{{$full??false?'':'w-lg-500px'}} p-10">
                    @yield('content')
                </div>
            </div>
            <!--end::Form-->
            <div class="d-flex flex-center flex-wrap px-5">
                <!--begin::Links-->
                @include('auth.footer')
                <!--end::Links-->
            </div>

        </div>
        <!--end::Body-->
        <!--begin::Aside-->
        <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2" style="background-image: url(/assets_backend/media/misc/auth-bg.png)">
            <!--begin::Content-->
            <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">
                <!--begin::Logo-->
                <a href="/" class="mb-0 mb-lg-12">
                    <img alt="Logo" src="/loghi/logo_white_large.png" class="h-100px mb-12">

                </a>
                <!--end::Logo-->
                <!--begin::Image-->
                <img class="d-none d-lg-block mx-auto w-275px w-md-50 w-xl-500px mb-10 mb-lg-20" src="/assets_backend/media/misc/auth-screens.png" alt=""/>
                <!--end::Image-->
                <!--begin::Title-->
                <h1 class="d-none d-lg-block text-white fs-2qx fw-bolder text-center mb-7">I tuoi contratti, in un unico posto, facile no?</h1>
                <!--end::Title-->
                <!--begin::Text-->
                <div class="d-none d-lg-block text-white fs-4 text-center">
                    Il giusto <span class="opacity-75-hover text-warning fw-bold me-1">crm</span> degli <span
                            class="opacity-75-hover text-warning fw-bold me-1">agenti</span>,<br>
                    per gli <span class="opacity-75-hover text-warning fw-bold me-1">agenti</span>
                    @if(false)
                        In this kind of post,
                        <a href="#" class="opacity-75-hover text-warning fw-bold me-1">the blogger</a>introduces a person they’ve interviewed
                        <br/>and provides some background information about
                        <a href="#" class="opacity-75-hover text-warning fw-bold me-1">the interviewee</a>and their
                        <br/>work following this is a transcript of the interview.

                    @endif
                </div>
                <!--end::Text-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Aside-->
    </div>
    <!--end::Authentication - Sign-up-->
</div>
<!--end::Root-->
<!--end::Main-->
<!--begin::Javascript-->
<script>var hostUrl = "assets/";</script>
<!--begin::Global Javascript Bundle(used by all pages)-->
<script src="/assets_backend/plugins/global/plugins.bundle.js"></script>
<script src="/assets_backend/js/scripts.bundle.js"></script>
<!--end::Global Javascript Bundle-->
@stack('customScript')
<!--end::Javascript-->

@include('auth.gdprScript')

</body>
<!--end::Body-->
</html>
