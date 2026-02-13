<h3 class="card-title">Dati generali</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'nome','testo'=>'Nome','required'=>true,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cognome','testo'=>'Cognome','required'=>true,'autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs_.inputTextValore',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','required'=>true,'autocomplete'=>'off','classe'=>'uppercase','valore' => $codiceFiscale])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs_.inputTextValore',['campo'=>'email','testo'=>'Email','autocomplete'=>'off','required'=>true,'valore' => $email])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs_.inputTextValore',['campo'=>'telefono','testo'=>'Telefono','required'=>true,'autocomplete'=>'off','valore' => $telefono])
    </div>
</div>

@include('Backend.ContrattoEnergia.dati-documento')

<h3 class="card-title">Indirizzo di residenza</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo','testo'=>'Indirizzo','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2',['campo'=>'citta','testo'=>'Citta','selected'=>\App\Models\Comune::selected(old('citta',$record->citta))])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cap','testo'=>'Cap','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'scala','testo'=>'Scala','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'interno','testo'=>'Interno','autocomplete'=>'off'])
    </div>
</div>


<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'residente_fornitura','testo'=>'Residente fornitura',])
    </div>
</div>
<div id="altri_indirizzi">
    <h3 class="card-title">Indirizzo fornitura</h3>
    <div class="w-100 text-center mb-5">
        <button class="btn btn-sm btn-light" type="button" onclick="copiaIndirizzo();">
            <span class="svg-icon svg-icon-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path
                            d="M13.9466 0.215088H4.45502C3.44455 0.215088 2.62251 1.0396 2.62251 2.05311V2.62219H2.04736C1.03688 2.62219 0.214844 3.44671 0.214844 4.46078V13.9469C0.214844 14.9605 1.03688 15.785 2.04736 15.785H11.5393C12.553 15.785 13.3776 14.9605 13.3776 13.9469V13.3779H13.9466C14.9604 13.3779 15.7852 12.5534 15.7852 11.5393V2.05306C15.7852 1.0396 14.9604 0.215088 13.9466 0.215088ZM12.2526 13.9469C12.2526 14.3402 11.9326 14.6599 11.5393 14.6599H2.04736C1.65732 14.6599 1.33984 14.3402 1.33984 13.9469V4.46073C1.33984 4.06743 1.65738 3.74714 2.04736 3.74714H3.18501H11.5393C11.9326 3.74714 12.2526 4.06737 12.2526 4.46073V12.8153V13.9469ZM14.6602 11.5392C14.6602 11.9325 14.3402 12.2528 13.9466 12.2528H13.3775V4.46073C13.3775 3.44671 12.553 2.62214 11.5392 2.62214H3.74746V2.05306C3.74746 1.65976 4.06499 1.34003 4.45497 1.34003H13.9466C14.3402 1.34003 14.6602 1.65976 14.6602 2.05306V11.5392Z"
                            fill="#B5B5C3"></path>
                </svg>
                copia indirizzo da dati generali
            </span>
        </button>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'indirizzo_fornitura','testo'=>'Indirizzo fornitura','autocomplete'=>'off'])
        </div>

        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'nr_fornitura','testo'=>'Nr fornitura','autocomplete'=>'off'])
        </div>

        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'scala_fornitura','testo'=>'Scala fornitura','autocomplete'=>'off'])
        </div>

        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'interno_fornitura','testo'=>'Interno fornitura','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'comune_fornitura','testo'=>'Comune fornitura','autocomplete'=>'off'])
        </div>

        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'cap_fornitura','testo'=>'Cap fornitura','autocomplete'=>'off'])
        </div>
    </div>
    <h3 class="card-title">Indirizzo fatturazione</h3>

    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'presso_fatturazione','testo'=>'Presso fatturazione','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'indirizzo_fatturazione','testo'=>'Indirizzo fatturazione','autocomplete'=>'off'])
        </div>

        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'nr_fatturazione','testo'=>'Nr fatturazione','autocomplete'=>'off'])
        </div>

        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'scala_fatturazione','testo'=>'Scala fatturazione','autocomplete'=>'off'])
        </div>

        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'interno_fatturazione','testo'=>'Interno fatturazione','autocomplete'=>'off'])
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
</div>
<h3 class="card-title">Energia elettrica</h3>

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
        @include('Backend._inputs.inputText',['campo'=>'consumo_annuo_luce','testo'=>'Consumo annuo luce','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'potenza_contrattuale','testo'=>'Potenza contrattuale','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'attuale_societa_luce','testo'=>'Attuale societa luce','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">Gas</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'pdr','testo'=>'Pdr','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'consumo_annuo_gas','testo'=>'Consumo annuo gas','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'attuale_societa_gas','testo'=>'Attuale societa gas','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'riscaldamento','testo'=>'Riscaldamento',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'cottura_acqua_calda','testo'=>'Cottura acqua calda',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_destinatario','testo'=>'Codice destinatario','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo_pec','testo'=>'Indirizzo pec','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">Pagamento</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'modalita_pagamento','testo'=>'Modalita pagamento','array'=>\App\Models\ProdottoEnergiaEnelConsumer::MODALITA_PAGAMENTO])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'invio_fattura','testo'=>'Invio fattura','array'=>\App\Models\ProdottoEnergiaEnelConsumer::INVIO_FATTURA])
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
        @include('Backend._inputs.inputText',['campo'=>'telefono_titolare','testo'=>'Telefono titolare','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'iban','testo'=>'Iban','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'bic_swift','testo'=>'Bic swift','autocomplete'=>'off'])
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
        function copiaIndirizzo() {
            var citta = $('#citta').select2('data');
            var indirizzo = $('#indirizzo').val();
            var cap = $('#cap').val();

            $('#indirizzo_fornitura').val(indirizzo);
            $('#cap_fornitura').val(cap);
            if (citta.length) {
                var $newOption = $("<option selected='selected'></option>").val(citta[0].id).text(citta[0].text)
            }

            $('#comune_fornitura').append($newOption).trigger('change');

        }

        $(function () {

            $('#residente_fornitura').change(function () {
                mostraNascondiAltriIndirizzi(!$('#residente_fornitura').is(':checked'));
            });

            mostraNascondiAltriIndirizzi(!$('#residente_fornitura').is(':checked'));

            function mostraNascondiAltriIndirizzi(mostra) {
                $('#altri_indirizzi').toggle(mostra);
            }

            select2UniversaleBackend('comune_fornitura', 'un comune', 3, 'citta').on('select2:select', function (e) {
                // Access to full data
                $("#cap_fornitura").val(e.params.data.cap);
            });
            select2UniversaleBackend('comune_fatturazione', 'un comune', 3, 'citta').on('select2:select', function (e) {
                // Access to full data
                $("#cap_fatturazione").val(e.params.data.cap);
            });


        });
    </script>
@endpush
