@php($tipoNomeCatasto = strtolower((string)($tipoServizio->nome ?? '')))
@php($defaultEntitaCatasto = str_contains($tipoNomeCatasto,'soggetto') ? 'soggetto' : 'immobile')
@php($entitaCatasto = old('catasto_entita', $catastoData['entita'] ?? $defaultEntitaCatasto))
@php($isTerreniCatasto = str_contains($tipoNomeCatasto,'terren'))
@php($isStoricaCatasto = str_contains($tipoNomeCatasto,'storic'))

<div class="separator separator-dashed my-6"></div>
<h3 class="card-title mb-4">Dati catastali</h3>

<div class="alert alert-info d-flex align-items-start mb-5 p-4">
    <i class="fas fa-info-circle mt-1 me-3"></i>
    <div class="small">
        <div class="fw-bold mb-2">Guida compilazione rapida</div>
        <div>
            <span class="badge badge-light-primary me-2">Immobile</span>
            Compila almeno: <strong>Provincia, Comune, Foglio, Particella</strong> (Subalterno consigliato).
        </div>
        <div class="mt-1">
            <span class="badge badge-light-success me-2">Soggetto</span>
            Usa <strong>ID soggetto</strong> oppure <strong>CF/P.IVA + Provincia</strong>.
        </div>
        <div class="mt-1">
            <span class="badge badge-light-warning me-2">Storica</span>
            Verrà inviata come <code>tipo_visura=storica</code>.
        </div>
        <div class="mt-1">
            <span class="badge badge-light-dark me-2">Terreni</span>
            Verrà inviato <code>tipo_catasto=T</code> (altrimenti <code>F</code> fabbricati).
        </div>
    </div>
</div>

<div class="row mb-2">
    <div class="col-md-12">
        <div class="text-muted small">
            Tipo attuale:
            <strong>{{ $isStoricaCatasto ? 'Storica' : 'Ordinaria' }}</strong>,
            Catasto:
            <strong>{{ $isTerreniCatasto ? 'Terreni (T)' : 'Fabbricati (F)' }}</strong>.
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <label class="form-label required">Provincia catastale</label>
        <input type="text"
               name="provincia_catasto"
               id="provincia_catasto"
               maxlength="80"
               class="form-control @error('provincia_catasto') is-invalid @enderror text-uppercase"
               value="{{ old('provincia_catasto', $catastoData['provincia'] ?? '') }}"
               required>
        @error('provincia_catasto')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-8">
        <label class="form-label required">Comune catastale</label>
        <input type="text"
               name="comune_catasto"
               id="comune_catasto"
               maxlength="120"
               class="form-control @error('comune_catasto') is-invalid @enderror"
               value="{{ old('comune_catasto', $catastoData['comune'] ?? $record->citta ?? '') }}"
               required>
        @error('comune_catasto')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mt-2">
    <div class="col-md-4">
        <label class="form-label">Tipo ricerca</label>
        <select name="catasto_entita" id="catasto_entita" class="form-select @error('catasto_entita') is-invalid @enderror">
            <option value="immobile" {{$entitaCatasto==='immobile'?'selected':''}}>Immobile</option>
            <option value="soggetto" {{$entitaCatasto==='soggetto'?'selected':''}}>Soggetto</option>
        </select>
        @error('catasto_entita')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div id="catasto_entita_hint" class="form-text">Per ricerca per immobile servono Foglio e Particella.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">ID immobile (se disponibile)</label>
        <input type="text"
               name="id_immobile_catasto"
               id="id_immobile_catasto"
               maxlength="60"
               class="form-control @error('id_immobile_catasto') is-invalid @enderror"
               value="{{ old('id_immobile_catasto', $catastoData['id_immobile'] ?? '') }}">
        @error('id_immobile_catasto')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Se valorizzato, riduce errori di individuazione unità.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">ID soggetto (se disponibile)</label>
        <input type="text"
               name="id_soggetto_catasto"
               id="id_soggetto_catasto"
               maxlength="60"
               class="form-control @error('id_soggetto_catasto') is-invalid @enderror"
               value="{{ old('id_soggetto_catasto', $catastoData['id_soggetto'] ?? '') }}">
        @error('id_soggetto_catasto')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Per ricerca soggetto è preferito rispetto a CF/P.IVA.</div>
    </div>
</div>

<div id="catasto_immobile_fields" class="row mt-2">
    <div class="col-md-3">
        <label class="form-label required">Foglio</label>
        <input type="text"
               name="foglio_catasto"
               id="foglio_catasto"
               maxlength="20"
               class="form-control @error('foglio_catasto') is-invalid @enderror"
               value="{{ old('foglio_catasto', $catastoData['foglio'] ?? '') }}">
        @error('foglio_catasto')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label required">Particella</label>
        <input type="text"
               name="particella_catasto"
               id="particella_catasto"
               maxlength="20"
               class="form-control @error('particella_catasto') is-invalid @enderror"
               value="{{ old('particella_catasto', $catastoData['particella'] ?? '') }}">
        @error('particella_catasto')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Subalterno</label>
        <input type="text"
               name="subalterno_catasto"
               id="subalterno_catasto"
               maxlength="20"
               class="form-control @error('subalterno_catasto') is-invalid @enderror"
               value="{{ old('subalterno_catasto', $catastoData['subalterno'] ?? '') }}">
        @error('subalterno_catasto')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Sezione</label>
        <input type="text"
               name="sezione_catasto"
               id="sezione_catasto"
               maxlength="20"
               class="form-control @error('sezione_catasto') is-invalid @enderror"
               value="{{ old('sezione_catasto', $catastoData['sezione'] ?? '') }}">
        @error('sezione_catasto')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Sezione urbana</label>
        <input type="text"
               name="sezione_urbana_catasto"
               id="sezione_urbana_catasto"
               maxlength="20"
               class="form-control @error('sezione_urbana_catasto') is-invalid @enderror"
               value="{{ old('sezione_urbana_catasto', $catastoData['sezione_urbana'] ?? '') }}">
        @error('sezione_urbana_catasto')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
