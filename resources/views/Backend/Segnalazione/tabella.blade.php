<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">

            <th class="">Nome azienda</th>
            <th class="">Partita iva</th>
            <th class="">Indirizzo</th>
            <th class="">Citta</th>
            <th class="">Agente</th>
            <th class="text-center">Esito</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @include('Backend.Segnalazione.tbody')
        </tbody>
    </table>
</div>
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator )
    <div class="w-100 text-center">
        {{$records->links()}}
    </div>
@endif
