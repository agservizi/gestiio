<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'convergenza_mobile','testo'=>'Convergenza mobile','valore' => \App\siNo($record->convergenza_mobile)])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'easy_pay','testo'=>'Easy pay','valore' => \App\siNo($record->easy_pay)])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'giga_illimitati_superfibra','testo'=>'Easy pay','valore' => \App\siNo($record->easy_pay)])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'smartphone_a_rate','testo'=>'Smartphone a rate','valore' => [''=>'','carta_di_credito'=>'Carta di credito','sdd'=>'SDD','finanziamento'=>'Finanziamento'][$record->smartphone_a_rate],'altro' => 'data-allow-clear="true"'])
    </div>
</div>
