<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Data</th>
            <th class="">Tipo visura</th>
            <th class="">Esito</th>
            <th class="">Partita Iva/Codice fiscale</th>
            <th class="">Denominazione</th>
            <th class="">Agente</th>
            <th class="text-end">Prezzo</th>
            <th class="text-center">Azioni</th>
        </tr>
        </thead>
        <tbody>
        @include('Backend.Visura.tbody')
        </tbody>
    </table>
</div>
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator )
    <div class="w-100 text-center">
        {{$records->links()}}
    </div>
@endif
