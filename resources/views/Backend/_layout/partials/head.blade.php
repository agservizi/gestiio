@php($darkMode = Auth::user()->getExtra('darkMode'))
<base href="/">
<meta charset="utf-8"/>
<title>{{$titoloPagina??config('configurazione.tag_title')}}</title>
<meta name="description" content=""/>
<meta name="keywords" content=""/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<meta name="_token" content="{{csrf_token()}}">
<link rel="shortcut icon" href="/favicon.png"/>
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700"/>

<!--begin::Global Stylesheets Bundle(used by all pages)-->
<link href="/assets_backend/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
<link href="/assets_backend/keenicons/duotone/style.css" rel="stylesheet" type="text/css"/>
<link href="/assets_backend/css/style.bundle.css" rel="stylesheet" type="text/css"/>
<link href="/assets_backend/css-miei/mio.css?v=8" rel="stylesheet" type="text/css">
<link href="/assets_backend/css-miei/responsive.css?v=5" rel="stylesheet" type="text/css">
<!--end::Global Stylesheets Bundle-->

@stack('customCss')
@livewireStyles
