<div class=" mb-6" id="div_{{$campo}}" data-kt-password-meter="true">
    <label class="fw-bold fs-6 @if($required??false) required @endif" for="{{$campo}}" id="label_{{$campo}}">{{$testo}}</label>
    @if($tooltip??false)
        <i class="fas fa-exclamation-circle ms-1 fs-7" data-bs-toggle="tooltip" title="" data-bs-original-title="{{$tooltip}}" aria-label="{{$tooltip}}"></i>
    @endif
    <div class="mb-1">
        <!--begin::Input wrapper-->
        <div class="position-relative mb-3">
            <input class="form-control bg-transparent @error('password') is-invalid @enderror" type="password" placeholder="Password" name="password"
                   autocomplete="new-password"/>
            <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" data-kt-password-meter-control="visibility">
												<i class="bi bi-eye-slash fs-2"></i>
												<i class="bi bi-eye fs-2 d-none"></i>
											</span>
        </div>
        <!--end::Input wrapper-->

        <!--begin::Meter-->
        <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
            <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
            <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
            <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
            <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
        </div>
        <!--end::Meter-->
        @error('password')
        <div class="fv-plugins-message-container invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <!--end::Wrapper-->
    <!--begin::Hint-->
    <div class="text-muted">Usa 8 o più caratteri con un mix di lettere, numeri e simboli.</div>
    <!--end::Hint-->
</div>
<!--end::Input group=-->
