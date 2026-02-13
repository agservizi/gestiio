<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'citta_di_nascita','testo'=>'Citta di nascita','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'provincia_di_nascita','testo'=>'Provincia di nascita','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'nazionalita','testo'=>'Nazionalita','autocomplete'=>'off'])
    </div>
</div>


<h3 class="card-title">Indirizzo impianto</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo_impianto','testo'=>'Indirizzo impianto','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'civico_impianto','testo'=>'Civico impianto','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2',['campo'=>'localita_impianto','testo'=>'Localita impianto','selected'=>\App\Models\Comune::selected(old('localita_impianto',$record->localita_impianto))])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'piano_impianto','testo'=>'Piano impianto','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'scala_impianto','testo'=>'Scala impianto','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'interno_impianto','testo'=>'Interno impianto','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'citofono_impianto','testo'=>'Citofono impianto','autocomplete'=>'off'])
    </div>
</div>


<h3 class="card-title">Indirizzo fattura</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo_fattura','testo'=>'Indirizzo fattura','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'civico_fattura','testo'=>'Civico fattura','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2',['campo'=>'citta_fattura','testo'=>'Citta fattura','selected'=>\App\Models\Comune::selected(old('citta_fattura',$record->citta_fattura))])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cap_fattura','testo'=>'Cap fattura','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">Indirizzo tim card</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo_timcard','testo'=>'Indirizzo timcard','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'civico_timcard','testo'=>'Civico timcard','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2',['campo'=>'citta_timcard','testo'=>'Citta timcard','selected'=>\App\Models\Comune::selected(old('citta_timcard',$record->citta_timcard))])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cap_timcard','testo'=>'Cap timcard','autocomplete'=>'off'])
    </div>
</div>


<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'numero_cellulare','testo'=>'Numero cellulare','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'recapito_alternativo','testo'=>'Recapito alternativo','autocomplete'=>'off'])
    </div>
</div>


<h3 class="card-title">Firmatario</h3>
<p>
    Per il Cliente che ha già la linea di casa TIM, inserire i dati del FIRMATARIO (coniuge/coabitante autorizzato dal Titolare) se diverso da TITOLARE (in tal caso non potranno
    essere richiesti servizi forniti da terze parti quali Mediaset Premium on line e Sky)
</p>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'firmatario_nome_cognome','testo'=>'Nome Cognome','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'firmatario_indirizzo_completo','testo'=>'Indirizzo completo','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'firmatario_tipo_documento','testo'=>'Tipo documento','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'firmatario_rilasciato_da','testo'=>'Rilasciato da','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextDataMask',['campo'=>'firmatario_data_emissione','testo'=>'Data emissione','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextDataMask',['campo'=>'firmatario_data_scadenza','testo'=>'Data scadenza','autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">LA TUA LINEA DI CASA</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'la_tua_linea_di_casa','testo'=>'La tua linea di casa','required'=>true,'array'=>\App\Models\ProdottoTimWifi::TIPO_LINEA])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'variazione_numero','testo'=>'Variazione numero','autocomplete'=>'off'])
    </div>
</div>
<div class="row" id="div-passaggio">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_migrazione','testo'=>'Codice migrazione','autocomplete'=>'off'])
    </div>

    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'numero_telefonico','testo'=>'Numero telefonico','autocomplete'=>'off'])
    </div>
</div>

@if(false)
    <h3 class="card-title">LA TUA LINEA MOBILE</h3>

    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputSwitch',['campo'=>'linea_mobile_tim','testo'=>'Linea mobile tim',])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputSwitch',['campo'=>'linea_mobile_new','testo'=>'Linea mobile new',])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputSwitch',['campo'=>'linea_mobile_abbonamento','testo'=>'Linea mobile abbonamento',])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputSwitch',['campo'=>'linea_mobile_prepagato','testo'=>'Linea mobile prepagato',])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'linea_mobile_operatore','testo'=>'Linea mobile operatore','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'linea_mobile_abbinata_offerta','testo'=>'Linea mobile abbinata offerta','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'linea_mobile_cf_piva_attuale','testo'=>'Linea mobile cf piva attuale','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',['campo'=>'linea_mobile_numero_seriale','testo'=>'Linea mobile numero seriale','autocomplete'=>'off'])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputSwitch',['campo'=>'linea_mobile_trasferimento_credito','testo'=>'Linea mobile trasferimento credito',])
        </div>
    </div>
@endif
<h3 class="card-title">LA TUA OFFERTA TIM WiFi Power</h3>
<div class="row">
    <div class="col-md-12">
        @include('Backend._inputs.inputRadioH',['campo'=>'la_tua_offerta','testo'=>'La tua offerta','required'=>true,'array'=>\App\Models\ProdottoTimWifi::OFFERTA,'col' => 2])
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        @include('Backend._inputs.inputCheckboxH',['campo'=>'opzione_inclusa','testo'=>'Opzione inclusa','array'=>\App\Models\ProdottoTimWifi::OPZIONI,'col' => 2])
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        @include('Backend._inputs.inputRadioV',['campo'=>'qualora','testo'=>'Qualora non sia possibile attivare l’offerta TIM WiFi POWER FIBRA ti sarà attivata, se disponibile, l’offerta TIM WiFi POWER MEGA con velocità fino a 200/20 Mega oppure fino a 100/20 Mega allo stesso costo mensile e con i medesimi contenuti','array' => [0=>'No',1=>'Si']])
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        @include('Backend._inputs.inputRadioH',['campo'=>'modem_tim','testo'=>'Modem tim','array'=>\App\Models\ProdottoTimWifi::MODEM,'col' => 2])
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        @include('Backend._inputs.inputCheckboxH',['campo'=>'offerta_scelta','testo'=>'Offerta scelta','array'=>\App\Models\ProdottoTimWifi::OFFERTA_SCELTA,'col' => 2])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'pagamento_bollettino','testo'=>'Pagamento bollettino',])
    </div>
</div>
@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            mostraNascondiPassaggio($('.la_tua_linea_di_casa:checked').val() === 'ATTIVAZIONE LINEA FISSA PER PASSAGGIO DA ALTRO OPERATORE');

            $('.la_tua_linea_di_casa').click(function () {
                mostraNascondiPassaggio($(this).val() === 'ATTIVAZIONE LINEA FISSA PER PASSAGGIO DA ALTRO OPERATORE');
            });

            function mostraNascondiPassaggio(mostra) {
                $('#div-passaggio').toggle(mostra);
            }

            $('#citta_fattura').select2({
                placeholder: 'Seleziona una città',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/backend/select2?citta",
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
                $("#cap_fattura").val(e.params.data.cap);
            });
            $('#localita_impianto').select2({
                placeholder: 'Seleziona una città',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/backend/select2?citta",
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
                //$("#cap_fattura").val(e.params.data.cap);
            });
            $('#citta_timcard').select2({
                placeholder: 'Seleziona una città',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/backend/select2?citta",
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
                $("#cap_timcard").val(e.params.data.cap);
            });

        });
    </script>
@endpush
