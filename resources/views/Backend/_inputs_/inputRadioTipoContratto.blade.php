<div class="row mb-6" id="div_{{$campo}}">
    <div class="col-lg-{{$col??'4'}} col-form-label text-lg-end">
        <label class="form-check-label fw-bold fs-6 @if($required??false) required @endif">
            {{$testo}}
        </label>
    </div>
    <div class="col-lg-{{isset($col)?(12-$col):8}} pt-3">
        <div class="d-flex flex-wrap">
            @php($selected=old($campo,$record->$campo))
            @foreach($array as $key=>$value)
                <div class="form-check form-check-custom form-check-solid me-10 mb-2">
                    <input class="form-check-input {{$campo}}" type="radio" value="{{$key}}" name="{{$campo}}"
                           id="{{$campo.$key}}" {{($required??false)?'required':''}} {{$selected==$key?'checked':''}}>
                    <label class="form-check-label fw-bold" for="{{$campo.$key}}">{{$value->nome}}</label>
                </div>
            @endforeach
        </div>
        @if($help??false)
            <div class="form-text">{{$help}}</div>
        @endif
    </div>
</div>
