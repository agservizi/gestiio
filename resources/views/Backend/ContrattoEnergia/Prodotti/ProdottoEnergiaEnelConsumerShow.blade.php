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
        @include('Backend._inputs.inputTextReadonly',['campo'=>'residente_fornitura','testo'=>'Residente fornitura','valore' => \App\siNo($record->residente_fornitura)])
    </div>
</div>
@if(!$record->residente_fornitura)
    <h3 class="card-title">Indirizzo fornitura</h3>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_fornitura','testo'=>'Indirizzo fornitura','autocomplete'=>'off'])
        </div>

        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'nr_fornitura','testo'=>'Nr fornitura','autocomplete'=>'off'])
        </div>

        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'scala_fornitura','testo'=>'Scala fornitura','autocomplete'=>'off'])
        </div>

        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'interno_fornitura','testo'=>'Interno fornitura','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'comune_fornitura','testo'=>'Comune fornitura','autocomplete'=>'off'])
        </div>

        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_fornitura','testo'=>'Cap fornitura','autocomplete'=>'off'])
        </div>
    </div>
@endif
<h3 class="card-title">Indirizzo fatturazione</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'presso_fatturazione','testo'=>'Presso fatturazione','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_fatturazione','testo'=>'Indirizzo fatturazione','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'nr_fatturazione','testo'=>'Nr fatturazione','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'scala_fatturazione','testo'=>'Scala fatturazione','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'interno_fatturazione','testo'=>'Interno fatturazione','autocomplete'=>'off'])
    </div>
</div>

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
        @include('Backend._inputs.inputTextReadonly',['campo'=>'email','testo'=>'Email','autocomplete'=>'off','valore' => $contratto->email])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'telefono','testo'=>'Telefono','autocomplete'=>'off','valore' => $contratto->telefono])
    </div>
</div>


<h3 class="card-title">Energia elettrica</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'pod','testo'=>'Pod','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'provenienza_mercato_libero','testo'=>'Provenienza mercato libero','valore'=>\App\siNo($record->provenienza_mercato_libero)])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'consumo_annuo_luce','testo'=>'Consumo annuo luce','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'potenza_contrattuale','testo'=>'Potenza contrattuale','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'attuale_societa_luce','testo'=>'Attuale societa luce','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">Gas</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'pdr','testo'=>'Pdr','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'consumo_annuo_gas','testo'=>'Consumo annuo gas','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'attuale_societa_gas','testo'=>'Attuale societa gas','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'riscaldamento','testo'=>'Riscaldamento','valore' => \App\siNo($record->riscaldamento)])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cottura_acqua_calda','testo'=>'Cottura acqua calda','valore' => \App\siNo($record->cottura_acqua_calda)])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_destinatario','testo'=>'Codice destinatario','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_pec','testo'=>'Indirizzo pec','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">Pagamento</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'modalita_pagamento','testo'=>'Modalita pagamento','valore'=>$record->modalita_pagamento?\App\Models\ProdottoEnergiaEnelConsumer::MODALITA_PAGAMENTO[$record->modalita_pagamento]:null])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'invio_fattura','testo'=>'Invio fattura',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'titolare_cc','testo'=>'Titolare cc','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_fiscale_titolare','testo'=>'Codice fiscale titolare','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'telefono_titolare','testo'=>'Telefono titolare','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'iban','testo'=>'Iban','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'bic_swift','testo'=>'Bic swift','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'voltura_ordinaria_contestuale','testo'=>'Voltura ordinaria contestuale','valore' => \App\siNo($record->voltura_ordinaria_contestuale)])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'voltura_mortis_causa','testo'=>'Voltura mortis causa','valore' => \App\siNo($record->voltura_mortis_causa)])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'bolletta_web','testo'=>'Bolletta web','valore' => \App\siNo($record->bolletta_web)])
    </div>
</div>
