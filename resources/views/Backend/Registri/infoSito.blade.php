@extends('Backend._layout._main')
@section('content')
    {{-- Testo tema-safe: niente #080655 + text-white (scompare con palette chiaro / card bg forzato) --}}
    @php
        $widgets = [
            [
                'value' => 'v'.($appVersion ?? config('configurazione.versione', '5.0')),
                'label' => 'Versione Gestiio',
            ],
            [
                'value' => app()->version(),
                'label' => 'Versione Laravel',
            ],
            [
                'value' => phpversion(),
                'label' => 'Versione Php',
            ],
            [
                'value' => in_array('two-factor-authentication', config('fortify.features')) ? 'Abilitata' : 'Disabilitata',
                'label' => 'Autenticazione 2FA',
            ],
            [
                'value' => \App\humanFileSize($stat['allegati_telefonia'] ?? 0),
                'label' => 'Spazio occupato allegati contratti telefonia',
            ],
            [
                'value' => \App\humanFileSize($stat['allegati_energia'] ?? 0),
                'label' => 'Spazio occupato allegati contratti energia',
            ],
            [
                'value' => \App\humanFileSize($stat['allegati_caf_patronato'] ?? 0),
                'label' => 'Spazio occupato allegati caf patronato',
            ],
            [
                'value' => \App\humanFileSize($stat['allegati_visure'] ?? 0),
                'label' => 'Spazio occupato allegati visure',
            ],
            [
                'value' => \App\humanFileSize(\App\Models\File::sum('dimensione_file')),
                'label' => 'Spazio occupato documenti',
            ],
        ];
    @endphp

    <div class="row g-5 g-xl-8">
        @foreach($widgets as $w)
            <div class="col-md-6 col-xl-6 col-xxl-3">
                <div class="card card-flush h-100 border border-gray-300">
                    <div class="card-header pt-5 pb-3">
                        <div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ $w['value'] }}</span>
                            <span class="text-gray-600 pt-2 fw-semibold fs-6">{{ $w['label'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(isset($versionReleases) && $versionReleases->isNotEmpty())
        <div class="card card-flush mt-6">
            <div class="card-header">
                <h3 class="card-title">Storico versioni Gestiio</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead>
                        <tr class="text-start text-muted fw-bold text-uppercase gs-0">
                            <th>Versione</th>
                            <th>Titolo</th>
                            <th>Note</th>
                            <th>Data</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($versionReleases as $release)
                            <tr>
                                <td>
                                    <span class="badge {{ $release->is_major ? 'badge-primary' : 'badge-light-primary' }} fw-bold">
                                        v{{ $release->version }}
                                    </span>
                                </td>
                                <td class="fw-semibold text-gray-800">{{ $release->title ?: '—' }}</td>
                                <td class="text-gray-700" style="white-space: pre-line;">{{ $release->notes ?: '—' }}</td>
                                <td class="text-muted">{{ optional($release->released_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
@push('customScript')

@endpush
