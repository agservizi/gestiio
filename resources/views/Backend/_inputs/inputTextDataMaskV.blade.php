<div class="fv-row fv-plugins-icon-container mb-6">
    <!--begin::Label-->
    <label class="d-flex align-items-center fw-bold fs-6 col-form-label mb-2" for="{{$campo}}">
        <span class="@if($required??false) required @endif">{{$testo}}</span>
        @if($tooltip??false)
            <i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="tooltip" title="" data-bs-original-title="{{$tooltip}}"
               aria-label="{{$tooltip}}"></i>
        @endif
    </label>
    <!--end::Label-->
    <!--begin::Input-->
    <input type="text" id="{{$campo}}" name="{{$campo}}" class="form-control form-control-solid {{$classe??''}}" placeholder="{{$placeholder??''}}"
           value="{{ old($campo,$record->$campo?$record->$campo->format('d/m/y'):'') }}" data-required="{{$required??''}}"
           @if($required??false) required @endif
           autocomplete="{{$autocomplete??''}}"
           data-inputmask="'mask': '{{$mask??'99/99/9999'}}'"
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

