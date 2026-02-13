<div class="row mb-6">
    <div class="col-lg-4 col-form-label text-lg-end">
        <label class="form-check-label fs-6 fw-bold" for="{{$campo}}">
            {{$testo}}
        </label>
    </div>
    <div class="col-lg-8 fv-row fv-plugins-icon-container">
        <div class="form-check form-switch form-check-custom form-check-solid mt-2">
            <input class="form-check-input" type="checkbox" value="1" id="{{$campo}}" name="{{$campo}}"  {{old($campo,$record->$campo)?'checked':''}}/>
        </div>
        @if($help??false)
            <div class="form-text">{{$help}}</div>
        @endif
    </div>
</div>
