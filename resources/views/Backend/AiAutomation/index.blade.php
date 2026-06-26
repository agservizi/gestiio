@extends('Backend._layout._main')

@section('content')
    <div class="card mb-7">
        <div class="card-header border-0 pt-6">
            <div>
                <h2 class="mb-1">Gestiio AI</h2>
                <div class="text-muted">Consigli, richieste e attività AI registrate in Gestiio.</div>
            </div>
            <form method="POST" action="{{action([\App\Http\Controllers\Backend\AiAutomationController::class, 'triggerDashboard'])}}">
                @csrf
                <button class="btn btn-primary">Aggiorna consigli</button>
            </form>
        </div>
    </div>

    <div class="card mb-7">
        <div class="card-header">
            <h3 class="card-title">Attività recenti</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle mb-0">
                    <thead>
                    <tr class="fw-bold text-muted">
                        <th class="ps-6">ID</th>
                        <th>Evento</th>
                        <th>Audience</th>
                        <th>Stato</th>
                        <th>Data</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td class="ps-6">#{{$event->id}}</td>
                            <td>{{$event->event_type}}</td>
                            <td>{{$event->audience}}</td>
                            <td><span class="badge badge-light-primary">{{$event->status}}</span></td>
                            <td>{{$event->created_at?->format('d/m/Y H:i')}}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ps-6 py-8 text-muted">Nessuna attività registrata.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{$suggestions->links()}}
@endsection
