@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    @php($tabAttivo = $tabPortafoglio ?? \App\Enums\TipiPortafoglioEnum::SERVIZI->value)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertMessage')
            @include('Backend._components.alertErrori')
            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6">
                @foreach(($walletTabs ?? []) as $walletTab)
                    @php($walletValue = $walletTab->value)
                    <li class="nav-item">
                        <a class="nav-link {{$tabAttivo === $walletValue ? 'active' : ''}}"
                           href="{{action([\App\Http\Controllers\Backend\RicaricaPlafonController::class,'show'], array_merge(request()->query(), ['tab_portafoglio' => $walletValue]))}}">
                            {{$walletTab->testo()}}
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="row">
                <div class="col-md-6 pt-sm-8 pt-md-0">
                    <h4>Ricarica agente - {{\App\Enums\TipiPortafoglioEnum::tryFrom($tabAttivo)?->testo()}}</h4>
                    @if(!empty($agenteSelezionato) && !empty($agenteSelezionato->agente))
                        @php($saldoServizi = (float)($agenteSelezionato->agente->portafoglio_servizi ?? 0))
                        @php($saldoSpedizioni = (float)($agenteSelezionato->agente->portafoglio_spedizioni ?? 0))
                        <div class="d-flex flex-wrap gap-3 mt-3 mb-3">
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4">
                                <div class="fs-6 text-gray-500">Saldo servizi</div>
                                <div class="fs-4 fw-bold">€ {{number_format($saldoServizi, 2, ',', '.')}}</div>
                            </div>
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4">
                                <div class="fs-6 text-gray-500">Saldo spedizioni</div>
                                <div class="fs-4 fw-bold">€ {{number_format($saldoSpedizioni, 2, ',', '.')}}</div>
                            </div>
                        </div>
                    @endif
                    <form method="POST"
                          action="{{action([\App\Http\Controllers\Backend\RicaricaPlafonController::class,'store'])}}"
                          class="card-form mt-3 mb-3">
                        @csrf
                        <input type="hidden" name="portafoglio" value="{{$tabAttivo}}">
                        <div class="row">
                            <div class="col-md-12">
                                @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                @include('Backend._inputs.inputText',['campo'=>'importo','testo'=>'Importo','required'=>true,"classe"=>"autonumericImporto"])
                            </div>
                        </div>

                        <div class="w-100 text-center">
                            <button class="btn btn-primary mt-3" type="submit" id="submit">Carica</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 pt-sm-8 pt-md-0">
                    <h4>Ultime ricariche - {{\App\Enums\TipiPortafoglioEnum::tryFrom($tabAttivo)?->testo()}}</h4>
                    <form method="GET" action="{{action([\App\Http\Controllers\Backend\RicaricaPlafonController::class,'show'])}}" class="mt-3">
                        <input type="hidden" name="tab_portafoglio" value="{{$tabAttivo}}">
                        <div class="row align-items-end">
                            <div class="col-md-8">
                                @include('Backend._inputs.inputSelect2',['campo'=>'filtro_agente_id','testo'=>'Filtra agente','required'=>false,'selected'=>\App\Models\User::selected(old('filtro_agente_id', $filtroAgenteId ?? null))])
                            </div>
                            <div class="col-md-4 d-flex gap-2 mb-4">
                                <button class="btn btn-primary" type="submit">Filtra</button>
                                <a href="{{action([\App\Http\Controllers\Backend\RicaricaPlafonController::class,'show'], ['tab_portafoglio' => $tabAttivo])}}" class="btn btn-light">Reset</a>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-row-dashed align-middle">
                            <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th>Data</th>
                                <th>Agente</th>
                                <th>Portafoglio</th>
                                <th class="text-end">Importo</th>
                                <th>Tipo ricarica</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse(($ultimeRicariche ?? collect()) as $movimento)
                                <tr>
                                    <td class="fs-8">{{$movimento->created_at?->format('d/m/Y H:i')}}</td>
                                    <td class="fs-8">{{$movimento->agente_nominativo}}</td>
                                    <td class="fs-8">{{$movimento->portafoglio}}</td>
                                    <td class="fs-8 text-end">€ {{number_format((float)$movimento->importo, 2, ',', '.')}}</td>
                                    <td class="fs-8">{{$movimento->tipo_ricarica}}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted fs-8">Nessuna ricarica recente trovata.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{$ultimeRicariche->appends(request()->query())->links()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-5">
        <div class="col-md-7 col-lg-8">
        </div>
    </div>
@endsection



@push('customCss')
@endpush
@push('customScript')
    <script>
        $(function () {
            select2UniversaleBackend('agente_id', 'un agente', 1, 'agente_id');
            select2UniversaleBackend('filtro_agente_id', 'un agente', 1, 'filtro_agente_id');
        });
    </script>
@endpush
