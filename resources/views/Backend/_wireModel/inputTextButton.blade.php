<div class="row mb-6">
    <!--begin::Label-->
    <div class="col-lg-4 col-form-label text-lg-end">
        <label class="fw-bold fs-6 @if($required??false) required @endif" for="{{$campo}}">{{$testo}}</label>
        @if($tooltip??false)
            <i class="fas fa-exclamation-circle ms-1 fs-7" data-bs-toggle="tooltip" title="" data-bs-original-title="{{$tooltip}}" aria-label="{{$tooltip}}"></i>
        @endif
    </div>
    <!--end::Label-->
    <!--begin::Col-->
    <div class="col-lg-8 fv-row fv-plugins-icon-container">
        <div class="input-group mb-3">
            <input type="text" id="{{$campo}}" name="{{$campo}}" class="form-control form-control-solid {{$classe??''}}" placeholder="{{$placeholder??''}}" value="{{ old($campo) }}"
                   data-required="{{$required??''}}"
                   @if($required??false) required @endif
                   autocomplete="{{$autocomplete??''}}"
                    {!! $altro??'' !!}
            />
            <button class="btn {{$classeButton??'btn-primary'}}" type="button" id="button-{{$campo}}">{{$testoButton??'Ok'}}</button>
        </div>
        @if($help??false)
            <div class="form-text">{{$help}}</div>
        @endif
        <div class="fv-plugins-message-container invalid-feedback">
            @error($campo)
            {{$message}}
            @enderror
        </div>
    </div>
    <!--end::Col-->
</div>

