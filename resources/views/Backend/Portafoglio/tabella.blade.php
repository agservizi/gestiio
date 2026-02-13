<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Data</th>
            <th class="">Portafoglio</th>
            <th class="text-end">Importo</th>
            <th class="">Descrizione</th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td class="">
                    {{$record->created_at->format('d/m/Y')}}
                </td>
                <td class="">
                    {{\App\Enums\TipiPortafoglioEnum::tryFrom($record->portafoglio)?->testo()}}
                </td>
                <td class="text-end">{{$record->importo}}</td>
                <td class="">{{$record->descrizione}}</td>

            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator )
    <div class="w-100 text-center">
        {{$records->links()}}
    </div>
@endif
