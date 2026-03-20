@php
    $collo = old('dati_colli.0', $record->dati_colli[0] ?? []);
    $packageType = old('altri_dati.package_type', $record->altri_dati['package_type'] ?? null);
    $referenceValue = old('altri_dati.package_reference', $record->altri_dati['package_reference'] ?? '');
    $annotationValue = old('altri_dati.annotation', $record->altri_dati['annotation'] ?? '');

    if (!$packageType) {
        $altezza = (int)($collo['altezza'] ?? 0);
        $larghezza = (int)($collo['larghezza'] ?? 0);
        $profondita = (int)($collo['profondita'] ?? 0);
        if ($altezza === 8 && $larghezza === 38 && $profondita === 64) {
            $packageType = 'small';
        } elseif ($altezza === 19 && $larghezza === 38 && $profondita === 64) {
            $packageType = 'medium';
        } elseif ($altezza === 41 && $larghezza === 38 && $profondita === 64) {
            $packageType = 'large';
        } else {
            $packageType = 'custom';
        }
    }
@endphp

<div class="card rounded border border-gray-300 mb-6">
    <div class="card-body p-8">
        <h3 class="fw-bolder fs-1 mb-8">Package</h3>

        <div class="mb-8">
            <label class="form-label fw-semibold fs-6">
                Numero di riferimento
            </label>
            <div class="d-flex align-items-center gap-3">
                <input type="text" name="altri_dati[package_reference]" id="package_reference"
                       class="form-control form-control-solid form-control-lg"
                       value="{{$referenceValue}}" placeholder="Numero di riferimento">
                <button type="button" class="btn btn-icon btn-light-secondary rounded-circle" title="Riferimento stampato sull'etichetta">
                    <i class="fas fa-question-circle"></i>
                </button>
            </div>
        </div>

        <div class="row g-4 mb-8" id="inpost-package-grid">
            @foreach([
                'small' => ['label' => 'Piccolo', 'badge' => 'S', 'h' => 8, 'w' => 38, 'l' => 64, 'weight' => 25],
                'medium' => ['label' => 'Medio', 'badge' => 'M', 'h' => 19, 'w' => 38, 'l' => 64, 'weight' => 25],
                'large' => ['label' => 'Grande', 'badge' => 'L', 'h' => 41, 'w' => 38, 'l' => 64, 'weight' => 25],
                'custom' => ['label' => 'Personalizzato', 'badge' => '', 'h' => null, 'w' => null, 'l' => null, 'weight' => null],
            ] as $type => $package)
                @php($active = $packageType === $type)
                <div class="col-md-6">
                    <label class="inpost-package-card {{$active ? 'is-active' : ''}} {{$type === 'custom' ? 'is-custom' : ''}}" data-package-card="{{$type}}">
                        <input type="radio" class="d-none package-choice" name="altri_dati[package_type]" value="{{$type}}" {{$active ? 'checked' : ''}}>
                        <span class="inpost-package-radio"></span>
                        <span class="inpost-package-content">
                            <span class="d-flex align-items-center gap-2 mb-1">
                                <span class="inpost-package-title">{{$package['label']}}</span>
                                @if($package['badge'])
                                    <span class="badge badge-light-dark">{{$package['badge']}}</span>
                                @endif
                            </span>
                            @if($type !== 'custom')
                                <span class="inpost-package-subtitle">{{$package['h']}} x {{$package['w']}} x {{$package['l']}} cm</span>
                                <span class="inpost-package-subtitle">{{$package['weight']}} kg</span>
                            @else
                                <span class="inpost-package-subtitle">Definisci le tue dimensioni.</span>
                            @endif
                        </span>
                        <span class="inpost-package-icon" aria-hidden="true">
                            <svg width="56" height="56" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13 23.5L32 12L51 23.5V42.5L32 54L13 42.5V23.5Z" stroke="#222" stroke-width="3" stroke-linejoin="round"/>
                                <path d="M13 23.5L32 35L51 23.5" stroke="#222" stroke-width="3" stroke-linejoin="round"/>
                                <path d="M32 35V54" stroke="#222" stroke-width="3" stroke-linejoin="round"/>
                                <path d="M22 29L32 23L42 29" stroke="#FFC700" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </label>
                </div>
            @endforeach
        </div>

        <div id="custom-package-fields" class="row g-4 mb-8" style="{{$packageType === 'custom' ? '' : 'display:none;'}}">
            <div class="col-md-3">
                <label class="form-label fw-semibold fs-6">Altezza</label>
                <input type="text" id="custom_altezza" class="form-control form-control-solid form-control-lg ricalcola"
                       value="{{old('dati_colli.0.altezza', $collo['altezza'] ?? '')}}" placeholder="cm">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold fs-6">Larghezza</label>
                <input type="text" id="custom_larghezza" class="form-control form-control-solid form-control-lg ricalcola"
                       value="{{old('dati_colli.0.larghezza', $collo['larghezza'] ?? '')}}" placeholder="cm">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold fs-6">Lunghezza</label>
                <input type="text" id="custom_profondita" class="form-control form-control-solid form-control-lg ricalcola"
                       value="{{old('dati_colli.0.profondita', $collo['profondita'] ?? '')}}" placeholder="cm">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold fs-6">Peso</label>
                <input type="text" id="custom_peso_reale" class="form-control form-control-solid form-control-lg ricalcola"
                       value="{{old('dati_colli.0.peso_reale', $collo['peso_reale'] ?? '')}}" placeholder="kg">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold fs-5">
                Aggiungi la tua annotazione che verrà stampata sull'etichetta.
            </label>
            <textarea name="altri_dati[annotation]" id="package_annotation"
                      class="form-control form-control-solid form-control-lg"
                      rows="2" placeholder="Annotazione">{{$annotationValue}}</textarea>
        </div>

        <input type="hidden" name="dati_colli[0][altezza]" id="dati_colli_0_altezza" value="{{old('dati_colli.0.altezza', $collo['altezza'] ?? '')}}">
        <input type="hidden" name="dati_colli[0][larghezza]" id="dati_colli_0_larghezza" value="{{old('dati_colli.0.larghezza', $collo['larghezza'] ?? '')}}">
        <input type="hidden" name="dati_colli[0][profondita]" id="dati_colli_0_profondita" value="{{old('dati_colli.0.profondita', $collo['profondita'] ?? '')}}">
        <input type="hidden" name="dati_colli[0][peso_reale]" id="dati_colli_0_peso_reale" value="{{old('dati_colli.0.peso_reale', $collo['peso_reale'] ?? '')}}">
        <input type="hidden" name="dati_colli[0][peso_volumetrico]" id="dati_colli_0_peso_volumetrico" value="{{old('dati_colli.0.peso_volumetrico', $collo['peso_volumetrico'] ?? '')}}">
    </div>
</div>
