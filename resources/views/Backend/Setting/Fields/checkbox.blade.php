<div class="row mb-6">
    <div class="col-lg-4 col-form-label text-lg-end">
        <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
    </div>
    <div class="col-lg-8 fv-row fv-plugins-icon-container">
        <input type="hidden" name="{{ $field['name'] }}" value="0">
        <div class="form-check form-switch form-check-custom form-check-solid mt-3">
            <input type="checkbox"
                   name="{{ $field['name'] }}"
                   value="1"
                   class="form-check-input"
                   id="{{ $field['name'] }}"
                   @checked((bool) old($field['name'], \App\setting($field['name'], false)))>
            <label class="form-check-label" for="{{ $field['name'] }}">Attivo</label>
        </div>

        <div class="fv-plugins-message-container invalid-feedback">
            @if(isset($errors))
                @error($field['name'])
                {{$message}}
                @enderror
            @endif
        </div>
    </div>
</div>
