<form action="{{action([$controller,'index'])}}" method="GET">
    <div class="px-7 py-5">
        <div class="mb-3">
            <label class="form-label fw-bold">Data ordini:</label>
            <div data-bs-toggle="tooltip" data-bs-placement="top"
                 data-bs-original-title="Impostando solo il mese verrà considerano l'anno in corso">
                <div class="d-flex justify-content-between w-100">
                    @php
                        $selected = request()->input('giorno');
                    @endphp
                    <input type="text" class="form-control form-control-sm form-control-solid w-60px me-2" placeholder="Giorno" name="giorno"
                           value="{{is_numeric($selected)?$selected:''}}">
                    @php
                        $selected = request()->input('mese');
                    @endphp
                    <select class="form-select form-select-solid form-select-sm" data-kt-select2="true" data-placeholder="Mese" data-allow-clear="true" name="mese"
                            data-minimum-results-for-search="Infinity">
                        <option></option>
                        @for($m=1;$m<=12;$m++)
                            <option value="{{$m}}" {{$selected==$m?'selected':''}}>{{\App\mese($m)}}</option>
                        @endfor
                    </select>
                    @php
                        $selected = request()->input('anno');
                    @endphp
                    <select class="form-select form-select-solid form-select-sm ms-2" data-kt-select2="true" data-placeholder="Anno" data-allow-clear="true" name="anno"
                            data-minimum-results-for-search="Infinity">
                        <option></option>
                        @for($a=config('configurazione.primoAnno');$a<=\Carbon\Carbon::now()->year;$a++)
                            <option value="{{$a}}" {{$selected==$a?'selected':''}}>{{$a}}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Esiti</label>
            @php
                $selectedEsiti = (array)request()->input('esiti', []);
            @endphp
            <select class="form-select form-select-solid form-select-sm" data-kt-select2="true" name="esiti[]" multiple>
                @foreach(\App\Models\EsitoCafPatronato::orderBy('nome')->get(['id','nome']) as $esito)
                    <option value="{{$esito->id}}" {{in_array($esito->id, $selectedEsiti) ? 'selected' : ''}}>{{$esito->nome}}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Tipo pratica</label>
            @php
                $selectedTipo = request()->input('tipo_caf_patronato_id');
            @endphp
            <select class="form-select form-select-solid form-select-sm" data-kt-select2="true" data-placeholder="Tutti" data-allow-clear="true" name="tipo_caf_patronato_id">
                <option></option>
                @foreach(\App\Models\TipoCafPatronato::orderBy('nome')->get(['id','nome']) as $tipo)
                    <option value="{{$tipo->id}}" {{$selectedTipo==$tipo->id ? 'selected' : ''}}>{{$tipo->nome}}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Agente</label>
            @php
                $selectedAgente = request()->input('agente_id');
                $agentiIds = \App\Models\CafPatronato::query()->whereNotNull('agente_id')->distinct()->pluck('agente_id');
                $agenti = \App\Models\User::query()->whereIn('id', $agentiIds)->orderBy('nome')->orderBy('cognome')->get(['id', 'nome', 'cognome']);
            @endphp
            <select class="form-select form-select-solid form-select-sm" data-kt-select2="true" data-placeholder="Tutti" data-allow-clear="true" name="agente_id">
                <option></option>
                @foreach($agenti as $agente)
                    <option value="{{$agente->id}}" {{$selectedAgente==$agente->id ? 'selected' : ''}}>{{$agente->nominativo()}}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <div class="form-check form-switch form-check-custom form-check-solid">
                <input class="form-check-input" type="checkbox" value="1" id="solo_fermi" name="solo_fermi" {{request()->boolean('solo_fermi') ? 'checked' : ''}}>
                <label class="form-check-label" for="solo_fermi">Solo pratiche ferme</label>
            </div>
            <div class="text-muted fs-8 mt-1">Bozza / Da gestire oltre soglia giorni</div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Giorni fermo</label>
            <input type="number" min="1" class="form-control form-control-sm form-control-solid" name="giorni_fermo" value="{{max(1, (int)request()->input('giorni_fermo', 7))}}">
        </div>

        <div class="d-flex justify-content-between">
            <div>
                @if($conFiltro)
                    <a href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}" class="btn btn-sm btn-light" data-kt-menu-dismiss="true">Vedi tutto</a>
                @endif
            </div>
            <button type="submit" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true" name="filtra">Filtra</button>
        </div>
    </div>
</form>
