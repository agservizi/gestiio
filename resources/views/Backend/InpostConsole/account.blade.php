@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-light" href="{{action([\App\Http\Controllers\Backend\InpostConsoleController::class, 'deposits'])}}">Deposits</a>
    </div>
@endsection

@section('content')
    <div class="row g-5">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="fw-bolder mb-4">Configurazione</h3>
                    @foreach($config as $label => $value)
                        <div class="d-flex justify-content-between border-bottom py-3">
                            <span class="text-muted">{{$label}}</span>
                            <span class="fw-bold text-end">{{is_scalar($value) ? $value : json_encode($value)}}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h3 class="fw-bolder mb-0">Risposta Account API</h3>
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
        </div>
    </div>
@endsection
