@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fw-bold text-gray-600 fs-6 gy-5" id="kt_table_customers_logs">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th class=""> Tipo contratto</th>
                        <th> Attivo</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($records as $gestore)
                        <tr class="">
                            <td>
                                <div class="symbol symbol-50px symbol-2by3 me-3">
                                    @if($gestore->gestore->logo)
                                        <img src="{{$gestore->gestore->immagineLogo()}}" class="" alt="">
                                    @endif
                                </div>
                                {{$gestore->nome}}
                            </td>
                            <td>
                                @if($gestore->gestore->attivo && $gestore->attivo)
                                    <span class="badge badge-success fw-bolder">Abilitato</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a data-target="kt_modal" data-toggle="modal-ajax"
                                   class="btn btn-sm btn-light btn-active-light-primary"
                                   href="{{action([\App\Http\Controllers\Backend\FasciaTipoContrattoController::class,'edit'],[$record->id,$gestore->id])}}">Modifica</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
@push('customScript')
    <script>
        $(function () {
        });
    </script>
@endpush
