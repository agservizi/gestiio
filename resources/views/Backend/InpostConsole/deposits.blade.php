@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-light" href="{{action([\App\Http\Controllers\Backend\InpostConsoleController::class, 'account'])}}">Account</a>
    </div>
@endsection

@section('content')
    <div class="row g-5">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="fw-bolder mb-4">Crea deposit</h3>
                    <p class="text-muted">Usa questa console solo con payload validato sul contratto InPost. I depositi sono disponibili solo dove previsti da InPost.</p>
                    <form method="POST" action="{{action([\App\Http\Controllers\Backend\InpostConsoleController::class, 'storeDeposit'])}}">
                        @csrf
                        <label class="form-label fw-bold">Payload JSON</label>
                        <textarea name="payload" rows="14" class="form-control form-control-solid font-monospace @error('payload') is-invalid @enderror">{{$payload}}</textarea>
                        @error('payload')
                            <div class="invalid-feedback d-block">{{$message}}</div>
                        @enderror
                        <button class="btn btn-primary mt-4" type="submit">Invia a InPost</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card mb-5">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h3 class="fw-bolder mb-0">Elenco deposits</h3>
                        <span class="badge {{$response['ok'] ? 'badge-light-success' : 'badge-light-danger'}}">
                            {{$response['ok'] ? 'OK' : 'Errore'}}
                        </span>
                    </div>
                    @if(! $response['ok'] && $response['message'])
                        <div class="alert alert-warning">{{$response['message']}}</div>
                    @endif
                    <pre class="bg-light rounded p-4 mb-0" style="white-space: pre-wrap;">{{json_encode($response['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)}}</pre>
                </div>
            </div>

            @if(session('deposit_response'))
                <div class="card">
                    <div class="card-body">
                        <h3 class="fw-bolder mb-4">Risposta creazione</h3>
                        <pre class="bg-light rounded p-4 mb-0" style="white-space: pre-wrap;">{{json_encode(session('deposit_response'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)}}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
