<div class="row mb-6" id="div_{{$campo}}">
    <div class="col-lg-12 fv-row fv-plugins-icon-container">
        <select id="{{$campo}}" name="{{$campo}}" class="form-select form-select-solid" @if($required??false) required @endif data-required="{{$required??''}}">
            @if($selected)
                {!! $selected !!}
            @endif
        </select>
        @if($help??false)
            <div class="form-text">{{$help}}</div>
        @endif
        <div class="fv-plugins-message-container invalid-feedback">
            @error($campo)
            {{$message}}
            @enderror
        </div>
    </div>
</div>

