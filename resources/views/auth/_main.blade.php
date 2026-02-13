<html lang="it">
<head>
    <meta charset="utf-8"/>
    <title>{{$tagTitle??config('configurazione.tag_title')}}</title>
    <meta name="description" content=""/>
    <meta name="keywords" content=""/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">
    <link href="/assets_backend/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="/assets_backend/css/style.bundle.css" rel="stylesheet" type="text/css">
    <link rel="icon" type="image/png" href="/Favicon.png"/>
    @if(env('APP_ENV')=='production' && config('configurazione.log_rocket'))
        <script src="https://cdn.lr-in.com/LogRocket.min.js" crossorigin="anonymous"></script>
        <script>
            //LogRocket
            window.LogRocket && window.LogRocket.init('{{config('configurazione.log_rocket')}}');
        </script>
    @endif
</head>
<body id="kt_body" class="bg-body">
<div class="d-flex flex-column flex-root">
    <div class="d-flex flex-column flex-column-fluid bgi-position-y-bottom position-x-center bgi-no-repeat bgi-size-contain bgi-attachment-fixed"
         style="background-image: url(/images/development-hd.png)">
        <div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
            <a href="/">
                <img alt="Logo" src="/loghi/logo.png" class="h-100px mb-12">
            </a>
            @yield('content')
        </div>
    </div>
</div>
<script src="/assets_backend/plugins/global/plugins.bundle.js"></script>
<script src="/assets_backend/js/scripts.bundle.js"></script>
@stack('customScript')
@include('auth.gdprScript')

</body>
</html>
