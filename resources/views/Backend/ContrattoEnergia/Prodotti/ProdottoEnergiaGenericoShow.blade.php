<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'nome','testo'=>'Nome','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cognome','testo'=>'Cognome','autocomplete'=>'off'])
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'email','testo'=>'Email','valore'=>$contratto->email])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'telefono','testo'=>'Telefono','valore'=>$contratto->telefono])
    </div>
</div>

<h3 class="card-title">Indirizzo</h3>
<div class="row">
    <div class="col-md-12">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo','testo'=>'Indirizzo','autocomplete'=>'off','col'=>2])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'citta','testo'=>'Citta','valore'=>$record->comune?->comuneConTarga()])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap','testo'=>'Cap','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'scala','testo'=>'Scala','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'interno','testo'=>'Interno','autocomplete'=>'off'])
    </div>
</div>

<h3 class="card-title">Dati documento</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_documento','testo'=>'Tipo documento','valore'=>$record->tipo_documento?\App\Models\ContrattoTelefonia::TIPI_DOCUMENTO[$record->tipo_documento]:'' ])
    </div>
</div>
<div class="row">
    <div class="col-md-6">@include('Backend._inputs.inputTextReadonly',['campo'=>'numero_documento','testo'=>'Numero Documento','required'=>false,'autocomplete'=>'off'])</div>
    <div class="col-md-6">@include('Backend._inputs.inputTextReadonly',['campo'=>'rilasciato_da','testo'=>'Rilasciato da','required'=>false,'array'=>['COMUNE'=>'COMUNE','MIT UCO'=>'MIT UCO', 'MC'=>'MC', 'MI'=>'MI']])</div>
</div>
<div class="row">
    <div class="col-md-6">@include('Backend._inputs.inputTextReadonly',['campo'=>'data_rilascio','testo'=>'Data rilascio','valore'=>$record->data_rilascio?->format('d/m/Y')])</div>
    <div class="col-md-6">@include('Backend._inputs.inputTextReadonly',['campo'=>'data_scadenza','testo'=>'Data scadenza','valore'=>$record->data_scadenza?->format('d/m/Y')])</div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'fornitura_richiesta','testo'=>'Fornitura richiesta','valore'=>$record->fornituraRichiestaArray()])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'fasce_reperibilita','testo'=>'Fasce reperibilita','valore'=>$record->fasceReperibilitaArray()])
    </div>
</div>
@if(in_array('luce',$record->fornitura_richiesta??[]))

    <h4>DATI TECNICI ENERGIA ELETTRICA</h4>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'attuale_fornitore_luce','testo'=>'Attuale fornitore luce','autocomplete'=>'off'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'pod','testo'=>'Pod','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_fornitura_luce','testo'=>'Indirizzo fornitura luce','autocomplete'=>'off','help'=>'(solo se diverso dall’indirizzo di residenza)'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'civico_fornitura_luce','testo'=>'Civico fornitura luce','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'comune_fornitura_luce','testo'=>'Comune fornitura luce','autocomplete'=>'off'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_fornitura_luce','testo'=>'Cap fornitura luce','autocomplete'=>'off'])
        </div>
    </div>
@endif
@if(in_array('gas',$record->fornitura_richiesta??[]))
    <h4>DATI TECNICI GAS NATURALE</h4>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'attuale_fornitore_gas','testo'=>'Attuale fornitore gas','autocomplete'=>'off'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'pdr','testo'=>'Pdr','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'tipologia_uso_gas','testo'=>'Tipologia uso gas','valore'=>$record->tipologiaUsoGasArray()])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_remi','testo'=>'Codice remi','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_fornitura_gas','testo'=>'Indirizzo fornitura gas','autocomplete'=>'off','help'=>'(solo se diverso dall’indirizzo di residenza)'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'civico_fornitura_gas','testo'=>'Civico fornitura gas','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'comune_fornitura_gas','testo'=>'Comune fornitura gas','autocomplete'=>'off'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_fornitura_gas','testo'=>'Cap fornitura gas','autocomplete'=>'off'])
        </div>
    </div>
@endif
<h4>MODALITÀ DI PAGAMENTO E SPEDIZIONE FATTURA</h4>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'modalita_pagamento_fattura','testo'=>'Modalita pagamento fattura','array'=>\App\Models\ProdottoEnergiaIllumia::MODALITA_PAGAMENTO_FATTURA,'altro'=>'data-minimum-results-for-search="Infinity"'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'intestatario_conto_corrente','testo'=>'Intestatario conto corrente','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_fiscale_intestatario','testo'=>'Codice fiscale intestatario','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'iban','testo'=>'Iban','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'modalita_spedizione_fattura','testo'=>'Modalita spedizione fattura','array'=>\App\Models\ProdottoEnergiaIllumia::MODALITA_SPEDIZIONE_FATTURA,'altro'=>'data-minimum-results-for-search="Infinity"'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_spedizione_fattura','testo'=>'Indirizzo spedizione fattura','autocomplete'=>'off','help'=>'(solo se diverso dall’indirizzo di residenza)'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'civico_spedizione_fattura','testo'=>'Civico spedizione fattura','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'comune_spedizione_fattura','testo'=>'Comune spedizione fattura','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_spedizione_fattura','testo'=>'Cap spedizione fattura','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'c_o','testo'=>'C o','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'virtu_titolo','testo'=>'Virtu titolo','array'=>\App\Models\ProdottoEnergiaIllumia::VIRTU_TITOLO,'altro'=>'data-minimum-results-for-search="Infinity"'])
    </div>
</div>

@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {


        });
    </script>
@endpush
