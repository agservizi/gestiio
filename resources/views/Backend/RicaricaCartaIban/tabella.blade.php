<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th>Nominativo</th>
            <th>Codice Fiscale</th>
            <th>IBAN</th>
            <th>Intestatario IBAN</th>
            <th>Carta</th>
            <th>Telefono</th>
            <th>Inserito il</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr>
                <td>{{$record->nominativo()}}</td>
                <td>{{$record->codice_fiscale}}</td>
                <td class="font-monospace fw-bold">{{$record->iban}}</td>
                <td>{{$record->intestatario_iban}}</td>
                <td>{{$record->carta}}</td>
                <td>{{$record->telefono}}</td>
                <td>{{$record->created_at?->format('d/m/Y')}}</td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-light btn-active-light-primary"
                       href="{{action([$controller,'edit'],$record->id)}}">Modifica</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="w-100 text-center">
        {{$records->links()}}
    </div>
@endif
