<div class="table-responsive">
    <table class="table align-middle table-row-dashed fw-bold text-gray-600 fs-6 gy-5" id="kt_table_customers_logs">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th> Gestore</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($tipiContratto as $tipoContratto)
            <tr class="">
                <td>
                    <div class="symbol symbol-50px symbol-2by3 me-3">
                        @if($tipoContratto->gestore->logo)
                            <img src="{{$tipoContratto->gestore->immagineLogo()}}" class="" alt="">
                        @endif
                    </div>
                    {{$tipoContratto->nome}}
                </td>
                <td>
                    <a data-target="kt_modal" data-toggle="modal-ajax"
                       class="btn btn-sm btn-light btn-active-light-success"
                       href="{{action([\App\Http\Controllers\Backend\ProfiloController::class,'showListino'],$tipoContratto->id)}}">Vedi listino</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
