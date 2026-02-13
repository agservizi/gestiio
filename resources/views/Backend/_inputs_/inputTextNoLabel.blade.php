<div class="fv-row fv-plugins-icon-container mb-6">

    <input type="text" id="{{$campo}}" name="{{$campo}}" class="form-control form-control-solid {{$classe??''}}" placeholder="{{$placeholder??''}}"
           value="{{ old($campo,$record->$campo) }}" data-required="{{$required??''}}"
           @if($required??false) required @endif
           autocomplete="{{$autocomplete??''}}"
    >

    @if($help??false)
        <div class="form-text">{{$help}}</div>
    @endif

    <div class="fv-plugins-message-container invalid-feedback">
        @error($campo)
        {{$message}}
        @enderror
    </div>
</div>
