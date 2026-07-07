@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        <a class="btn btn-sm btn-primary fw-bold" href="{{action([$controller,'create'])}}">
            <span class="d-md-none">+</span>
            <span class="d-none d-md-block">{{$testoNuovo}}</span>
        </a>
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
                            <th>Nome</th>
                            <th>SKU</th>
                            <th>Prezzo</th>
                            <th>Giacenza</th>
                            <th>Stato</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-4">
                                        @if($record->immagine)
                                            <img src="{{$record->urlImmagine()}}" alt="" class="rounded" style="width:40px;height:40px;object-fit:cover;flex:0 0 auto;">
                                        @endif
                                        <span>{{$record->nome}}</span>
                                    </div>
                                </td>
                                <td>{{$record->sku}}</td>
                                <td>{{importo($record->prezzo, true)}}</td>
                                <td>{{$record->giacenza}}</td>
                                <td>
                                    <span class="badge {{$record->attivo ? 'badge-light-success' : 'badge-light-danger'}}">
                                        {{$record->attivo ? 'Attivo' : 'Disattivo'}}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-light-primary" href="{{action([$controller,'edit'],$record->id)}}">Modifica</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nessun prodotto in catalogo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{$records->links()}}
        </div>
    </div>
@endsection
