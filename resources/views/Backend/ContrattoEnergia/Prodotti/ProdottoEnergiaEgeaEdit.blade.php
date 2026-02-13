
<h3 class="card-title">Dati generali</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs_.inputTextValore',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','required'=>true,'autocomplete'=>'off','classe'=>'uppercase','valore' => $codiceFiscale])
    </div>
</div>
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
        @include('Backend._inputs_.inputTextValore',['campo'=>'email','testo'=>'Email','autocomplete'=>'off','required'=>true,'valore' => $email])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs_.inputTextValore',['campo'=>'telefono','testo'=>'Telefono','required'=>true,'autocomplete'=>'off','valore' => $telefono])
    </div>
</div>
<h3 class="card-title">Indirizzo</h3>

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

@include('Backend.ContrattoEnergia.dati-documento')
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
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'spedizione_fattura','testo'=>'Spedizione fattura','required'=>true,'array'=>\App\Models\ProdottoEnergiaEgea::SPEDIZIONE_FATTURA,'altro'=>'data-minimum-results-for-search="Infinity"'])
    </div>
</div>
<div class="row" id="div_indirizzo_spedizione" style="display: none;">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo_fatturazione','testo'=>'Indirizzo fatturazione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cap_fatturazione','testo'=>'Cap fatturazione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'comune_fatturazione','testo'=>'Comune fatturazione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'nominativo_residente_fatturazione','testo'=>'Nominativo residente fatturazione','autocomplete'=>'off'])
    </div>
</div>

<h3 class="card-title">Mandato per addebito diretto SEPA - Dati bancari</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'banca','testo'=>'Banca','required'=>false,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'agenzia_filiale','testo'=>'Agenzia filiale','required'=>false,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'iban','testo'=>'Iban','required'=>false,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'bic_swift','testo'=>'Bic swift','required'=>false,'autocomplete'=>'off'])
    </div>
</div>

<h3 class="card-title">Dati del punto fornitura di gas metano</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'tipo_attivazione_gas','testo'=>'Tipo attivazione gas','required'=>false,'array'=>\App\Models\ProdottoEnergiaEgea::TIPI_ATTIVAZIONE,'altro'=>'data-allow-clear="true"'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'pdr','testo'=>'Pdr','required'=>false,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'matricola_contatore','testo'=>'Matricola contatore','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'cat_uso_arera','testo'=>'Cat uso arera','required'=>false,'array'=>\App\Models\ProdottoEnergiaEgea::CAT_USO_ARERA])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cabina_remi','testo'=>'Cabina remi','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'tipologia_pdr','testo'=>'Tipologia pdr','required'=>false,'array'=>\App\Models\ProdottoEnergiaEgea::TIPOLOGIA_PDR])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'distributore_locale','testo'=>'Distributore locale','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'soc_vendita_attuale_gas','testo'=>'Soc vendita attuale gas','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'mercato_attuale_gas','testo'=>'Mercato attuale','array'=>\App\Models\ProdottoEnergiaEgea::MERCATO_ATTUALE])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'potenzialita_impianto','testo'=>'Potenzialita impianto','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'consumo_anno_termico','testo'=>'Consumo anno termico','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">Dati del punto di fornitura di energia elettrica</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'tipo_attivazione_luce','testo'=>'Tipo attivazione luce','required'=>false,'array'=>\App\Models\ProdottoEnergiaEgea::TIPI_ATTIVAZIONE,'altro'=>'data-allow-clear="true"'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'pod','testo'=>'Pod','required'=>false,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'tipologia_uso','testo'=>'Tipologia uso','required'=>false,'array'=>\App\Models\ProdottoEnergiaEgea::TIPOLOGIA_USO,'altro'=>'data-allow-clear="true"'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'tensione','testo'=>'Tensione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'potenza_contrattuale','testo'=>'Potenza contrattuale','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'consumo_anno','testo'=>'Consumo anno','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'mercato_provenienza_luce','testo'=>'Mercato provenienza luce','array'=>\App\Models\ProdottoEnergiaEgea::MERCATO_PROVENIENZA])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'soc_vendita_attuale_luce','testo'=>'Soc vendita attuale luce','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'mercato_attuale_luce','testo'=>'Mercato attuale luce','array'=>\App\Models\ProdottoEnergiaEgea::MERCATO_ATTUALE_LUCE])
    </div>
</div>
<h3 class="card-title">Indirizzo di fornitura</h3>
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
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2',['campo'=>'comune_fornitura','testo'=>'Comune fornitura','selected'=>\App\Models\Comune::selected(old('comune_fornitura',$record->comune_fornitura))])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cap_fornitura','testo'=>'Cap fornitura','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'dichiara_di_essere','testo'=>'Dichiara di essere','autocomplete'=>'off'])
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
            mostraNascondi($('#spedizione_fattura').val());

            $('#spedizione_fattura').change(function () {
                mostraNascondi($(this).val());
            });

            select2UniversaleBackend('comune_fornitura', 'un comune', 3, 'citta').on('select2:select', function (e) {
                // Access to full data
                $("#cap_fornitura").val(e.params.data.cap);
            });
            select2UniversaleBackend('comune_fatturazione', 'un comune', 3, 'citta').on('select2:select', function (e) {
                // Access to full data
                $("#cap_fatturazione").val(e.params.data.cap);
            });

            function mostraNascondi(val) {
                $('#div_indirizzo_spedizione').toggle(val === 'posta_ordinaria');
            }

        });
    </script>
@endpush
