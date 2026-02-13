<div class="row mb-6" id="div_{{$campo}}">
    <div class="col-lg-{{$col??4}} col-form-label text-lg-end">
        <label class="fw-bold fs-6" for="{{$campo}}" id="label_{{$campo}}">{!! $testo !!}</label>
        @if($tooltip??false)
            <i class="fas fa-exclamation-circle ms-1 fs-7" data-bs-toggle="tooltip" title="" data-bs-original-title="{{$tooltip}}" aria-label="{{$tooltip}}"></i>
        @endif
    </div>
    <div class="col-lg-{{isset($col)?(12-$col):8}} fv-row fv-plugins-icon-container">
        <span id="{{$campo}}" class="form-control {{$classe??''}}"
              style="min-height: 42px;"
            {!! $altro??'' !!}
        >{!!  $valore??$record->$campo !!}</span>
        @if($help??false)
            <div class="form-text">{{$help}}</div>
        @endif
    </div>
</div>

