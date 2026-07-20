@php
    $footerFrom = 2022;
    $footerTo = (int) now()->year;
    $footerYears = $footerTo > $footerFrom ? $footerFrom.'–'.$footerTo : (string) $footerFrom;
    $footerVersion = (string) config('configurazione.versione', '4.0');
@endphp
{{-- Footer Gestiio v{{ $footerVersion }} --}}
<div id="kt_app_footer" class="app-footer">
    <div class="app-container container-xxl d-flex flex-column flex-md-row flex-center flex-md-stack py-4">
        <div class="text-gray-700 order-1 order-md-1 fs-7 fw-semibold">
            <span class="me-1">© {{ $footerYears }}</span>
            <span class="text-gray-900 fw-bold">Gestiio</span>
            <span class="text-muted mx-2">·</span>
            <span class="text-primary fw-bold">v{{ $footerVersion }}</span>
        </div>
        <div class="order-2 order-md-2">
            <a href="https://www.agenziaplinio.it"
               target="_blank"
               rel="noopener noreferrer"
               class="text-gray-600 text-hover-primary fs-7 fw-semibold">
                AG Servizi · Via Plinio 72
            </a>
        </div>
    </div>
</div>
