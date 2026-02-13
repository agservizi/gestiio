<h3 class="card-title">Indirizzo fattura</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_fattura','testo'=>'Indirizzo fattura',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'citta_fattura','testo'=>'Citta fattura',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_fattura','testo'=>'Cap fattura',])
    </div>
</div>
<h3 class="card-title">Indirizzo tim card</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_timcard','testo'=>'Indirizzo timcard',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'citta_timcard','testo'=>'Citta timcard',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_timcard','testo'=>'Cap timcard',])
    </div>
</div>
<h3 class="card-title">LA TUA LINEA DI CASA</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'la_tua_linea_di_casa','testo'=>'La tua linea di casa','required'=>true,'array'=>\App\Models\ProdottoTimWifi::TIPO_LINEA])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'variazione_numero','testo'=>'Variazione numero',])
    </div>
</div>
@if($record->la_tua_linea_di_casa=='ATTIVAZIONE LINEA FISSA PER PASSAGGIO DA ALTRO OPERATORE')
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputRadioH',['campo'=>'codice_migrazione','testo'=>'Codice migrazione','required'=>true,'array'=>\App\Models\ProdottoTimWifi::TIPO_LINEA])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'numero_telefonico','testo'=>'Numero telefonico',])
        </div>
    </div>
@endif
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
            @include('Backend._inputs.inputTextReadonly',['campo'=>'linea_mobile_operatore','testo'=>'Linea mobile operatore',])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'linea_mobile_abbinata_offerta','testo'=>'Linea mobile abbinata offerta',])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'linea_mobile_cf_piva_attuale','testo'=>'Linea mobile cf piva attuale',])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputTextReadonly',['campo'=>'linea_mobile_numero_seriale','testo'=>'Linea mobile numero seriale',])
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
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'qualora','testo'=>'Qualora non sia possibile attivare l’offerta TIM WiFi POWER FIBRA ti sarà attivata, se disponibile, l’offerta TIM WiFi POWER MEGA con velocità fino a 200/20 Mega oppure fino a 100/20 Mega allo stesso costo mensile e con i medesimi contenuti','valore' => \App\siNo($record->qualora)])
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
        @include('Backend._inputs.inputTextReadonly',['campo'=>'pagamento_bollettino','testo'=>'Pagamento bollettino','valore' => \App\siNo($record->pagamento_bollettino)])
    </div>
</div>




@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            $('#contratto_id').select2({
                placeholder: 'Seleziona un contratto',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/select2?contratto",
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
