<h3 class="card-title">Dati generali</h3>

<div class="row">
    <div class="col-md-12">
        @include('Backend._inputs_.inputTextValore',['campo'=>'denominazione','testo'=>'Nome Cognome / Ragione sociale','required'=>true,'autocomplete'=>'off','col' => 2,'valore' => $contratto->denominazione])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'forma_giuridica','testo'=>'Forma giuridica','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'partita_iva','testo'=>'Partita iva','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs_.inputTextValore',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','required'=>true,'autocomplete'=>'off','classe'=>'uppercase','valore' => $codiceFiscale])
    </div>

</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs_.inputTextValore',['campo'=>'telefono','testo'=>'Telefono','required'=>true,'autocomplete'=>'off','valore' => $telefono])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cellulare','testo'=>'Cellulare','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'fax','testo'=>'Fax','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs_.inputTextValore',['campo'=>'email','testo'=>'Email o pec','autocomplete'=>'off','required'=>true,'valore' => $email])
    </div>
</div>
<h3 class="card-title">REFERENTE/AMM.RE CONDOMINIO</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'nome_cognome_referente','testo'=>'Nome cognome referente','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_fiscale_referente','testo'=>'Codice fiscale referente','autocomplete'=>'off'])
    </div>
</div>
@include('Backend.ContrattoEnergia.dati-documento')

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_fiscale_referente','testo'=>'Codice fiscale referente','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'telefono_referente','testo'=>'Telefono referente','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">INDIRIZZO SEDE LEGALE</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo_sede','testo'=>'Indirizzo','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'nr_sede','testo'=>'Numero','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2',['campo'=>'comune_sede','testo'=>'Citta','selected'=>\App\Models\Comune::selected(old('comune_sede',$record->comune_sede))])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cap_sede','testo'=>'Cap','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">COMPLIARE SE DVIERSO DALLA SEDE DEL CLIENTE</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'c_o','testo'=>'C o','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo_fatturazione','testo'=>'Indirizzo fatturazione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'nr_fatturazione','testo'=>'Nr fatturazione','autocomplete'=>'off'])
    </div>
</div>
<div class="row">

    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'comune_fatturazione','testo'=>'Comune fatturazione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cap_fatturazione','testo'=>'Cap fatturazione','autocomplete'=>'off'])
    </div>
</div>
<hr>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_destinatario','testo'=>'Codice destinatario','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextData',['campo'=>'data_inizio_validita','testo'=>'Data inizio validita','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextData',['campo'=>'data_fine_validita','testo'=>'Data fine validita','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cig','testo'=>'Cig','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cup','testo'=>'Cup','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">LUCE</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'pod','testo'=>'Pod','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'provenienza_mercato_libero','testo'=>'Provenienza mercato libero',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'uso_non_professionale_luce','testo'=>'Uso non professionale luce',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'consumo_annuo_luce','testo'=>'Consumo annuo luce','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'potenza_contrattuale','testo'=>'Potenza contrattuale','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'livello_tensione','testo'=>'Livello tensione','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'attuale_societa_luce','testo'=>'Attuale societa luce','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo_fornitura_luce','testo'=>'Indirizzo fornitura luce','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'nr_fornitura_luce','testo'=>'Nr fornitura luce','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'comune_fornitura_luce','testo'=>'Comune fornitura luce','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cap_fornitura_luce','testo'=>'Cap fornitura luce','autocomplete'=>'off'])
    </div>

</div>
<h3 class="card-title">GAS</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'pdr','testo'=>'Pdr','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'uso_non_professionale_gas','testo'=>'Uso non professionale gas',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'attuale_societa_gas','testo'=>'Attuale societa gas','autocomplete'=>'off'])
    </div>
</div>
<div class="row">

    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'profilo_consumo','testo'=>'Profilo consumo','required'=>false,'array'=>\App\Models\ProdottoEnergiaEnelBusiness::PROFILI_CONSUMO,'altro'=>'data-allow-clear="true"'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'posizione_contatore','testo'=>'Posizione contatore','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'consumo_annuo','testo'=>'Consumo annuo','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'matricola_contatore','testo'=>'Matricola contatore','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo_fornitura_gas','testo'=>'Indirizzo fornitura gas','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'nr_fornitura_gas','testo'=>'Nr fornitura gas','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'comune_fornitura_gas','testo'=>'Comune fornitura gas','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cap_fornitura_gas','testo'=>'Cap fornitura gas','autocomplete'=>'off'])
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
        @include('Backend._inputs.inputText',['campo'=>'titolare_cc','testo'=>'Titolare cc','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_fiscale_titolare','testo'=>'Codice fiscale titolare','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cognome_nome_sottoscrittore','testo'=>'Cognome nome sottoscrittore','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'recapito_telefonico_titolare','testo'=>'Recapito telefonico titolare','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'iban','testo'=>'Iban','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'iban_sepa','testo'=>'Iban sepa','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'voltura_ordinaria_contestuale','testo'=>'Voltura ordinaria contestuale',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'voltura_mortis_causa','testo'=>'Voltura mortis causa',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'bolletta_web','testo'=>'Bolletta web',])
    </div>
</div>


@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
            select2UniversaleBackend('comune_sede', 'un comune', 3, 'citta').on('select2:select', function (e) {
                // Access to full data
                $("#cap_sede").val(e.params.data.cap);
            });
            select2UniversaleBackend('comune_fatturazione', 'un comune', 3, 'citta').on('select2:select', function (e) {
                // Access to full data
                $("#cap_fatturazione").val(e.params.data.cap);
            });
            select2UniversaleBackend('comune_fornitura_luce', 'un comune', 3, 'citta').on('select2:select', function (e) {
                // Access to full data
                $("#cap_fornitura_luce").val(e.params.data.cap);
            });
            select2UniversaleBackend('comune_fornitura_gas', 'un comune', 3, 'citta').on('select2:select', function (e) {
                // Access to full data
                $("#cap_fornitura_gas").val(e.params.data.cap);
            });


            $('#partita_iva').blur(function () {

                if ($('#denominazione').val() !== '') {
                    return;
                }

                var url = "{{action([\App\Http\Controllers\RegistratiController::class,'verificaPIvaEu'])}}";
                $.ajax(url,
                    {
                        dataType: 'json',
                        method: 'POST',
                        data: {
                            'partita_iva': $('#partita_iva').val(),
                            '_token': '{{csrf_token()}}'
                        },
                        success: function (resp) {
                            if (resp.success) {
                                $('#denominazione').val(resp.res.name);
                            }
                        }
                    });

            });

        });
    </script>
@endpush
