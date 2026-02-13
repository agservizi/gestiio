<div class="card mt-6">
    <div class="card-header">
        <h3 class="card-title">Sostituzioni sim</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-row-bordered" id="tabella-elenco">
                <thead>
                <tr class="fw-bolder fs-6 text-gray-800">
                    <th class="">Agente</th>
                    <th class="">Motivazione</th>
                    <th class="text-end">Importo</th>
                    <th class="">Seriale vecchia sim</th>
                    <th class="">Seriale nuova sim</th>
                </tr>
                </thead>
                <tbody>
                @foreach($records as $record)
                    <tr class="">
                        <td class="">
                            {{$record->agente->ragione_sociale}}
                        </td>
                        <td class="">{{\App\Models\SostituzioneSim::MOTIVAZIONI[$record->motivazione]}}</td>
                        <td class="text-end">{{$record->importo}}</td>
                        <td class="">{{$record->seriale_vecchia_sim}}</td>
                        <td class="">{{$record->seriale_nuova_sim}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
