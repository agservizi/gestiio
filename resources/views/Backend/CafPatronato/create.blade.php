@extends('Backend._components.modal')
@section('content')
    @php($selected=old('tipo_servizio'))
    @if(false)
        <div class="w-100 text-center pt-4">
            <div class="fw-bolder fs-4">in collaborazione con:</div>
        </div>
    @endif
    <div class="table-responsive">
        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-3">
            <thead>
            <tr class="fw-bold text-muted">
                <th>Servizio</th>
                <th class="text-end">Prezzo consigliato</th>
                <th class="text-end">Costo</th>
                <th class="text-end">Azione</th>
            </tr>
            </thead>
            <tbody>
            @foreach(\App\Models\TipoCafPatronato::orderBy('nome')->get() as $servizio)
                <tr>
                    <td class="fw-semibold">{{$servizio->nome}}</td>
                    <td class="text-end fw-bold">{{\App\importo($servizio->prezzo_cliente)}}</td>
                    <td class="text-end text-muted">{{\App\importo($servizio->prezzo_agente)}}</td>
                    <td class="text-end">
                        @if($portafoglioServizi>=$servizio->prezzo_agente)
                        <a href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'create'],$servizio->id)}}"
                           class="btn btn-primary btn-sm" style="white-space: nowrap;">Crea pratica</a>
                        @else
                            <a href="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'create'])}}"
                               class="btn btn-light-danger btn-sm">Ricarica portafoglio</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
