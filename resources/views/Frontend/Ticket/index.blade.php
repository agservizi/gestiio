@extends('Frontend._layout.main')

@section('content')
    <div class="w-100 px-4 px-lg-8">
        <div class="card border-0 shadow-sm mb-6">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="text-gray-900 fw-bold mb-1">I tuoi ticket</h2>
                    <div class="text-muted">Messaggi non letti: <span class="fw-bold">{{ $unreadCount }}</span></div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ action([\App\Http\Controllers\Frontend\TicketController::class, 'create']) }}" data-target="kt_modal" data-toggle="modal-ajax"
                       class="btn btn-primary">Nuovo ticket</a>
                    <a href="{{ url('/area-personale') }}" class="btn btn-light">Torna all'area personale</a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @if($records->count() === 0)
                    <div class="alert alert-info mb-0">Non hai ancora aperto ticket.</div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-4">
                            <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th>Ticket</th>
                                <th>Tipo</th>
                                <th>Stato</th>
                                <th>Aggiornamento</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($records as $record)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $record->uidTicket() }}</div>
                                        <div class="text-muted">{{ $record->oggetto }}</div>
                                    </td>
                                    <td>{{ \App\Models\Ticket::TIPI_TICKETS[$record->tipo] ?? $record->tipo }}</td>
                                    <td>{!! $record->labelStatoTicket() !!}</td>
                                    <td>{{ $record->updated_at->format('d/m/Y H:i') }}</td>
                                    <td class="text-end">
                                        <a data-target="kt_modal" data-toggle="modal-ajax"
                                           class="btn btn-sm btn-light btn-active-light-primary"
                                           href="{{ action([\App\Http\Controllers\Frontend\TicketController::class, 'show'], $record->id) }}">Apri</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $records->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('sidebar')
@endsection
