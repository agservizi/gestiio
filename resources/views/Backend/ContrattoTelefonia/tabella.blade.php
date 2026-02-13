<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="d-none d-lg-table-cell">Data</th>
            <th class="">Codice cliente</th>
            <th class="">Codice contratto</th>
            <th class="">Tipo contratto</th>
            <th class="">Esito</th>
            <th class="">Nominativo</th>
            <th class="d-none d-md-table-cell">Contatti</th>
            <th class="d-none d-lg-table-cell">Indirizzo</th>
            <th class="">Agente</th>
            <th class="">Mese pagamento</th>
            <th class="text-center">Azioni</th>
        </tr>
        </thead>
        <tbody>
        @include('Backend.ContrattoTelefonia.tbody')
        </tbody>
    </table>
</div>
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator )
    <div class="w-100 text-center">
        {{$records->links()}}
    </div>
@endif
