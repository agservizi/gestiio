@extends('Backend._components.modal')
@section('content')
    @php
        $selected = old('tipo_servizio');
    @endphp
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr class="fw-bold fs-6 text-gray-800">
                <th>Gestore</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($servizi as $servizio)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-2by3 me-3">
                                @if($servizio->logo)
                                    <img src="{{$servizio->immagineLogo()}}" class="" alt="">
                                @endif
                            </div>
                            <div class="d-flex justify-content-start flex-column">
                                {{$servizio->nome}}
                            </div>
                        </div>
                    </td>
                    <td class="text-end">
                        <a href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'create'],$servizio->id)}}"
                           class="btn btn-primary btn-sm" style="white-space: nowrap;">Avanti</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
