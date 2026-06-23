@extends('Backend._components.modal')
@section('content')
    @php($selected=old('tipo_servizio'))
    @if(false)
        <div class="w-100 text-center pt-4">
            <div class="fw-bolder fs-4">in collaborazione con:</div>
        </div>
    @endif
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr class="fw-bold fs-6 text-gray-800">
                <th>Servizio</th>
                <th class="text-end">Prezzo consigliato</th>
                <th class="text-end">Costo</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach(\App\Models\TipoVisura::orderBy('nome')->get() as $servizio)
                <tr>
                    <td>{{$servizio->nome}}</td>
                    <td class="text-end">{{importo($servizio->prezzo_cliente)}}</td>
                    <td class="text-end">{{importo($servizio->prezzo_agente)}}</td>
                    <td class="text-end">
                        @if((float)(Auth::user()->agente->portafoglio_visure ?? 0) >= (float)$servizio->prezzo_agente)
                        <a href="{{action([\App\Http\Controllers\Backend\VisuraController::class,'create'],$servizio->id)}}"
                           class="btn btn-primary btn-sm" style="white-space: nowrap;">Crea pratica</a>
                        @else
                            <a href="{{action([\App\Http\Controllers\Backend\RicaricaPlafonController::class,'show'], ['tab_portafoglio' => \App\Enums\TipiPortafoglioEnum::VISURE->value])}}"
                               class="btn btn-danger btn-sm">Ricarica portafoglio</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
