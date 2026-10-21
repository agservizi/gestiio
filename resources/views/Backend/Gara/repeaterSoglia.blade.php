<div data-repeater-item class="row mb-3 align-items-end">
    <input type="hidden" name="id" value="{{ $record->id ?? '' }}"/>
    <div class="col-md-3">
        <label class="form-label">Da contratti</label>
        <input type="number" min="1" class="form-control form-control-solid" name="da_valore"
               value="{{ old('da_valore', $record->da_valore ?? 1) }}"/>
    </div>
    <div class="col-md-3">
        <label class="form-label">A contratti</label>
        <input type="number" min="1" class="form-control form-control-solid" name="a_valore"
               value="{{ old('a_valore', $record->a_valore) }}" placeholder="∞"/>
    </div>
    <div class="col-md-4">
        <label class="form-label">Premio €</label>
        <input type="text" class="form-control form-control-solid" name="premio"
               value="{{ old('premio', $record->premio ?? 0) }}"/>
    </div>
    <div class="col-md-2">
        <button type="button" data-repeater-delete class="btn btn-sm btn-light-danger">Rimuovi</button>
    </div>
</div>
