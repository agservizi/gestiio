@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex align-items-center py-1">
        <a href="{{ action([\App\Http\Controllers\Backend\CafPatronatoController::class, 'index']) }}" class="btn btn-sm btn-light me-2">
            Torna a CAF
        </a>
    </div>
@endsection

@section('content')
    @include('Backend._components.alertMessage')

    <div class="card card-flush mb-6">
        <div class="card-body py-4 d-flex flex-wrap align-items-center gap-4">
            <div>
                <div class="text-muted fs-7">Totale allegati orfani</div>
                <div class="fs-2 fw-bold text-danger">{{ $totaleOrfani ?? 0 }}</div>
            </div>

            <form method="get" class="d-flex align-items-center gap-2 ms-auto">
                <label for="per_page" class="text-muted fs-7 mb-0">Per pagina</label>
                <select name="per_page" id="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach([25,50,100,200] as $size)
                        <option value="{{ $size }}" @selected((int)($perPage ?? 50) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-body pt-0 pb-5 fs-6">
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle gs-0 gy-3">
                    <thead>
                    <tr class="fw-bold text-muted">
                        <th>ID Allegato</th>
                        <th>ID Pratica</th>
                        <th>Nominativo</th>
                        <th>Codice fiscale</th>
                        <th>File</th>
                        <th>Path DB</th>
                        <th>Tipo</th>
                        <th>Data</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>
                                @if($row->caf_patronato_id)
                                    <a href="{{ action([\App\Http\Controllers\Backend\CafPatronatoController::class, 'show'], ['caf_patronato' => $row->caf_patronato_id]) }}" class="fw-bold">
                                        {{ $row->caf_patronato_id }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ trim(($row->pratica_cognome ?? '') . ' ' . ($row->pratica_nome ?? '')) ?: '-' }}</td>
                            <td>{{ $row->pratica_codice_fiscale ?? '-' }}</td>
                            <td>{{ $row->filename_originale ?? '-' }}</td>
                            <td><span class="text-muted">{{ $row->path_filename ?? '-' }}</span></td>
                            <td>
                                @if((int) $row->per_cliente === 1)
                                    <span class="badge badge-light-primary">Cliente</span>
                                @else
                                    <span class="badge badge-light">Interno</span>
                                @endif
                            </td>
                            <td>{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-8">Nessun allegato orfano rilevato.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $records->links() }}
            </div>
        </div>
    </div>
@endsection
