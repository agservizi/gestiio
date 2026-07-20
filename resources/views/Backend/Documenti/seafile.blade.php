@extends('Backend._layout._main')

@section('content')
    <style>
        #kt_app_content {
            padding: 0 !important;
            margin: 0 !important;
        }

        #kt_app_content_container {
            max-width: none !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .documenti-seafile-wrap {
            position: relative;
            width: 100%;
            height: calc(100vh - 65px);
            overflow: hidden;
            background: #f5f5f5;
        }

        .documenti-seafile-frame {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .documenti-seafile-banner {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 2;
            padding: .35rem .75rem;
            background: #eef6ff;
            border-bottom: 1px solid #cfe2ff;
            color: #084298;
            font-size: .85rem;
            text-align: center;
        }

        .documenti-seafile-wrap.has-banner .documenti-seafile-frame {
            height: calc(100% - 2rem);
            margin-top: 2rem;
        }

        @media (max-width: 991.98px) {
            .documenti-seafile-wrap {
                height: calc(100vh - 55px);
            }
        }
    </style>

    <div class="documenti-seafile-wrap @if(!empty($isAgenteOnly)) has-banner @endif">
        @if(!empty($isAgenteOnly))
            <div class="documenti-seafile-banner">
                Modalità sola lettura — puoi consultare e scaricare i documenti, non modificarli.
            </div>
        @endif
        <iframe
            id="documenti-seafile-iframe"
            src="{{ $ssoUrl }}"
            title="Documenti"
            class="documenti-seafile-frame"
            referrerpolicy="no-referrer-when-downgrade"
            allow="clipboard-read; clipboard-write"
        ></iframe>
    </div>
@endsection
