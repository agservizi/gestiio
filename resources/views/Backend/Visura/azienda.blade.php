<div class="row mb-6">
    <div class="col-md-12">
        <div class="alert alert-light-primary border border-primary border-dashed mb-3">
            <div class="fw-bold mb-2">Ricerca azienda</div>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Denominazione</label>
                    <input type="text" id="ricerca_azienda_denominazione" class="form-control form-control-solid" placeholder="Es. Enel">
                </div>
                <div class="col-md-5">
                    @include('Backend._inputs.inputSelect2',['campo'=>'provincia_ricerca','testo'=>'Provincia (opzionale)','required'=>false,'selected'=>\App\Models\Provincia::selected(null)])
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button"
                            class="btn btn-light-primary w-100"
                            id="btn-ricerca-azienda"
                            data-url="{{action([\App\Http\Controllers\Backend\VisuraController::class,'ricercaAzienda'])}}">
                        Cerca
                    </button>
                </div>
            </div>
            <div class="form-text mt-2">Seleziona un risultato per compilare automaticamente i campi azienda.</div>
        </div>
    </div>
</div>

<div class="row mb-6 d-none" id="box-risultati-ricerca-azienda">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-row-bordered align-middle">
                <thead>
                <tr class="fw-bolder fs-7 text-uppercase">
                    <th>Denominazione</th>
                    <th>P.IVA</th>
                    <th>Comune</th>
                    <th>Natura giuridica</th>
                    <th class="text-end">Azione</th>
                </tr>
                </thead>
                <tbody id="tbody-risultati-ricerca-azienda"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'partita_iva','testo'=>'Partita iva','required'=>true,'autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'ragione_sociale','testo'=>'Ragione sociale','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo','testo'=>'Indirizzo'])
    </div>
</div>
