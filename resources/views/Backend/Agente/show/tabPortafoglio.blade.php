<div class="table-responsive">
    <table class="table align-middle table-row-dashed fw-bold text-gray-600 fs-6 gy-5" id="kt_table_customers_logs">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th> Data</th>
            <th> Portafoglio</th>
            <th> Descrizione</th>
            <th class="text-end"> Importo prima</th>
            <th class="text-end"> Importo movimento</th>
            <th class="text-end"> Importo dopo</th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td> {{$record->created_at->format('d/m/Y')}} </td>
                <td class="">
                    {{\App\Enums\TipiPortafoglioEnum::tryFrom($record->portafoglio)?->testo()}}
                </td>
                <td> {{$record->descrizione}}</td>
                <td class="text-end"> {{\App\importo($record->importo_prima)}}</td>
                <td class="text-end"> {{\App\importo($record->importo)}}</td>
                <td class="text-end"> {{\App\importo($record->importo_dopo)}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{$records->links()}}
