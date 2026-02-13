<div class="fv-row fv-plugins-icon-container mb-6">
    <label class="d-flex align-items-center fs-5 fw-bold mb-2" for="{{$campo}}">
        {{$testo}}
    </label>

    <div class="form-check form-switch form-check-custom form-check-solid">
        <input class="form-check-input" type="checkbox" value="1" id="{{$campo}}" name="{{$campo}}" {{old($campo,$record->$campo)?'checked':''}}/>
        @if($help??false)
            <div class="form-text m-lg-4">{{$help}}</div>
        @endif
    </div>
</div>
