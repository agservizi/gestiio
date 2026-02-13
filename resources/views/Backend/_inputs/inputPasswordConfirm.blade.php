<div class="row mb-6" id="div_password_confirmation">
    <div class="col-lg-{{$col??4}} col-form-label text-lg-end">
        <label class="fw-bold fs-6  required " for="password_confirmation" id="label_password_confirmation" >{{$testo}}</label>
        @if($tooltip??false)
            <i class="fas fa-exclamation-circle ms-1 fs-7" data-bs-toggle="tooltip" title="" data-bs-original-title="{{$tooltip}}" aria-label="{{$tooltip}}"></i>
        @endif
    </div>
    <div class="col-lg-{{isset($col)?(12-$col):8}} fv-row fv-plugins-icon-container">
        <input type="password" id="password_confirmation" placeholder="Conferma password" name="password_confirmation" autocomplete="new-password"
               class="form-control form-control-solid" required/>
        @if($help??false)
            <div class="form-text">{!! $help !!}</div>
        @endif
        <div class="fv-plugins-message-container invalid-feedback">
            @error('password_confirmation')
            {{$message}}
            @enderror
        </div>
    </div>
</div>





