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
        @include('Backend._inputs.inputSwitch',['campo'=>'chiede_esecuzione_anticipata','testo'=>'Chiede esecuzione anticipata',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'residente_fornitura','testo'=>'Residente fornitura',])
    </div>
</div>
<h3 class="card-title">Indirizzo fatturazione</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'spedizione_fattura','testo'=>'Spedizione fattura','required'=>true,'array'=>\App\Models\ProdottoEnergiaEgea::SPEDIZIONE_FATTURA,'altro'=>'data-minimum-results-for-search="Infinity"'])
    </div>
</div>
<div class="row" id="div_indirizzo_spedizione" style="display: none;">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_fatturazione','testo'=>'Indirizzo fatturazione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_fatturazione','testo'=>'Cap fatturazione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'comune_fatturazione','testo'=>'Comune fatturazione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'nominativo_residente_fatturazione','testo'=>'Nominativo residente fatturazione','autocomplete'=>'off'])
    </div>
</div>

<h3 class="card-title">Mandato per addebito diretto SEPA - Dati bancari</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'banca','testo'=>'Banca','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'agenzia_filiale','testo'=>'Agenzia filiale','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'iban','testo'=>'Iban','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'bic_swift','testo'=>'Bic swift','autocomplete'=>'off'])
    </div>
</div>

<h3 class="card-title">Dati del punto fornitura di gas metano</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_attivazione_gas','testo'=>'Tipo attivazione gas','array'=>\App\Models\ProdottoEnergiaEgea::TIPI_ATTIVAZIONE,'altro'=>'data-allow-clear="true"'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'pdr','testo'=>'Pdr','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'matricola_contatore','testo'=>'Matricola contatore','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cat_uso_arera','testo'=>'Cat uso arera','array'=>\App\Models\ProdottoEnergiaEgea::CAT_USO_ARERA])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cabina_remi','testo'=>'Cabina remi','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipologia_pdr','testo'=>'Tipologia pdr','array'=>\App\Models\ProdottoEnergiaEgea::TIPOLOGIA_PDR])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'distributore_locale','testo'=>'Distributore locale','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'soc_vendita_attuale_gas','testo'=>'Soc vendita attuale gas','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'mercato_attuale_gas','testo'=>'Mercato attuale','array'=>\App\Models\ProdottoEnergiaEgea::MERCATO_ATTUALE])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'potenzialita_impianto','testo'=>'Potenzialita impianto','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'consumo_anno_termico','testo'=>'Consumo anno termico','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">Dati del punto di fornitura di energia elettrica</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_attivazione_luce','testo'=>'Tipo attivazione luce','array'=>\App\Models\ProdottoEnergiaEgea::TIPI_ATTIVAZIONE,'altro'=>'data-allow-clear="true"'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'pod','testo'=>'Pod','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipologia_uso','testo'=>'Tipologia uso','array'=>\App\Models\ProdottoEnergiaEgea::TIPOLOGIA_USO,'altro'=>'data-allow-clear="true"'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'tensione','testo'=>'Tensione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'potenza_contrattuale','testo'=>'Potenza contrattuale','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'consumo_anno','testo'=>'Consumo anno','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'mercato_provenienza_luce','testo'=>'Mercato provenienza luce','array'=>\App\Models\ProdottoEnergiaEgea::MERCATO_PROVENIENZA])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'soc_vendita_attuale_luce','testo'=>'Soc vendita attuale luce','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'mercato_attuale_luce','testo'=>'Mercato attuale luce','array'=>\App\Models\ProdottoEnergiaEgea::MERCATO_ATTUALE_LUCE])
    </div>
</div>
<h3 class="card-title">Indirizzo di fornitura</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_fornitura','testo'=>'Indirizzo fornitura','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'comune_fornitura','testo'=>'Comune fornitura','selected'=>\App\Models\Comune::selected(old('comune_fornitura',$record->comune_fornitura))])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_fornitura','testo'=>'Cap fornitura','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'dichiara_di_essere','testo'=>'Dichiara di essere','autocomplete'=>'off'])
    </div>
</div>

