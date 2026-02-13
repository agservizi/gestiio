<div class="row mb-6" id="div_{{$campo}}">
    <div class="col-lg-{{$col??4}} col-form-label text-lg-end">
        <label class="fw-bold fs-6 @if($required??false) required @endif" for="{{$campo}}">{{$testo}}</label>
        @if($tooltip??false)
            <i class="fas fa-exclamation-circle ms-1 fs-7" data-bs-toggle="tooltip" title="" data-bs-original-title="{{$tooltip}}" aria-label="{{$tooltip}}"></i>
        @endif
    </div>
    <div class="col-lg-{{isset($col)?(12-$col):8}} fv-row fv-plugins-icon-container">

        <div class="input-group mb-3">
            <input type="text" id="{{$campo}}" name="{{$campo}}" class="form-control form-control-solid {{$classe??''}}" placeholder="{{$placeholder??''}}"
                   value="{{ old($campo,$record->$campo) }}" data-required="{{$required??''}}"
                   @if($required??false) required @endif
                   autocomplete="{{$autocomplete??''}}"
            >
            <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Scegli</button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg" data-id="{{$campo}}">
                @foreach($array as $g)
                    <li><a class="dropdown-item piu-usato" href="javascript:void(0)">{{$g['nome']}}</a></li>
                @endforeach
            </ul>
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
</div>
