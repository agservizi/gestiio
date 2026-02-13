<h3 class="card-title">Dati generali</h3>

<div class="row">
    <div class="col-md-12">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'denominazione','testo'=>'Nome Cognome / Ragione sociale','valore'=>$contratto->denominazione,'col' => 2])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'forma_giuridica','testo'=>'Forma giuridica','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'partita_iva','testo'=>'Partita iva','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','valore'=>$contratto->codice_fiscale])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'telefono','testo'=>'Telefono','valore'=>$contratto->telefono])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cellulare','testo'=>'Cellulare','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'fax','testo'=>'Fax','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'email','testo'=>'Email o PEC','valore'=>$contratto->email])
    </div>
</div>
<h3 class="card-title">REFERENTE/AMM.RE CONDOMINIO</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'nome_cognome_referente','testo'=>'Nome cognome referente','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_fiscale_referente','testo'=>'Codice fiscale referente','autocomplete'=>'off'])
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


<h3 class="card-title">INDIRIZZO SEDE LEGALE</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_sede','testo'=>'Indirizzo','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'nr_sede','testo'=>'Numero','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'comune_sede','testo'=>'Citta','valore'=>\App\Models\Comune::find($record->comune_sede)?->comuneConTarga()])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_sede','testo'=>'Cap','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">COMPLIARE SE DVIERSO DALLA SEDE DEL CLEINTE</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'c_o','testo'=>'C o','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_fatturazione','testo'=>'Indirizzo fatturazione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'nr_fatturazione','testo'=>'Nr fatturazione','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_fatturazione','testo'=>'Cap fatturazione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'comune_fatturazione','testo'=>'Comune fatturazione','autocomplete'=>'off'])
    </div>
</div>
<hr>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_destinatario','testo'=>'Codice destinatario','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'data_inizio_validita','testo'=>'Data inizio validita','valore'=>$record->data_inizio_validita?->format('d/m/Y')])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'data_fine_validita','testo'=>'Data fine validita','valore'=>$record->data_fine_validita?->format('d/m/Y')])
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cig','testo'=>'Cig','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cup','testo'=>'Cup','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">LUCE</h3>

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
        @include('Backend._inputs.inputTextReadonly',['campo'=>'uso_non_professionale_luce','testo'=>'Uso non professionale luce','valore'=>\App\siNo($record->uso_non_professionale_luce)])
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

    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'livello_tensione','testo'=>'Livello tensione','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'attuale_societa_luce','testo'=>'Attuale societa luce','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_fornitura_luce','testo'=>'Indirizzo fornitura luce','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'nr_fornitura_luce','testo'=>'Nr fornitura luce','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_fornitura_luce','testo'=>'Cap fornitura luce','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'comune_fornitura_luce','testo'=>'Comune fornitura luce','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">GAS</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'pdr','testo'=>'Pdr','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'uso_non_professionale_gas','testo'=>'Uso non professionale luce','valore'=>\App\siNo($record->uso_non_professionale_gas)])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'attuale_societa_gas','testo'=>'Attuale societa gas','autocomplete'=>'off'])
    </div>
</div>
<div class="row">

    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'profilo_consumo','testo'=>'Profilo consumo','required'=>false,'array'=>\App\Models\ProdottoEnergiaEgea::TIPOLOGIA_USO,'altro'=>'data-allow-clear="true"'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'posizione_contatore','testo'=>'Posizione contatore','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'consumo_annuo','testo'=>'Consumo annuo','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'matricola_contatore','testo'=>'Matricola contatore','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_fornitura_gas','testo'=>'Indirizzo fornitura gas','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'nr_fornitura_gas','testo'=>'Nr fornitura gas','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_fornitura_gas','testo'=>'Cap fornitura gas','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'comune_fornitura_gas','testo'=>'Comune fornitura gas','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">Pagamento</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'modalita_pagamento','testo'=>'Modalita pagamento','array'=>\App\Models\ProdottoEnergiaEnelBusiness::MODALITA_PAGAMENTO])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'invio_fattura','testo'=>'Invio fattura','array'=>\App\Models\ProdottoEnergiaEnelBusiness::INVIO_FATTURA])
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
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cognome_nome_sottoscrittore','testo'=>'Cognome nome sottoscrittore','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'recapito_telefonico_titolare','testo'=>'Recapito telefonico titolare','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'iban','testo'=>'Iban','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'iban_sepa','testo'=>'Iban sepa','autocomplete'=>'off'])
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

@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            $('#contratto_energia_id').select2({
                placeholder: 'Seleziona una contratto energia',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/select2?contratto_energia",
                    dataType: 'json',
                    data: function (term, page) {
                        return {
                            term: term.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    }
                }
            }).on('select2:select', function (e) {
                // Access to full data
                //$("#cap").val(e.params.data.cap);

            }).on('select2:open', function () {

            });

        });
    </script>
@endpush
