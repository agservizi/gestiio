@extends('Backend._layout._main')
@section('content')
    <div class="card mb-6">
        <div class="card-body">
            <h4 class="mb-3">Dati per il bonifico istantaneo</h4>
            <p class="text-muted mb-2">Effettua il bonifico istantaneo verso questo IBAN indicando come causale il numero ordine che riceverai dopo l'invio. Potrai poi caricare la ricevuta direttamente sull'ordine.</p>
            <ul class="mb-0">
                <li><strong>IBAN:</strong> {{$iban ?? 'Non configurato - contatta l\'amministrazione'}}</li>
                <li><strong>Intestatario:</strong> {{$intestatario ?? '-'}}</li>
                <li><strong>Banca:</strong> {{$banca ?? '-'}}</li>
            </ul>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'store'])}}">
                @csrf
                <div class="table-responsive mb-6">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th>Prodotto</th>
                                <th>Prezzo</th>
                                <th>Disponibilità</th>
                                <th style="width:140px">Quantità</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($prodotti as $prodotto)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-4">
                                            @if($prodotto->immagine)
                                                <img src="{{$prodotto->urlImmagine()}}" alt="" class="rounded" style="width:48px;height:48px;object-fit:cover;flex:0 0 auto;">
                                            @endif
                                            <span>{{$prodotto->nome}} <span class="text-muted">({{$prodotto->sku}})</span></span>
                                        </div>
                                    </td>
                                    <td>{{importo($prodotto->prezzo, true)}}</td>
                                    <td>{{$prodotto->giacenza}}</td>
                                    <td>
                                        <input type="number" min="0" max="{{$prodotto->giacenza}}"
                                               name="quantita[{{$prodotto->id}}]" class="form-control" value="0">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Nessun prodotto disponibile al momento.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mb-6">
                    <label class="fw-bold fs-6">Note (opzionale)</label>
                    <textarea name="note" class="form-control" rows="3">{{old('note')}}</textarea>
                </div>
                <div class="text-end">
                    <a href="{{action([$controller,'index'])}}" class="btn btn-light me-3">Annulla</a>
                    <button type="submit" class="btn btn-primary">Crea ordine</button>
                </div>
            </form>
        </div>
    </div>
@endsection
