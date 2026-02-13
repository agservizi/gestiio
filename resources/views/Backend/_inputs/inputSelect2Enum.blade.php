<div class="row mb-6" id="div_{{$campo}}">
    <div class="col-lg-4 col-form-label text-lg-end">

        <label class="fw-bold fs-6 @if($required??false) required @endif" for="{{$campo}}"
               id="label_{{$campo}}">{{$label??ucwords(str_replace('_',' ',$campo))}}</label>
    </div>
    <div class="col-lg-8 fv-row fv-plugins-icon-container">
        @php($selected=old($campo,$record->$campo))
        <select id="{{$campo}}" name="{{$campo}}" class="form-select form-select-solid" @if($required??false) required
                @endif data-required="{{$required??''}}"
                data-kt-select2="true" data-placeholder="{{$placeholder??'Seleziona'}}" @isset($altro)
                    {!! $altro !!}
                @endisset
                data-minimum-results-for-search="Infinity"
        >
            <option value="">{{$placeholder??'Seleziona'}}</option>
            @foreach($cases::cases() as $item)
                <option value="{{$item->value}}" {{$selected==$item->value?'selected':''}}>{{$item->testo()}}</option>
            @endforeach

        </select>
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
