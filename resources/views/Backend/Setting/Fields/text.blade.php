<div class="row mb-6">
    <div class="col-lg-4 col-form-label text-lg-end">
        <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
    </div>
    <div class="col-lg-8 fv-row fv-plugins-icon-container">
        <input type="{{ $field['type'] }}"
               name="{{ $field['name'] }}"
               value="{{ old($field['name'], \App\setting($field['name'])) }}"
               class="form-control {{ \Illuminate\Support\Arr::get( $field, 'class') }} form-control-solid"
               id="{{ $field['name'] }}"
               placeholder="{{ $field['label'] }}">

        <div class="fv-plugins-message-container invalid-feedback">
            @error($field['name'])
            {{$message}}
            @enderror
        </div>
    </div>
</div>
