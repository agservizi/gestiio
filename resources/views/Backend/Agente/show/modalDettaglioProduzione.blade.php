@extends('Backend._components.modal')
@section('content')

    <table class="table table-row-bordered" id="tabella-elenco">
        <tbody>
        <tr>
            <td class="fw-bolder">
                Contratti Telefonia
            </td>
            <td class="text-end fw-bolder">
                {{\App\importo($record->importo_ordini)}}
            </td>
        </tr>
        <tr>
            <td class="fw-bolder">
                Contratti Energia
            </td>
            <td class="text-end fw-bolder">
                {{\App\importo($record->importo_contratti_energia)}}
            </td>
        </tr>

        <tr>
            <td class="fw-bolder">
                Servizi finanziari
            </td>
            <td class="text-end fw-bolder">
                {{\App\importo($record->importo_servizi_finanziari)}}
            </td>
        </tr>

        </tbody>
    </table>

    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Tipo contratto</th>
            <th class="text-end">Contratti</th>
            <th class="">Soglia</th>
            <th class="text-end">Importo</th>
        </tr>
        </thead>
        <tbody>

        @foreach($record->dettaglio_tipi_contratto??[] as $key=>$value)
            <tr>
                <td>
                    {{\App\Models\TipoContratto::find($key)->nome}}
                </td>
                <td class="text-end">
                    {{$value['numero_contratti']}}
                </td>
                <td>
                    {{$value['soglia']}}
                </td>
                <td class="text-end">
                    {{$value['importo']}}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
