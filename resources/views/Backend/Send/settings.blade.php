@extends('Backend._layout._main')

@section('toolbar')
    <a href="{{ action([$controller, 'dashboard']) }}" class="btn btn-sm btn-light">Dashboard</a>
@endsection

@section('content')
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form method="post" action="{{ action([$controller, 'updateSettings']) }}" class="card">
        @csrf
        <div class="card-header"><h3 class="card-title">Impostazioni SEND</h3></div>
        <div class="card-body row g-5">
            <div class="col-md-4">
                <label class="form-label">Modulo attivo</label>
                <select name="module_enabled" class="form-select">
                    <option value="1" @selected(($settings['module_enabled'] ?? '1') === '1')>Sì</option>
                    <option value="0" @selected(($settings['module_enabled'] ?? '1') === '0')>No</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Metodo assegnazione</label>
                <select name="assignment_method" class="form-select">
                    <option value="least_open" @selected(($settings['assignment_method'] ?? '') === 'least_open')>Meno pratiche aperte</option>
                    <option value="round_robin" @selected(($settings['assignment_method'] ?? '') === 'round_robin')>Round-robin</option>
                    <option value="default_supervisor" @selected(($settings['assignment_method'] ?? '') === 'default_supervisor')>Supervisore predefinito</option>
                    <option value="manual" @selected(($settings['assignment_method'] ?? '') === 'manual')>Solo manuale</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Selezione manuale consentita</label>
                <select name="allow_manual_assignment" class="form-select">
                    <option value="1" @selected(($settings['allow_manual_assignment'] ?? '1') === '1')>Sì</option>
                    <option value="0" @selected(($settings['allow_manual_assignment'] ?? '1') === '0')>No</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Priorità default</label>
                <select name="default_priority" class="form-select">
                    <option value="normale" @selected(($settings['default_priority'] ?? '') === 'normale')>Normale</option>
                    <option value="alta" @selected(($settings['default_priority'] ?? '') === 'alta')>Alta</option>
                    <option value="urgente" @selected(($settings['default_priority'] ?? '') === 'urgente')>Urgente</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Supervisore predefinito</label>
                <select name="default_supervisor_id" class="form-select">
                    <option value="">—</option>
                    @foreach($supervisors as $sup)
                        <option value="{{ $sup->id }}" @selected((string)($settings['default_supervisor_id'] ?? '') === (string)$sup->id)>{{ $sup->nominativo() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Max upload (KB)</label>
                <input type="number" name="max_upload_kb" class="form-control" value="{{ $settings['max_upload_kb'] ?? 20480 }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Versione informativa privacy</label>
                <input type="text" name="privacy_version" class="form-control" value="{{ $settings['privacy_version'] ?? '2026-07-01' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Prezzo cliente (€)</label>
                <input type="number" step="0.01" min="0" name="prezzo_cliente" class="form-control" value="{{ $settings['prezzo_cliente'] ?? 5 }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Prezzo agente / plafond (€)</label>
                <input type="number" step="0.01" min="0" name="prezzo_agente" class="form-control" value="{{ $settings['prezzo_agente'] ?? 4 }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Importo fornitore (€)</label>
                <input type="number" step="0.01" min="0" name="importo_fornitore" class="form-control" value="{{ $settings['importo_fornitore'] ?? 0 }}" required>
                <div class="form-text">Dovuto mensile al fornitore (solo admin; non visibile agli agenti).</div>
            </div>
            <div class="col-12">
                <div class="alert alert-light border mb-0">
                    Provider: <code>{{ config('send.provider') }}</code> ·
                    Integrazione esterna: <code>{{ config('send.integration_enabled') ? 'on' : 'off' }}</code> ·
                    Retention: <code>{{ (int) config('send.retention_days', 0) }} giorni</code>
                    (0 = disabilitata).
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary" type="submit">Salva</button>
        </div>
    </form>
@endsection
