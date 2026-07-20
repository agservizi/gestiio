@php
    $name = $field['name'];
    $label = $field['label'];
    $type = $field['type'];
@endphp

<div class="row mb-6">
    <div class="col-lg-4 col-form-label text-lg-end">
        <label for="{{ $name }}">{{ $label }}</label>
    </div>
    <div class="col-lg-8">
        @if($type === 'checkbox')
            <input type="hidden" name="{{ $name }}" value="0">
            <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                <input type="checkbox" name="{{ $name }}" value="1" id="{{ $name }}" class="form-check-input"
                       @checked((bool) old($name, setting($name, true)))>
                <label class="form-check-label" for="{{ $name }}">Attivo</label>
            </div>
        @elseif($type === 'textarea')
            <textarea name="{{ $name }}" id="{{ $name }}" rows="4"
                      class="form-control form-control-solid">{{ old($name, setting($name, '')) }}</textarea>
        @else
            <input type="text" name="{{ $name }}" id="{{ $name }}"
                   value="{{ old($name, setting($name, '')) }}"
                   class="form-control form-control-solid">
        @endif
        @error($name)
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>
