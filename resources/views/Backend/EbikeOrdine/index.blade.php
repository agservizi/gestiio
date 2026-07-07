@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        @can('ebike-b2b')
            <a class="btn btn-sm btn-primary fw-bold" href="{{action([$controller,'create'])}}">
                <span class="d-md-none">+</span>
                <span class="d-none d-md-block">Nuovo ordine ebike</span>
            </a>
        @endcan
    </div>
@endsection
@section('content')
    @include('Backend._components.alertErrori')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted">
                            <th>Ordine</th>
                            @if($isAdmin)
                                <th>Agente</th>
                            @endif
                            <th>Totale</th>
                            <th>Stato</th>
                            <th>Scadenza spedizione</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>#{{$record->id}}</td>
                                @if($isAdmin)
                                    <td>{{$record->agente?->nominativo() ?? 'Agente #'.$record->agente_id}}</td>
                                @endif
                                <td>{{importo($record->totale, true)}}</td>
                                <td>
                                    <span class="badge {{$record->stato->badge()}}">{{$record->stato->testo()}}</span>
                                    @if($record->scadenzaSuperata())
                                        <span class="badge badge-light-danger">SLA superato</span>
                                    @endif
                                </td>
                                <td>{{$record->scadenza_spedizione?->format('d/m/Y') ?? '-'}}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-light-primary" href="{{action([$controller,'show'],$record->id)}}">Apri</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{$isAdmin ? 6 : 5}}" class="text-center text-muted">Nessun ordine ebike.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{$records->links()}}
        </div>
    </div>
@endsection
