<h3 class="card-title">Sky Tv</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_cliente','testo'=>'Codice cliente','autocomplete'=>'off'])
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipologia_cliente','testo'=>'Tipologia cliente','required'=>true,'valore'=>\App\Models\ProdottoSkyTv::TIPOLOGIA_CLIENTE[$record->tipologia_cliente]])
    </div>
</div>
@if(false)
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'pacchetti_sky','testo'=>'Pacchetti Sky','valore'=>$record->pacchettiSky()])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'servizi_opzionali','testo'=>'Servizi opzionali','valore'=>$record->serviziOpzionali()])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'offerte_sky','testo'=>'Offerte sky','array'=>\App\Models\ProdottoSkyTv::OFFERTE_SKY])
        </div>
    </div>
    @if(false)
        <div class="row">
            <div class="col-md-6">
                @include('Backend._inputs.inputTextReadonly',['campo'=>'canali_opzionali','testo'=>'Canali opzionali','autocomplete'=>'off'])
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                @include('Backend._inputs.inputTextReadonly',['campo'=>'servizio_decoder','testo'=>'Servizio decoder','autocomplete'=>'off'])
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'tecnologia','testo'=>'Tecnologia','valore'=>\App\Models\ProdottoSkyTv::TECNOLOGIA[$record->tecnologia]])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'sky_q_mini','testo'=>'Sky q mini','array'=>['no','1','2','3']])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'solo_smartcard','testo'=>'Solo smartcard',])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'metodo_pagamento_tv','testo'=>'Metodo pagamento tv','valore'=>ucwords(str_replace('_',' ',$record->metodo_pagamento_tv))])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'frequenza_pagamento_tv','testo'=>'Frequenza pagamento tv','array'=>\App\Models\ProdottoSkyTv::FREQUENZA_PAGAMENTO_TV])
        </div>
    </div>
@endif
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'linea_telefonica','testo'=>'linea_telefonica','valore'=>$record->linea_telefonica?\App\Models\ProdottoSkyWifi::LINEA_TELEFONICA[$record->linea_telefonica]:''])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'numero_da_migrare','testo'=>'numero_da_migrare','array'=>\App\Models\ProdottoSkyTv::FREQUENZA_PAGAMENTO_TV])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_migrazione_voce','testo'=>'codice_migrazione_voce','array'=>\App\Models\ProdottoSkyTv::FREQUENZA_PAGAMENTO_TV])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_migrazione_dati','testo'=>'codice_migrazione_dati','array'=>\App\Models\ProdottoSkyTv::FREQUENZA_PAGAMENTO_TV])
    </div>
</div>
<h3 class="card-title">Dati pagamento</h3>


<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'metodo_pagamento_internet','testo'=>'Metodo pagamento','valore'=>ucwords(str_replace('_',' ',$record->metodo_pagamento_internet))])
    </div>
</div>
@if($record->metodo_pagamento_internet=='carta_credito')
    <div id="div_carta">

        <div class="row">
            <div class="col-md-6">
                @include('Backend._inputs.inputTextReadonly',['campo'=>'carta_di_credito_tipo','testo'=>'Carta di credito','array'=>\App\Models\ProdottoSkyTv::CARTA_DI_CREDITO])
            </div>
            <div class="col-md-6">
                @include('Backend._inputs.inputTextReadonly',['campo'=>'carta_di_credito_numero','testo'=>'Carta di credito numero','autocomplete'=>'off'])
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'carta_di_credito_valida_al','testo'=>'Carta di credito valida al','autocomplete'=>'off','altro'=>"data-inputmask=\"'mask': '99/99'\""])
                </div>
            </div>

        </div>

        <div class="row">
            <div class="col-md-6">
                @include('Backend._inputs.inputTextReadonly',['campo'=>'carta_di_credito_cognome','testo'=>'Carta di credito cognome','autocomplete'=>'off'])
            </div>
            <div class="col-md-6">
                @include('Backend._inputs.inputTextReadonly',['campo'=>'carta_di_credito_nome','testo'=>'Carta di credito nome','autocomplete'=>'off'])
            </div>
        </div>
    </div>
@else
    <div id="div_sepa">

        <div class="row">
            <div class="col-md-6">
                @include('Backend._inputs.inputTextReadonly',['campo'=>'sepa_banca','testo'=>'Sepa banca','autocomplete'=>'off'])
            </div>
            <div class="col-md-6">
                @include('Backend._inputs.inputTextReadonly',['campo'=>'sepa_agenzia','testo'=>'Sepa agenzia','autocomplete'=>'off'])
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
                @include('Backend._inputs.inputTextReadonly',['campo'=>'sepa_iban','testo'=>'Sepa iban','autocomplete'=>'off'])
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                @include('Backend._inputs.inputTextReadonly',['campo'=>'sepa_intestatario','testo'=>'Sepa intestatario','autocomplete'=>'off'])
            </div>
            <div class="col-md-6">
                @include('Backend._inputs.inputTextReadonly',['campo'=>'sepa_via','testo'=>'Sepa via','autocomplete'=>'off'])
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                @include('Backend._inputs.inputTextReadonly',['campo'=>'sepa_codice_fiscale','testo'=>'Sepa codice fiscale','autocomplete'=>'off'])
            </div>
        </div>
    </div>
@endif
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'consenso_1','testo'=>'Finalità di marketing canali tradizionali','valore'=>\App\siNo($record->consenso_1)])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'consenso_2','testo'=>'Finalità di marketing canali digitali','valore'=>\App\siNo($record->consenso_2)])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'consenso_3','testo'=>'Finalità di marketing di terze parti','valore'=>\App\siNo($record->consenso_3)])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'consenso_4','testo'=>'Finalità di profilazione generica','valore'=>\App\siNo($record->consenso_4)])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'consenso_5','testo'=>'Finalità di profilazione Sky Q','valore'=>\App\siNo($record->consenso_5)])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'consenso_6','testo'=>'Finalità di profilazione Sky Broadband','valore'=>\App\siNo($record->consenso_6)])
    </div>
</div>
