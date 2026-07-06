@extends('Backend._layout._main')
@section('content')
    <div class="card mb-6">
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger">
                    <strong>Controlla i dati inseriti.</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('test_cf_result'))
                @php
                    $result = session('test_cf_result');
                @endphp
                <div class="alert {{ $result['bloccato'] ? 'alert-warning' : 'alert-success' }}">
                    <strong>{{ $result['cf'] }}</strong>
                    {{ $result['bloccato'] ? 'risulta bloccato' : 'non risulta bloccato' }}
                    per {{ $result['modulo'] }}.
                    @if($result['motivi'])
                        Motivi: {{ implode(', ', $result['motivi']) }}.
                    @endif
                </div>
            @endif

            <div class="d-flex flex-wrap gap-2 mb-6">
                <a class="btn btn-sm btn-light-primary" href="{{ route('settings.export') }}">Esporta JSON</a>
                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#importSettings">Importa JSON</button>
                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#testCodiceFiscale">Test codice fiscale</button>
            </div>

            <div class="collapse mb-6" id="importSettings">
                <form method="POST" action="{{ route('settings.import') }}">
                    @csrf
                    <textarea name="settings_json" class="form-control form-control-solid mb-3" rows="8" placeholder="Incolla JSON esportato"></textarea>
                    <button class="btn btn-sm btn-primary">Importa impostazioni</button>
                </form>
            </div>

            <div class="collapse mb-6" id="testCodiceFiscale">
                <form method="POST" action="{{ route('settings.test-codice-fiscale') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Modulo</label>
                        <select name="modulo" class="form-select form-select-solid">
                            <option value="telefonia">Telefonia</option>
                            <option value="energia">Energia</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Codice fiscale</label>
                        <input name="codice_fiscale" class="form-control form-control-solid" placeholder="RSSMRA80A01H501U">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ID gestore</label>
                        <input name="gestore_id" class="form-control form-control-solid" placeholder="opzionale">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">Test</button>
                    </div>
                </form>
            </div>

            <form method="post" action="{{ route('settings.store') }}" class="form-horizontal" role="form">
                @csrf
                <input type="hidden" name="_settings_scope" value="{{ ($isControlliContrattiPage ?? false) ? 'controlli_contratti' : 'all' }}">

                @foreach(config('setting_fields', []) as $section => $fields)
                    <div class="mb-8" @if($section === 'controlli_contratti') id="controlli-contratti" @endif>
                        @if(!(($isControlliContrattiPage ?? false) && $section === 'controlli_contratti'))
                            <h3 class="mb-2">{{ $fields['title'] }}</h3>
                        @endif

                        <p class="fw-bold">{{ $fields['desc'] }}</p>

                        @if($section === 'controlli_contratti')
                            @php
                                $telefoniaFields = collect($fields['elements'])->filter(fn ($field) => \Illuminate\Support\Str::startsWith($field['name'], 'blocco_contratti_telefonia_'));
                                $energiaFields = collect($fields['elements'])->filter(fn ($field) => \Illuminate\Support\Str::startsWith($field['name'], 'blocco_contratti_energia_'));
                            @endphp

                            <div class="row g-8 mb-8">
                                <div class="col-12 col-lg-6">
                                    <div class="accordion" id="accordionGestoriTelefonia">
                                        <div class="accordion-item border rounded bg-light">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGestoriTelefonia">
                                                    Gestori Telefonia
                                                </button>
                                            </h2>
                                            <div id="collapseGestoriTelefonia" class="accordion-collapse collapse" data-bs-parent="#accordionGestoriTelefonia">
                                                <div class="accordion-body pt-3">
                                                    <div class="small text-muted mb-2">Formato regole per gestore: <code>ID_GESTORE: CF1,CF2</code></div>
                                                    <ul class="mb-0 ps-4">
                                                        @forelse(($gestoriTelefonia ?? collect()) as $gestore)
                                                            <li><strong>{{ $gestore->id }}</strong> - {{ $gestore->nome }}</li>
                                                        @empty
                                                            <li>Nessun gestore telefonia disponibile.</li>
                                                        @endforelse
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="accordion" id="accordionGestoriEnergia">
                                        <div class="accordion-item border rounded bg-light">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGestoriEnergia">
                                                    Gestori Energia
                                                </button>
                                            </h2>
                                            <div id="collapseGestoriEnergia" class="accordion-collapse collapse" data-bs-parent="#accordionGestoriEnergia">
                                                <div class="accordion-body pt-3">
                                                    <div class="small text-muted mb-2">Formato regole per gestore: <code>ID_GESTORE: CF1,CF2</code></div>
                                                    <ul class="mb-0 ps-4">
                                                        @forelse(($gestoriEnergia ?? collect()) as $gestore)
                                                            <li><strong>{{ $gestore->id }}</strong> - {{ $gestore->nome }}</li>
                                                        @empty
                                                            <li>Nessun gestore energia disponibile.</li>
                                                        @endforelse
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-8">
                                <div class="col-12 col-lg-6">
                                    <h5 class="mb-4">Telefonia</h5>
                                    @foreach($telefoniaFields as $field)
                                        @includeIf('Backend.Setting.Fields.' . $field['type'])
                                    @endforeach
                                </div>
                                <div class="col-12 col-lg-6">
                                    <h5 class="mb-4">Energia</h5>
                                    @foreach($energiaFields as $field)
                                        @includeIf('Backend.Setting.Fields.' . $field['type'])
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="row">
                                <div class="col-md-8">
                                    @foreach($fields['elements'] as $field)
                                        @includeIf('Backend.Setting.Fields.' . $field['type'])
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

                <button class="btn-primary btn">Salva impostazioni</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Audit impostazioni</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th>Data</th>
                        <th>Utente</th>
                        <th>Impostazione</th>
                        <th>Azione</th>
                        <th>Valore precedente</th>
                        <th>Nuovo valore</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($auditLogs as $log)
                        <tr>
                            <td class="text-nowrap">{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->utente ? $log->utente->nominativo() : ($log->user_id ?? '-') }}</td>
                            <td><code>{{ $log->setting_name }}</code></td>
                            <td>{{ $log->action }}</td>
                            <td><code class="text-break">{{ \Illuminate\Support\Str::limit($log->old_value, 180) }}</code></td>
                            <td><code class="text-break">{{ \Illuminate\Support\Str::limit($log->new_value, 180) }}</code></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('settings.rollback', $log) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-light-warning">Rollback</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Nessuna modifica registrata.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
