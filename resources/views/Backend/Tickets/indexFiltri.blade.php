<form action="{{action([$controller,'index'])}}" method="GET">
    <div class="px-7 py-5">
        <div class="mb-3">
            <label class="form-label fw-bold">Stato:</label>
            <div>
                @php($selected=request()->input('stato_contratto'))
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
        <div class="d-flex justify-content-end">
            @if($conFiltro)
                <a href="{{action([$controller,'index'])}}" class="btn btn-sm btn-success me-2">Vedi tutti</a>
            @endif
            <button type="submit" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true" name="filtra">Filtra</button>
        </div>
    </div>
</form>
