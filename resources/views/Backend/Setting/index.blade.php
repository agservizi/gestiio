@extends('Backend._layout._main')
@section('content')
    <div class="card">
        <div class="card-body">

            <div class="row">
                <div class="col-md-8 col-md-offset-2">

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="post" action="{{ route('settings.store') }}" class="form-horizontal" role="form">
                        {!! csrf_field() !!}

                        @if(count(config('setting_fields', [])) )

                            @foreach(config('setting_fields') as $section => $fields)
                                <div class="panel panel-info" @if($section === 'controlli_contratti') id="controlli-contratti" @endif>
                                    @if(!(($isControlliContrattiPage ?? false) && $section === 'controlli_contratti'))
                                        <div class="panel-heading">
                                            <i class="{{ \Illuminate\Support\Arr::get($fields, 'icon', 'glyphicon glyphicon-flash') }}"></i>
                                            <h4> {{ $fields['title'] }}</h4>
                                        </div>
                                    @endif

                                    <p class="fw-bold">{{ $fields['desc'] }}</p>

                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-7  col-md-offset-2">
                                                @foreach($fields['elements'] as $field)
                                                    @includeIf('Backend.Setting.Fields.' . $field['type'] )
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            <!-- end panel for {{ $fields['title'] }} -->
                            @endforeach

                        @endif

                        @if(!array_key_exists('controlli_contratti', config('setting_fields', [])))
                            <div class="panel panel-info" id="controlli-contratti">
                                @if(!($isControlliContrattiPage ?? false))
                                    <div class="panel-heading">
                                        <i class="glyphicon glyphicon-flash"></i>
                                        <h4>Controlli contratti</h4>
                                    </div>
                                @endif

                                <p class="fw-bold">Blocchi automatici su codice fiscale per telefonia/energia (semaforo rosso).</p>

                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-7  col-md-offset-2">
                                            <div class="row mb-6">
                                                <div class="col-lg-4 col-form-label text-lg-end">
                                                    <label for="blocco_contratti_telefonia_verifica_cf_attivo">Telefonia - Controllo CF attivo (1/0)</label>
                                                </div>
                                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                                    <input type="text"
                                                           name="blocco_contratti_telefonia_verifica_cf_attivo"
                                                           value="{{ old('blocco_contratti_telefonia_verifica_cf_attivo', \App\setting('blocco_contratti_telefonia_verifica_cf_attivo', '0')) }}"
                                                           class="form-control form-control-solid"
                                                           id="blocco_contratti_telefonia_verifica_cf_attivo"
                                                           placeholder="Telefonia - Controllo CF attivo (1/0)">
                                                </div>
                                            </div>

                                            <div class="row mb-6">
                                                <div class="col-lg-4 col-form-label text-lg-end">
                                                    <label for="blocco_contratti_telefonia_cf_morosita">Telefonia - CF in morosita</label>
                                                </div>
                                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                                    <textarea name="blocco_contratti_telefonia_cf_morosita"
                                                              class="form-control form-control-solid"
                                                              id="blocco_contratti_telefonia_cf_morosita"
                                                              placeholder="Telefonia - CF in morosita">{{ old('blocco_contratti_telefonia_cf_morosita', \App\setting('blocco_contratti_telefonia_cf_morosita', '')) }}</textarea>
                                                </div>
                                            </div>

                                            <div class="row mb-6">
                                                <div class="col-lg-4 col-form-label text-lg-end">
                                                    <label for="blocco_contratti_telefonia_cf_blacklist">Telefonia - CF in blacklist</label>
                                                </div>
                                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                                    <textarea name="blocco_contratti_telefonia_cf_blacklist"
                                                              class="form-control form-control-solid"
                                                              id="blocco_contratti_telefonia_cf_blacklist"
                                                              placeholder="Telefonia - CF in blacklist">{{ old('blocco_contratti_telefonia_cf_blacklist', \App\setting('blocco_contratti_telefonia_cf_blacklist', '')) }}</textarea>
                                                </div>
                                            </div>

                                            <div class="row mb-6">
                                                <div class="col-lg-4 col-form-label text-lg-end">
                                                    <label for="blocco_contratti_telefonia_cf_credit_check">Telefonia - CF con credit check negativo</label>
                                                </div>
                                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                                    <textarea name="blocco_contratti_telefonia_cf_credit_check"
                                                              class="form-control form-control-solid"
                                                              id="blocco_contratti_telefonia_cf_credit_check"
                                                              placeholder="Telefonia - CF con credit check negativo">{{ old('blocco_contratti_telefonia_cf_credit_check', \App\setting('blocco_contratti_telefonia_cf_credit_check', '')) }}</textarea>
                                                </div>
                                            </div>

                                            <div class="row mb-6">
                                                <div class="col-lg-4 col-form-label text-lg-end">
                                                    <label for="blocco_contratti_energia_verifica_cf_attivo">Energia - Controllo CF attivo (1/0)</label>
                                                </div>
                                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                                    <input type="text"
                                                           name="blocco_contratti_energia_verifica_cf_attivo"
                                                           value="{{ old('blocco_contratti_energia_verifica_cf_attivo', \App\setting('blocco_contratti_energia_verifica_cf_attivo', '0')) }}"
                                                           class="form-control form-control-solid"
                                                           id="blocco_contratti_energia_verifica_cf_attivo"
                                                           placeholder="Energia - Controllo CF attivo (1/0)">
                                                </div>
                                            </div>

                                            <div class="row mb-6">
                                                <div class="col-lg-4 col-form-label text-lg-end">
                                                    <label for="blocco_contratti_energia_cf_morosita">Energia - CF in morosita</label>
                                                </div>
                                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                                    <textarea name="blocco_contratti_energia_cf_morosita"
                                                              class="form-control form-control-solid"
                                                              id="blocco_contratti_energia_cf_morosita"
                                                              placeholder="Energia - CF in morosita">{{ old('blocco_contratti_energia_cf_morosita', \App\setting('blocco_contratti_energia_cf_morosita', '')) }}</textarea>
                                                </div>
                                            </div>

                                            <div class="row mb-6">
                                                <div class="col-lg-4 col-form-label text-lg-end">
                                                    <label for="blocco_contratti_energia_cf_blacklist">Energia - CF in blacklist</label>
                                                </div>
                                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                                    <textarea name="blocco_contratti_energia_cf_blacklist"
                                                              class="form-control form-control-solid"
                                                              id="blocco_contratti_energia_cf_blacklist"
                                                              placeholder="Energia - CF in blacklist">{{ old('blocco_contratti_energia_cf_blacklist', \App\setting('blocco_contratti_energia_cf_blacklist', '')) }}</textarea>
                                                </div>
                                            </div>

                                            <div class="row mb-6">
                                                <div class="col-lg-4 col-form-label text-lg-end">
                                                    <label for="blocco_contratti_energia_cf_credit_check">Energia - CF con credit check negativo</label>
                                                </div>
                                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                                    <textarea name="blocco_contratti_energia_cf_credit_check"
                                                              class="form-control form-control-solid"
                                                              id="blocco_contratti_energia_cf_credit_check"
                                                              placeholder="Energia - CF con credit check negativo">{{ old('blocco_contratti_energia_cf_credit_check', \App\setting('blocco_contratti_energia_cf_credit_check', '')) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row m-b-md">
                            <div class="col-md-12">
                                <button class="btn-primary btn">
                                    Salva impostazioni
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('customScript')
    <script src="/assets_backend/js-miei/autoNumeric.js"></script>

    <script>

        $(function () {
            autonumericImporto('importo');
        });
    </script>
@endpush
