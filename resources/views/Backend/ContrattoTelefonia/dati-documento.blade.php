<h3 class="card-title">Dati documento</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'tipo_documento','testo'=>'Tipo documento','required'=>false,'array'=>\App\Models\ContrattoTelefonia::TIPI_DOCUMENTO ])
    </div>
    <div class="col-md-6">@include('Backend._inputs.inputRadioH',['campo'=>'rilasciato_da','testo'=>'Rilasciato da','required'=>false,'array'=>['COMUNE'=>'COMUNE','MIT UCO'=>'MIT UCO', 'MC'=>'MC', 'MI'=>'MI']])</div>

    <div class="col-md-6">@include('Backend._inputs.inputText',['campo'=>'numero_documento','testo'=>'Numero Documento','required'=>false,'autocomplete'=>'off'])</div>
    <div class="col-md-6">@include('Backend._inputs.inputSelect2',['campo'=>'comune_rilascio','testo'=>'Comune rilascio','required'=>false,'selected'=>\App\Models\Comune::selected(old('comune_rilascio',$record->comune_rilascio))])</div>

</div>
<div class="row">
    <div class="col-md-6">@include('Backend._inputs.inputTextDataMask',['campo'=>'data_rilascio','testo'=>'Data rilascio','required'=>false,'autocomplete'=>'off'])</div>
    <div class="col-md-6">@include('Backend._inputs.inputTextDataMask',['campo'=>'data_scadenza','testo'=>'Data scadenza','required'=>false,'autocomplete'=>'off'])</div>
</div>
<div class="row">
    <div class="col-md-6">@include('Backend._inputs.inputSelect2',['campo'=>'cittadinanza','testo'=>'Cittadinanza','required'=>false,'selected'=>\App\Models\Nazione::selected(old('cittadinanza',$record->cittadinanza))])</div>
</div>
<div class="row" id="div_permesso_soggiorno">
    <div class="col-md-6">@include('Backend._inputs.inputTextDataMask',['campo'=>'permesso_soggiorno_numero','testo'=>'Permesso soggiorno numero','required'=>false,'autocomplete'=>'off'])</div>
    <div class="col-md-6">@include('Backend._inputs.inputTextDataMask',['campo'=>'permesso_soggiorno_scadenza','testo'=>'Permesso soggiorno scadenza','required'=>false,'autocomplete'=>'off'])</div>
</div>


