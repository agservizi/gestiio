<form action="{{action([$controller,'index'])}}" method="GET">
    <div class="px-7 py-5">
        <div class="mb-3">
            <label class="form-label fw-bold">Data ordini:</label>
            <div data-bs-toggle="tooltip" data-bs-placement="top"
                 data-bs-original-title="Impostando solo il mese verrà considerano l'anno in corso">
                <div class="d-flex justify-content-between w-100">
                    @php($selected=request()->input('giorno'))
                    <input type="text" class="form-control form-control-sm form-control-solid w-60px me-2" placeholder="Giorno" name="giorno"
                           value="{{is_numeric($selected)?$selected:''}}">
                    @php($selected=request()->input('mese'))
                    <select class="form-select form-select-solid form-select-sm" data-kt-select2="true" data-placeholder="Mese" data-allow-clear="true" name="mese"
                            data-minimum-results-for-search="Infinity">
                        <option></option>
                        @for($m=1;$m<=12;$m++)
                            <option value="{{$m}}" {{$selected==$m?'selected':''}}>{{\App\mese($m)}}</option>
                        @endfor
                    </select>
                    @php($selected=request()->input('anno'))
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
