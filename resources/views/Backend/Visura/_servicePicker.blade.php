@php
    $saldoVisure = (float)(Auth::user()->agente->portafoglio_visure ?? 0);
    $serviziVisura = $serviziVisura ?? \App\Models\TipoVisura::orderBy('nome')->get();
@endphp

<div class="row g-5">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="text-uppercase text-primary fw-bolder fs-8 mb-2">Visure</div>
                    <h2 class="mb-1">Scegli il servizio da creare</h2>
                    <div class="text-muted">Apri una nuova pratica solo se il portafoglio visure copre il costo del servizio.</div>
                </div>
                <div class="rounded bg-light-primary px-5 py-4 text-lg-end">
                    <div class="text-muted fw-bold fs-8 text-uppercase">Portafoglio visure</div>
                    <div class="fs-2 fw-bolder text-primary">{!! importo($saldoVisure) !!}</div>
                </div>
            </div>
        </div>
    </div>

    @foreach($serviziVisura as $servizio)
        @php
            $costo = (float)$servizio->prezzo_agente;
            $puoCreare = $saldoVisure >= $costo;
        @endphp
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column gap-4">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h3 class="fs-5 fw-bolder mb-2">{{$servizio->nome}}</h3>
                            <span class="badge {{$puoCreare ? 'badge-light-success' : 'badge-light-danger'}}">
                                {{$puoCreare ? 'Disponibile' : 'Credito insufficiente'}}
                            </span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="bg-light rounded p-3">
                                <div class="text-muted fs-8 fw-bold text-uppercase">Prezzo consigliato</div>
                                <div class="fw-bolder">{!! importo($servizio->prezzo_cliente) !!}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-3">
                                <div class="text-muted fs-8 fw-bold text-uppercase">Costo</div>
                                <div class="fw-bolder">{!! importo($servizio->prezzo_agente) !!}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto">
                        @if($puoCreare)
                            <a href="{{action([\App\Http\Controllers\Backend\VisuraController::class,'create'], $servizio->id)}}"
                               class="btn btn-primary w-100">Crea pratica</a>
                        @else
                            <a href="{{action([\App\Http\Controllers\Backend\RicaricaPlafonController::class,'show'], ['tab_portafoglio' => \App\Enums\TipiPortafoglioEnum::VISURE->value])}}"
                               class="btn btn-light-danger w-100">Ricarica portafoglio</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
