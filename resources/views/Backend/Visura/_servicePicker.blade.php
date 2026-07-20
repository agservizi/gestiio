@php
    $saldoVisure = (float)(Auth::user()->agente->portafoglio_visure ?? 0);
    $serviziVisura = $serviziVisura ?? \App\Models\TipoVisura::query()->where('abilitato', 1)->orderBy('nome')->get();
@endphp

<div class="row g-5">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="text-uppercase text-primary fw-bolder fs-8 mb-2">Visure</div>
                    <h2 class="mb-1">Scegli il servizio da creare</h2>
                    <div class="text-muted">Catalogo Gestiio allineabile a Visengine. L’addebito usa sempre il listino Gestiio (portafoglio visure).</div>
                </div>
                <div class="rounded bg-light-primary px-5 py-4 text-lg-end">
                    <div class="text-muted fw-bold fs-8 text-uppercase">Portafoglio visure</div>
                    <div class="fs-2 fw-bolder text-primary">{!! importo($saldoVisure) !!}</div>
                </div>
            </div>
        </div>
    </div>

    @if($serviziVisura->isEmpty())
        <div class="col-12">
            <div class="alert alert-warning mb-0">
                Nessun tipo visura abilitato. Importa il catalogo Visengine con
                <code>php artisan visure:sync-openapi-hash --import-missing</code>
                oppure abilita i tipi in Impostazioni → Tipi visura.
            </div>
        </div>
    @endif

    @foreach($serviziVisura as $servizio)
        @php
            $costo = (float)$servizio->prezzo_agente;
            $puoCreare = $saldoVisure >= $costo;
            $hasOpenApi = filled($servizio->openapi_hash_visura);
        @endphp
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column gap-4">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h3 class="fs-5 fw-bolder mb-2">{{$servizio->nome}}</h3>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge {{$puoCreare ? 'badge-light-success' : 'badge-light-danger'}}">
                                    {{$puoCreare ? 'Disponibile' : 'Credito insufficiente'}}
                                </span>
                                @if($hasOpenApi)
                                    <span class="badge badge-light-primary">Visengine</span>
                                @else
                                    <span class="badge badge-light-info">Backoffice</span>
                                @endif
                            </div>
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
