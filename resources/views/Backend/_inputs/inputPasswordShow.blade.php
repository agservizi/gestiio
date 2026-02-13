<div class="row mb-6" id="div_{{$campo}}">
    <div class="col-lg-{{$col??4}} col-form-label text-lg-end">
        <label class="fw-bold fs-6 @if($required??false) required @endif" for="{{$campo}}" id="label_{{$campo}}" >{{$testo}}</label>
        @if($tooltip??false)
            <i class="fas fa-exclamation-circle ms-1 fs-7" data-bs-toggle="tooltip" title="" data-bs-original-title="{{$tooltip}}" aria-label="{{$tooltip}}"></i>
        @endif
    </div>
    <div class="col-lg-{{isset($col)?(12-$col):8}} fv-row fv-plugins-icon-container">
        <div class="input-group">


            <input type="password" id="{{$campo}}" name="{{$campo}}" class="form-control form-control-solid {{$classe??''}}" placeholder="{{$placeholder??''}}"
                   value="{{ old($campo,$newPassword??'') }}" data-required="{{$required??''}}"
                   @if($required??false) required @endif
                   autocomplete="{{$autocomplete??''}}"
                    {!! $altro??'' !!}
            >

            <div class="position-absolute translate-middle-y top-50 end-0 me-3" onclick="showHidePasword();"><i class="fas fa-eye fs-4" id="icona_pw"></i>
            </div>
        </div>

        @if($help??false)
            <div class="form-text">{{$help}}</div>
        @endif
        @includeWhen($include??false,$include??'')
        <div class="fv-plugins-message-container invalid-feedback">
            @error($campo)
            {{$message}}
            @enderror
        </div>
    </div>
</div>
<script>
    function showHidePasword() {
        var $password = $('#{{$campo}}');
        if ($password.prop('type') == 'password') {
            $password.prop('type', 'text');
            $('#icona_pw').removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            $password.prop('type', 'password');
            $('#icona_pw').removeClass('fa-eye-slash').addClass('fa-eye');

        }
    }
</script>

