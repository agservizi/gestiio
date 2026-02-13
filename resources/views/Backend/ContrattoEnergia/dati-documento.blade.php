<h3 class="card-title">Dati documento</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'tipo_documento','testo'=>'Tipo documento','required'=>false,'array'=>\App\Models\ContrattoEnergia::TIPI_DOCUMENTO ])
    </div>
    <div class="col-md-6">@include('Backend._inputs.inputRadioH',['campo'=>'rilasciato_da','testo'=>'Rilasciato da','required'=>false,'array'=>['COMUNE'=>'COMUNE','MIT UCO'=>'MIT UCO', 'MC'=>'MC', 'MI'=>'MI']])</div>
    <div class="col-md-6">@include('Backend._inputs.inputText',['campo'=>'numero_documento','testo'=>'Numero Documento','required'=>false,'autocomplete'=>'off'])</div>
</div>
<div class="row">
    <div class="col-md-6">@include('Backend._inputs.inputTextDataMask',['campo'=>'data_rilascio','testo'=>'Data rilascio','required'=>false,'autocomplete'=>'off'])</div>
    <div class="col-md-6">@include('Backend._inputs.inputTextDataMask',['campo'=>'data_scadenza','testo'=>'Data scadenza','required'=>false,'autocomplete'=>'off'])</div>
</div>
