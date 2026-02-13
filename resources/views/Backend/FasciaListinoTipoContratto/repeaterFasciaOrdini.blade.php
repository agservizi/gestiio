<div data-repeater-item class="row">
    <input type="hidden" name="id" value="{{$record->id??''}}">
    <div class="col-md-3">
        @include('Backend._inputs_.inputTextNoLabel',['campo'=>'da_contratti','testo'=>'Da contratti','required'=>true])
    </div>
    <div class="col-md-4">
        @include('Backend._inputs_.inputTextNoLabel',['campo'=>'importo_per_contratto','testo'=>'Importo per contratto','required'=>false,'classe'=>'autonumericImporto'])
    </div>
    <div class="col-md-4">
        @include('Backend._inputs_.inputTextNoLabel',['campo'=>'importo_bonus','testo'=>'Importo bonus','required'=>false,'classe'=>'autonumericImporto'])
    </div>
    <div class="col-md-1">
        <button data-repeater-delete class="btn btn-sm btn-danger" type="button" tabindex="-1">
            <i class="fa fa-trash"></i>
        </button>
    </div>
</div>
