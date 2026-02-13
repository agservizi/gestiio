<div class="col-form-label ">
    <!--begin::Label-->
    <label class="fw-bold fs-6 @if($required??false) required @endif" for="{{$campo}}">{{$testo}}</label>
    @if($tooltip??false)
        <i class="fas fa-exclamation-circle ms-1 fs-7" data-bs-toggle="tooltip" title="" data-bs-original-title="{{$tooltip}}" aria-label="{{$tooltip}}"></i>
    @endif
    <!--end::Label-->
    <!--begin::Col-->
</div>
<div class="fv-row fv-plugins-icon-container">



    <textarea id="{{$campo}}" name="{{$campo}}" class="form-control form-control-solid" data-kt-autosize="true" placeholder="{{$placeholder??''}}"
              @if($required??false) required @endif
              autocomplete="{{$autocomplete??''}}"
    >{{ old($campo,$record->$campo) }}</textarea>
    @if($help??false)
        <div class="form-text">{{$help}}</div>
    @endif
    <div class="fv-plugins-message-container invalid-feedback">
        @error($campo)
        {{$message}}
        @enderror
    </div>
</div>


