<form action="{{action([$controller,'index'])}}" method="GET">
    <div class="px-7 py-5">
        <div class="mb-3">
            <label class="form-label fw-bold">Data ordini:</label>
            <div data-bs-toggle="tooltip" data-bs-placement="top"
                 title="Impostando solo il mese verrà considerano l'anno in corso">
                <div class="d-flex justify-content-between w-100">
                    @php($selected=request()->input('mese'))
                    <select class="form-select form-select-solid form-select-sm" data-kt-select2="true"
                            data-placeholder="Mese" data-allow-clear="true" name="mese"
                            data-minimum-results-for-search="Infinity">
                        <option></option>
                        @for($m=1;$m<=12;$m++)
                            <option value="{{$m}}" {{$selected==$m?'selected':''}}>{{\App\mese($m)}}</option>
                        @endfor
                    </select>
                    @php($selected=request()->input('anno'))
                    <select class="form-select form-select-solid form-select-sm ms-2" data-kt-select2="true"
                            data-placeholder="Anno" data-allow-clear="true" name="anno"
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
            <label class="form-label fw-bold">Esiti:</label>
            <div class="row">
                <div class="col-12">
                    @php($selected=request()->input('esiti',[]))
                    @foreach(\App\Models\EsitoTelefonia::get() as $esito)
                        <div class="form-check form-check-custom form-check-solid form-check-sm mb-4 me-2"
                             style="display: inline-block;">
                            <input class="form-check-input" type="checkbox" value="{{$esito->id}}" name="esiti[]"
                                   id="esiti-{{$esito->id}}" {{in_array($esito->id,$selected)?'checked':''}}/>
                            <label class="form-check-label" for="esiti-{{$esito->id}}">
                                {!! $esito->labelStato() !!}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Agente:</label>
            <div>
                @php($selected=request()->input('agente_id'))
                <select class="form-select form-select-solid form-select-sm" data-dropdown-parent="#filtri-drop"
                        name="agente_id" id="agente"
                >
                    @if($selected)
                        {!! \App\Models\User::selected($selected) !!}
                    @endif
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Gestore:</label>
            <div>
                @php($selected=request()->input('gestore_id'))
                <select class="form-select form-select-solid form-select-sm" data-dropdown-parent="#filtri-drop"
                        name="gestore_id" id="gestore"
                >
                    @if($selected)
                        {!! \App\Models\Gestore::selected($selected) !!}
                    @endif
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Pratiche ferme:</label>
            <div class="d-flex align-items-center">
                <div class="form-check form-check-custom form-check-solid form-check-sm me-3">
                    <input class="form-check-input" type="checkbox" value="1" name="solo_fermi" id="solo_fermi" {{request()->boolean('solo_fermi') ? 'checked' : ''}}/>
                    <label class="form-check-label" for="solo_fermi">Solo fermi</label>
                </div>
                <input type="number" min="1" step="1" class="form-control form-control-sm form-control-solid w-90px" name="giorni_fermo" value="{{ (int)request()->input('giorni_fermo', 7) }}" title="Giorni minimi"/>
            </div>
            <div class="text-muted fs-8 mt-1">Stato bozza o da gestire.</div>
        </div>
        <div class="d-flex justify-content-between">
            <div>
                @if($conFiltro)
                    <a href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}"
                       class="btn btn-sm btn-light" data-kt-menu-dismiss="true">Vedi tutto</a>
                @endif
            </div>
            <button type="submit" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true" name="filtra">Filtra
            </button>
        </div>
    </div>
</form>
