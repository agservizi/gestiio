@extends('Frontend._layout.main')

@section('content')
    <div class="row w-xxl-1100px g-7">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h2 class="text-white fw-bold mb-0">I tuoi contratti</h2>
                <a href="{{ url('/area-personale') }}" class="btn btn-sm btn-light">Torna all'area personale</a>
            </div>
            <div class="card border-0 shadow-none">
                <div class="card-body">
                    @if($records->count() === 0)
                        <div class="alert alert-info mb-0">Non hai ancora contratti associati al tuo account.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-4">
                                <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Tipo</th>
                                    <th>Stato</th>
                                    <th>Data inserimento</th>
                                    <th>Località</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($records as $record)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold"
                                                  style="color: {{ $record->tipoContratto?->gestore?->colore_hex ?? '#3F4254' }};">{{ $record->tipoContratto?->nome ?? '-' }}</span>
                                        </td>
                                        <td>{!! $record->esito?->labelStato() !!}</td>
                                        <td>{{ optional($record->data)->format('d/m/Y') ?: '-' }}</td>
                                        <td>{{ $record->comune?->comuneConTarga() ?? '-' }}</td>
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
    </div>
@endsection

@section('sidebar')
@endsection
