<h3 class="card-title">Sky Tv</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_cliente','testo'=>'Codice cliente','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs_.inputRadioH',['campo'=>'profilo','testo'=>'Profilo','required'=>true,'array'=>\App\Models\ProdottoSkyTv::PROFILO])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_promozione','testo'=>'Codice promozione','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'tipologia_cliente','testo'=>'Tipologia cliente','required'=>true,'array'=>\App\Models\ProdottoSkyTv::TIPOLOGIA_CLIENTE,])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputCheckboxH',['campo'=>'pacchetti_sky','testo'=>'Pacchetti Sky','array'=>\App\Models\ProdottoSkyTv::PACCHETTI_SKY_TV])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs_.inputCheckboxH',['campo'=>'servizi_opzionali','testo'=>'Servizi opzionali','array'=>\App\Models\ProdottoSkyTv::SERVIZI_OPZIONALI])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'offerte_sky','testo'=>'Offerte sky','array'=>\App\Models\ProdottoSkyTv::OFFERTE_SKY])
    </div>
</div>
@if(false)
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'canali_opzionali','testo'=>'Canali opzionali','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'servizio_decoder','testo'=>'Servizio decoder','autocomplete'=>'off'])
        </div>
    </div>
@endif
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'tecnologia','testo'=>'Tecnologia','required' => true,'array'=>\App\Models\ProdottoSkyTv::TECNOLOGIA])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs_.inputRadioH',['campo'=>'sky_q_mini','testo'=>'Sky q mini','array'=>['0','1','2','3']])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'solo_smartcard','testo'=>'Solo smartcard',])
    </div>
</div>
<h3 class="card-title">Sky Wifi</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'offerta','testo'=>'Offerta','required'=>true,'array'=>\App\Models\ProdottoSkyWifi::OFFERTE])
    </div>

</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'modem_wifi_hub','testo'=>'Modem Sky Wifi Hub',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'ultra_wifi','testo'=>'Ultra wifi',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs_.inputRadioH',['campo'=>'wifi_spot','testo'=>'Wifi Spot aggiuntivi','array'=>['0','1','2','3']])
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputCheckboxH',['campo'=>'pacchetti_voce','testo'=>'Pacchetti voce','array'=>\App\Models\ProdottoSkyWifi::PACCHETTI_VOCE])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'linea_telefonica','testo'=>'Linea telefonica','required' => true,'array'=>\App\Models\ProdottoSkyWifi::LINEA_TELEFONICA])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'numero_da_migrare','testo'=>'Numero telefonico da migrare','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_migrazione_voce','testo'=>'Codice migrazione di provenienza VOCE','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_migrazione_dati','testo'=>'Codice migrazione di provenienza DATI','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">Dati pagamento</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'metodo_pagamento_tv','testo'=>'Metodo pagamento tv','required' => true,'array'=>\App\Models\ProdottoSkyTv::METODO_PAGAMENTO_TV])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs_.inputRadioH',['campo'=>'frequenza_pagamento_tv','testo'=>'Frequenza pagamento tv','required' => true,'array'=>\App\Models\ProdottoSkyTv::FREQUENZA_PAGAMENTO_TV])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'metodo_pagamento_internet','testo'=>'Metodo pagamento internet','required' => true,'array'=>\App\Models\ProdottoSkyWifi::METODO_PAGAMENTO_INTERNET])
    </div>
</div>
<div id="div_carta">

    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs_.inputRadioH',['campo'=>'carta_di_credito_tipo','testo'=>'Carta di credito','array'=>\App\Models\ProdottoSkyTv::CARTA_DI_CREDITO])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'carta_di_credito_numero','testo'=>'Carta di credito numero','autocomplete'=>'off'])
        </div>
        <div class="row">
            <div class="col-md-6">
                @include('Backend._inputs.inputText',['campo'=>'carta_di_credito_valida_al','testo'=>'Carta di credito valida al','autocomplete'=>'off','altro'=>"data-inputmask=\"'mask': '99/99'\""])
            </div>
        </div>

    </div>
    <div class="w-100 text-center mb-5">
        <button class="btn btn-sm btn-light" type="button" onclick="copiaDatiCarta();">
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
            @include('Backend._inputs.inputText',['campo'=>'carta_di_credito_cognome','testo'=>'Carta di credito cognome','autocomplete'=>'off'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'carta_di_credito_nome','testo'=>'Carta di credito nome','autocomplete'=>'off'])
        </div>
    </div>
</div>
<div id="div_sepa">

    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'sepa_banca','testo'=>'Sepa banca','autocomplete'=>'off'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'sepa_agenzia','testo'=>'Sepa agenzia','autocomplete'=>'off'])
        </div>
    </div>
    <div class="w-100 text-center mb-5">
        <button class="btn btn-sm btn-light" type="button" onclick="copiaDatiSepa();">
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
            @include('Backend._inputs.inputText',['campo'=>'sepa_iban','testo'=>'Sepa iban','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'sepa_intestatario','testo'=>'Sepa intestatario','autocomplete'=>'off'])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'sepa_via','testo'=>'Sepa via','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'sepa_codice_fiscale','testo'=>'Sepa codice fiscale','autocomplete'=>'off'])
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'consenso_1','testo'=>'Finalità di marketing canali tradizionali',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'consenso_2','testo'=>'Finalità di marketing canali digitali',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'consenso_3','testo'=>'Finalità di marketing di terze parti',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'consenso_4','testo'=>'Finalità di profilazione generica',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'consenso_5','testo'=>'Finalità di profilazione Sky Q',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'consenso_6','testo'=>'Finalità di profilazione Sky Broadband',])
    </div>
</div>


@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            mostraNascondiCarta($('.metodo_pagamento_tv:checked').val());
            $('.metodo_pagamento_tv').click(function () {
                mostraNascondiCarta($(this).val());
            });

            function mostraNascondiCarta(val) {
                if (val == 'carta_credito') {
                    $('#div_carta').show();
                    $('#div_sepa').hide();
                } else {
                    $('#div_carta').hide();
                    $('#div_sepa').show();
                }
            }
        });

        function copiaDatiCarta() {
            var cognome = $('#cognome').val();
            var nome = $('#nome').val();

            $('#carta_di_credito_cognome').val(cognome);
            $('#carta_di_credito_nome').val(nome);

        }

        function copiaDatiSepa() {
            var cognome = $('#cognome').val();
            var nome = $('#nome').val();


            $('#sepa_intestatario').val(cognome + ' ' + nome);
            $('#sepa_via').val($('#indirizzo').val() + ' ' + $('#civico').val() + ', ' + $("#citta option:selected").text() + ', ' + $('#cap').val());
            $('#sepa_codice_fiscale').val($('#codice_fiscale').val());
        }
    </script>
@endpush
