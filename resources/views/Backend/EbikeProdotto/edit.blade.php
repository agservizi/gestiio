@extends('Backend._layout._main')
@section('content')
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST"
                  action="{{$record->id ? action([$controller,'update'],$record->id) : action([$controller,'store'])}}"
                  enctype="multipart/form-data">
                @csrf
                @if($record->id)
                    @method('PUT')
                @endif
                <div class="row mb-6">
                    <div class="col-lg-4 col-form-label text-lg-end"><label class="fw-bold fs-6 required">Nome</label></div>
                    <div class="col-lg-8 fv-row">
                        <input type="text" name="nome" class="form-control" value="{{old('nome',$record->nome)}}" required>
                    </div>
                </div>
                <div class="row mb-6">
                    <div class="col-lg-4 col-form-label text-lg-end"><label class="fw-bold fs-6">SKU</label></div>
                    <div class="col-lg-8 fv-row">
                        <input type="text" class="form-control" value="{{$record->sku}}" readonly disabled>
                        <div class="form-text">Generato automaticamente, non modificabile.</div>
                    </div>
                </div>
                <div class="row mb-6">
                    <div class="col-lg-4 col-form-label text-lg-end"><label class="fw-bold fs-6">Descrizione</label></div>
                    <div class="col-lg-8 fv-row">
                        <textarea name="descrizione" class="form-control" rows="4">{{old('descrizione',$record->descrizione)}}</textarea>
                    </div>
                </div>
                <div class="row mb-6">
                    <div class="col-lg-4 col-form-label text-lg-end"><label class="fw-bold fs-6 required">Prezzo (IVA inclusa)</label></div>
                    <div class="col-lg-8 fv-row">
                        <input type="text" name="prezzo" class="form-control" value="{{old('prezzo',$record->prezzo)}}" required>
                    </div>
                </div>
                <div class="row mb-6">
                    <div class="col-lg-4 col-form-label text-lg-end"><label class="fw-bold fs-6 required">Giacenza</label></div>
                    <div class="col-lg-8 fv-row">
                        <input type="number" min="0" name="giacenza" class="form-control" value="{{old('giacenza',$record->giacenza ?? 0)}}" required>
                    </div>
                </div>
                <div class="row mb-6">
                    <div class="col-lg-4 col-form-label text-lg-end"><label class="fw-bold fs-6">Immagine</label></div>
                    <div class="col-lg-8 fv-row">
                        <input type="file" name="immagine" class="form-control" accept="image/*">
                        @if($record->immagine)
                            <img src="{{$record->urlImmagine()}}" alt="{{$record->nome}}" style="max-width:120px" class="mt-3 rounded zoomable-thumb">
                        @endif
                    </div>
                </div>
                <div class="row mb-6">
                    <div class="col-lg-4 col-form-label text-lg-end"><label class="fw-bold fs-6">Attivo nel catalogo</label></div>
                    <div class="col-lg-8 fv-row">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="attivo" value="1" {{old('attivo',$record->attivo ?? true) ? 'checked' : ''}}>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <a href="{{action([$controller,'index'])}}" class="btn btn-light me-3">Annulla</a>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>
@endsection
