<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'convergenza_mobile','testo'=>'Convergenza mobile'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'easy_pay','testo'=>'Easy pay'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'giga_illimitati_superfibra','testo'=>'Giga illimitati superfibra'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'smartphone_a_rate','testo'=>'Smartphone a rate','array' => [''=>'','carta_di_credito'=>'Carta di credito','sdd'=>'SDD','finanziamento'=>'Finanziamento'],'altro' => 'data-allow-clear="true"'])
    </div>
</div>
