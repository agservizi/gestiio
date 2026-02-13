<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'importo_prestito','testo'=>'Importo prestito','required'=>true,"classe"=>"autonumericImporto"])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'durata_prestito','testo'=>'Durata prestito','required'=>true,'array'=>\App\arrayToKeyValue(\App\Models\ServizioPrestito::DURATA_MESI)])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'stato_civile','testo'=>'Stato civile','required'=>true,'array'=>\App\arrayToKeyValue(\App\Models\ServizioPrestito::STATO_CIVILE),'altro'=>'data-minimum-results-for-search="Infinity"'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'immobile_residenza','testo'=>'Immobile residenza','required'=>true,'array'=>\App\Models\ServizioPrestito::IMMOBILE_RESIDENZA,'altro'=>'data-minimum-results-for-search="Infinity"'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'telefono_fisso','testo'=>'Telefono fisso','required'=>true,'autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'prestiti_in_corso','testo'=>'Prestiti in corso',])
        @include('Backend._inputs.inputSwitch',['campo'=>'prestiti_in_passato','testo'=>'Prestiti in passato',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'motivazione_prestito','testo'=>'Motivazione prestito','required'=>true,'autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'componenti_famiglia','testo'=>'Componenti famiglia','required'=>true,'classe'=>'intero'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'componenti_famiglia_con_reddito','testo'=>'Componenti famiglia con reddito','required'=>true,'classe'=>'intero'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'titolo_studio','testo'=>'Titolo studio','required'=>true,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'reddito_mensile','testo'=>'Reddito mensile','required'=>true,"classe"=>"autonumericImporto"])
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'lavoro','testo'=>'Lavoro','required'=>true,'array'=>\App\Models\ServizioPrestito::LAVORO,'altro'=>'data-minimum-results-for-search="Infinity"'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'datore_lavoro_intestazione','testo'=>'Datore lavoro intestazione','required'=>true,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'mesi_anzianita_servizio','testo'=>'Mesi anzianita servizio','required'=>true,'autocomplete'=>'off','classe'=>'intero'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'anni_anzianita_servizio','testo'=>'Anni anzianita servizio','required'=>true,'autocomplete'=>'off','classe'=>'intero'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo_lavoro','testo'=>'Indirizzo lavoro','required'=>true,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2',['campo'=>'citta_lavoro','testo'=>'Citta lavoro','required'=>true,'selected'=>\App\Models\Comune::selected(old('citta_lavoro',$record->citta_lavoro))])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'telefono_lavoro','testo'=>'Telefono lavoro','required'=>true,'autocomplete'=>'off'])
    </div>
</div>
@include('Backend._inputs_.allegati')

@push('customScript')
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            autonumericImporto('autonumericImporto');
            autonumericIntero('intero');

            mostraNascondi($('#lavoro').val());


            $('#lavoro').change(function () {
                mostraNascondi($(this).val());
            });


            function mostraNascondi(lavoro) {
                var mostra = lavoro !== 'pensionato';
                mostraCampo('telefono_lavoro', mostra, false);
                mostraCampo('citta_lavoro', mostra, false);
                mostraCampo('indirizzo_lavoro', mostra, false);
                mostraCampo('anni_anzianita_servizio', mostra, false);
                mostraCampo('mesi_anzianita_servizio', mostra, false);
                mostraCampo('datore_lavoro_intestazione', mostra, false);
            }

            $('#citta_lavoro').select2({
                placeholder: 'Seleziona una citta',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/select2?citta",
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
            });
        });
    </script>
@endpush
