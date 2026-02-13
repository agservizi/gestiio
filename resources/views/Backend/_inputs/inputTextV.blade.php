<div class="fv-row fv-plugins-icon-container mb-6">
    <!--begin::Label-->
    <!--end::Label-->
    <!--begin::Input-->
    <input type="text" id="{{$campo}}" name="{{$campo}}" class="form-control form-control-solid {{$classe??''}}" placeholder="{{$placeholder??''}}"
           value="{{ old($campo,$record->$campo) }}" data-required="{{$required??''}}"
           @if($required??false) required @endif
           autocomplete="{{$autocomplete??''}}"
           @isset($mask)
               data-inputmask="'mask': '{{$mask}}'"
            @endisset
    >
    @if($help??false)
        <div class="form-text">{{$help}}</div>
    @endif

    <!--end::Input-->
    <div class="fv-plugins-message-container invalid-feedback">
        @error($campo)
        {{$message}}
        @enderror
    </div>
</div>
