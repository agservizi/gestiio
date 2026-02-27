<form action="{{action([$controller,'index'])}}" method="GET" class="js-ticket-filter-form">
    <div class="px-7 py-5">
        <div class="mb-3">
            <label class="form-label fw-bold">Ricerca:</label>
            <input type="text" class="form-control form-control-solid form-control-sm" name="q" value="{{request()->input('q')}}" placeholder="Oggetto, uid, utente">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Stato:</label>
            <div>
                @php($selected=request()->input('stato'))
                <select class="form-select form-select-solid form-select-sm" data-dropdown-parent="#filtri-drop" data-hide-search="true" data-control="select2"
                        name="stato" id="stato"
                >
                    <option value="">Seleziona</option>
                    @foreach(\App\Models\Ticket::STATI_TICKETS as $k=>$v)
                        <option value="{{$k}}" {{$selected==$k?'selected':''}}>{{$v['testo']}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if(Auth::user()?->hasAnyPermission(['admin','supervisore']))
            <div class="mb-3">
                <label class="form-label fw-bold">Assegnato a:</label>
                <div>
                    @php($selectedAssegnatario=request()->input('assegnatario_id'))
                    <select class="form-select form-select-solid form-select-sm" data-dropdown-parent="#filtri-drop" data-hide-search="true" data-control="select2"
                            name="assegnatario_id" id="assegnatario_id"
                    >
                        <option value="">Seleziona</option>
                        <option value="__non_assegnato" {{$selectedAssegnatario==='__non_assegnato'?'selected':''}}>Non assegnato</option>
                        @foreach($assegnatariFiltro as $assegnatario)
                            <option value="{{$assegnatario->id}}" {{$selectedAssegnatario==(string)$assegnatario->id?'selected':''}}>
                                {{$assegnatario->cognome}} {{$assegnatario->nome}}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
        <div class="mb-3">
            <label class="form-label fw-bold">Priorità:</label>
            @php($selectedPriorita=request()->input('priorita'))
            <select class="form-select form-select-solid form-select-sm" data-dropdown-parent="#filtri-drop" data-hide-search="true" data-control="select2"
                    name="priorita" id="priorita">
                <option value="">Seleziona</option>
                @foreach(\App\Models\Ticket::PRIORITA_TICKETS as $k=>$v)
                    <option value="{{$k}}" {{$selectedPriorita===$k?'selected':''}}>{{$v['testo']}}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">SLA:</label>
            @php($selectedSla=request()->input('sla'))
            <select class="form-select form-select-solid form-select-sm" data-dropdown-parent="#filtri-drop" data-hide-search="true" data-control="select2"
                    name="sla" id="sla">
                <option value="">Seleziona</option>
                <option value="in_scadenza" {{$selectedSla==='in_scadenza'?'selected':''}}>In scadenza</option>
                <option value="violato" {{$selectedSla==='violato'?'selected':''}}>Violato</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Team:</label>
            @php($selectedTeam=request()->input('owner_team'))
            <select class="form-select form-select-solid form-select-sm" data-dropdown-parent="#filtri-drop" data-hide-search="true" data-control="select2"
                    name="owner_team" id="owner_team">
                <option value="">Seleziona</option>
                @foreach(\App\Models\Ticket::TEAM_TICKETS as $k=>$v)
                    <option value="{{$k}}" {{$selectedTeam===$k?'selected':''}}>{{$v}}</option>
                @endforeach
            </select>
        </div>
        <div class="d-flex justify-content-end gap-2">
            @if($conFiltro)
                <a href="{{action([$controller,'index'])}}" class="btn btn-sm btn-light-success">Vedi tutti</a>
            @endif
            <button type="submit" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true" name="filtra">Filtra</button>
        </div>
    </div>
</form>
