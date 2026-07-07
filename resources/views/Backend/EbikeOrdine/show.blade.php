@extends('Backend._layout._main')
@section('content')
    @include('Backend._components.alertErrori')

    <div class="card mb-6">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Ordine #{{$record->id}}</h3>
                <span class="badge {{$record->stato->badge()}} fs-6">{{$record->stato->testo()}}</span>
            </div>
            <ul class="mb-0">
                <li><strong>Agente:</strong> {{$record->agente?->nominativo() ?? 'Agente #'.$record->agente_id}}</li>
                <li><strong>Totale:</strong> {{importo($record->totale, true)}}</li>
                @if($record->note)
                    <li><strong>Note:</strong> {{$record->note}}</li>
                @endif
                @if($record->scadenza_spedizione)
                    <li>
                        <strong>Scadenza spedizione (SLA {{\App\Models\OrdineEbike::GIORNI_SLA_SPEDIZIONE}} giorni):</strong>
                        {{$record->scadenza_spedizione->format('d/m/Y')}}
                        @if($record->scadenzaSuperata())
                            <span class="badge badge-light-danger">Superata</span>
                        @endif
                    </li>
                @endif
            </ul>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <h4 class="mb-3">Prodotti</h4>
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted">
                            <th>Prodotto</th>
                            <th>Quantità</th>
                            <th>Prezzo unitario</th>
                            <th>Subtotale</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($record->righe as $riga)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-4">
                                        @if($riga->prodotto?->immagine)
                                            <img src="{{$riga->prodotto->urlImmagine()}}" alt="" class="rounded zoomable-thumb" style="width:40px;height:40px;object-fit:cover;flex:0 0 auto;">
                                        @endif
                                        <span>{{$riga->nome_prodotto}}</span>
                                    </div>
                                </td>
                                <td>{{$riga->quantita}}</td>
                                <td>{{importo($riga->prezzo_unitario, true)}}</td>
                                <td>{{importo($riga->subtotale(), true)}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($record->stato->value === 'in_attesa_pagamento')
        <div class="card mb-6">
            <div class="card-body">
                <h4 class="mb-3">Dati per il bonifico istantaneo</h4>
                <ul>
                    <li><strong>IBAN:</strong> {{$iban ?? 'Non configurato - contatta l\'amministrazione'}}</li>
                    <li><strong>Intestatario:</strong> {{$intestatario ?? '-'}}</li>
                    <li><strong>Banca:</strong> {{$banca ?? '-'}}</li>
                    <li><strong>Causale:</strong> Ordine ebike #{{$record->id}}</li>
                </ul>

                @if(!$isAdmin)
                    <hr>
                    <h5 class="mb-3">Carica la ricevuta del bonifico</h5>
                    <form method="POST" action="{{action([\App\Http\Controllers\Backend\EbikeOrdineController::class,'caricaPagamento'],$record->id)}}" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="fw-bold fs-7">CRO bonifico</label>
                                <input type="text" name="cro_bonifico" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold fs-7">Data bonifico</label>
                                <input type="date" name="data_bonifico_dichiarata" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold fs-7">Ricevuta (PDF o immagine)</label>
                                <input type="file" name="ricevuta_bonifico" class="form-control" accept=".pdf,image/*" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Carica ricevuta</button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if($record->cro_bonifico || $record->ricevuta_bonifico)
        <div class="card mb-6">
            <div class="card-body">
                <h4 class="mb-3">Bonifico dichiarato</h4>
                <ul class="mb-3">
                    <li><strong>CRO:</strong> {{$record->cro_bonifico}}</li>
                    <li><strong>Data dichiarata:</strong> {{$record->data_bonifico_dichiarata?->format('d/m/Y')}}</li>
                </ul>
                @if($record->urlRicevutaBonifico())
                    <a href="{{$record->urlRicevutaBonifico()}}" target="_blank" class="btn btn-sm btn-light-primary">Vedi ricevuta</a>
                @endif

                @if($isAdmin && in_array($record->stato->value, ['in_attesa_pagamento','pagamento_da_verificare'], true))
                    <hr>
                    <form method="POST" action="{{action([\App\Http\Controllers\Backend\EbikeOrdineController::class,'confermaPagamento'],$record->id)}}">
                        @csrf
                        <button type="submit" class="btn btn-success">Conferma pagamento</button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if($isAdmin && $record->stato->value === 'pagamento_confermato')
        <div class="card mb-6">
            <div class="card-body">
                <h4 class="mb-3">Imposta spedizione</h4>
                <form method="POST" action="{{action([\App\Http\Controllers\Backend\EbikeOrdineController::class,'impostaTracking'],$record->id)}}">
                    @csrf
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="fw-bold fs-7">Corriere</label>
                            <input type="text" name="corriere" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold fs-7">Numero tracking</label>
                            <input type="text" name="tracking_number" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Segna come spedito</button>
                </form>
            </div>
        </div>
    @endif

    @if($record->stato->value === 'spedito')
        <div class="card mb-6">
            <div class="card-body">
                <h4 class="mb-3">Spedizione</h4>
                <ul class="mb-3">
                    <li><strong>Corriere:</strong> {{$record->corriere}}</li>
                    <li><strong>Tracking:</strong> {{$record->tracking_number}}</li>
                    <li><strong>Spedito il:</strong> {{$record->spedito_at?->format('d/m/Y H:i')}}</li>
                </ul>
                <form method="POST" action="{{action([\App\Http\Controllers\Backend\EbikeOrdineController::class,'segnaConsegnato'],$record->id)}}">
                    @csrf
                    <button type="submit" class="btn btn-light-success">Segna come consegnato</button>
                </form>
            </div>
        </div>
    @endif

    @if(!in_array($record->stato->value, ['spedito','consegnato','annullato'], true))
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 text-danger">Annulla ordine</h4>
                <form method="POST" action="{{action([\App\Http\Controllers\Backend\EbikeOrdineController::class,'annulla'],$record->id)}}">
                    @csrf
                    <div class="mb-4">
                        <label class="fw-bold fs-7">Motivo</label>
                        <input type="text" name="motivo" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-light-danger">Annulla ordine</button>
                </form>
            </div>
        </div>
    @endif
@endsection
