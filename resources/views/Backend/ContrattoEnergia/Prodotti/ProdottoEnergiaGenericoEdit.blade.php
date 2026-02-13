
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
        @include('Backend._inputs.inputCheckboxH',['campo'=>'fornitura_richiesta','testo'=>'Fornitura richiesta','required'=>false,'array'=>\App\Models\ProdottoEnergiaIllumia::FORNITURA_RICHIESTA])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputCheckboxH',['campo'=>'fasce_reperibilita','testo'=>'Fasce reperibilita','required'=>false,'array'=>\App\Models\ProdottoEnergiaIllumia::FASCE_REPERIBILITA])
    </div>
</div>
<div id="dati-luce" style="display: none;">
    <h4>DATI TECNICI ENERGIA ELETTRICA</h4>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'attuale_fornitore_luce','testo'=>'Attuale fornitore luce','autocomplete'=>'off'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'pod','testo'=>'Pod','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'indirizzo_fornitura_luce','testo'=>'Indirizzo fornitura luce','autocomplete'=>'off','help'=>'(solo se diverso dall’indirizzo di residenza)'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'civico_fornitura_luce','testo'=>'Civico fornitura luce','autocomplete'=>'off'])
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
</div>
<div id="dati-gas" style="display: none;">

    <h4>DATI TECNICI GAS NATURALE</h4>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'attuale_fornitore_gas','testo'=>'Attuale fornitore gas','autocomplete'=>'off'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'pdr','testo'=>'Pdr','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputCheckboxH',['campo'=>'tipologia_uso_gas','testo'=>'Tipologia uso gas','array'=>\App\Models\ProdottoEnergiaIllumia::TIPOLOGIA_USO_GAS])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'codice_remi','testo'=>'Codice remi','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'indirizzo_fornitura_gas','testo'=>'Indirizzo fornitura gas','autocomplete'=>'off','help'=>'(solo se diverso dall’indirizzo di residenza)'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'civico_fornitura_gas','testo'=>'Civico fornitura gas','autocomplete'=>'off'])
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
</div>
<h4>MODALITÀ DI PAGAMENTO E SPEDIZIONE FATTURA</h4>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'modalita_pagamento_fattura','testo'=>'Modalita pagamento fattura','required'=>false,'array'=>\App\Models\ProdottoEnergiaIllumia::MODALITA_PAGAMENTO_FATTURA,'altro'=>'data-minimum-results-for-search="Infinity"'])
    </div>
</div>
<div class="w-100 text-center mb-5">
    <button class="btn btn-sm btn-light" type="button" onclick="copiaIntestatario();">
            <span class="svg-icon svg-icon-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path
                            d="M13.9466 0.215088H4.45502C3.44455 0.215088 2.62251 1.0396 2.62251 2.05311V2.62219H2.04736C1.03688 2.62219 0.214844 3.44671 0.214844 4.46078V13.9469C0.214844 14.9605 1.03688 15.785 2.04736 15.785H11.5393C12.553 15.785 13.3776 14.9605 13.3776 13.9469V13.3779H13.9466C14.9604 13.3779 15.7852 12.5534 15.7852 11.5393V2.05306C15.7852 1.0396 14.9604 0.215088 13.9466 0.215088ZM12.2526 13.9469C12.2526 14.3402 11.9326 14.6599 11.5393 14.6599H2.04736C1.65732 14.6599 1.33984 14.3402 1.33984 13.9469V4.46073C1.33984 4.06743 1.65738 3.74714 2.04736 3.74714H3.18501H11.5393C11.9326 3.74714 12.2526 4.06737 12.2526 4.46073V12.8153V13.9469ZM14.6602 11.5392C14.6602 11.9325 14.3402 12.2528 13.9466 12.2528H13.3775V4.46073C13.3775 3.44671 12.553 2.62214 11.5392 2.62214H3.74746V2.05306C3.74746 1.65976 4.06499 1.34003 4.45497 1.34003H13.9466C14.3402 1.34003 14.6602 1.65976 14.6602 2.05306V11.5392Z"
                            fill="#B5B5C3"></path>
                </svg>
                copia intestatario da dati generali
            </span>
    </button>
</div>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'intestatario_conto_corrente','testo'=>'Intestatario conto corrente','required'=>false,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_fiscale_intestatario','testo'=>'Codice fiscale intestatario','required'=>false,'autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'iban','testo'=>'Iban','required'=>false,'autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'modalita_spedizione_fattura','testo'=>'Modalita spedizione fattura','required'=>false,'array'=>\App\Models\ProdottoEnergiaIllumia::MODALITA_SPEDIZIONE_FATTURA,'altro'=>'data-minimum-results-for-search="Infinity"'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo_spedizione_fattura','testo'=>'Indirizzo spedizione fattura','autocomplete'=>'off','help'=>'(solo se diverso dall’indirizzo di residenza)'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'civico_spedizione_fattura','testo'=>'Civico spedizione fattura','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'comune_spedizione_fattura','testo'=>'Comune spedizione fattura','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cap_spedizione_fattura','testo'=>'Cap spedizione fattura','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'c_o','testo'=>'C o','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'virtu_titolo','testo'=>'Virtu titolo','array'=>\App\Models\ProdottoEnergiaIllumia::VIRTU_TITOLO,'altro'=>'data-minimum-results-for-search="Infinity"'])
    </div>
</div>

@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        function copiaIntestatario() {

            $('#intestatario_conto_corrente').val($('#cognome').val() + ' ' + $('#nome').val());
            $('#codice_fiscale_intestatario').val($('#codice_fiscale').val());
        }

        $(function () {

            const $fornitura = $('.fornitura_richiesta');
            $fornitura.click(function () {
                mostraNascondiLuceGas();
            });

            mostraNascondiLuceGas();

            function mostraNascondiLuceGas() {
                $fornitura.each(function () {
                    var cosa = $(this).val();

                    if ($(this).is(':checked')) {
                        $('#dati-'+cosa).show();
                    }else{
                        $('#dati-'+cosa).hide();
                    }
                });
            }


        });
    </script>
@endpush
