<div class="card rounded border border-gray-500 mb-6">
    <div class="card-header bg-documentazione">
        <h3 class="card-title">Colli</h3>
        <div class="card-toolbar">

        </div>
    </div>
    <div class="card-body">
        <div id="dati_colli">
            <!--begin::Form group-->
            <div class="form-group">
                <div data-repeater-list="dati_colli">
                    <div class="row">
                        <div class="col-md-2">
                            <label class="form-label">Altezza</label>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Larghezza</label>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Profondità</label>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Peso</label>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Peso volumetrico</label>
                        </div>
                    </div>
                    @foreach(old('dati_colli',$record->dati_colli??[['larghezza'=>'','larghezza'=>'','profondita'=>'','altezza'=>'','peso_reale'=>'']]) as $sede)
                        <div data-repeater-item="" class="item-collo">
                            <div class="form-group row mb-5">
                                <div class="col-md-2">
                                    @php($selected=old('dati_colli.larghezza',$sede['larghezza']))
                                    <input type="text"
                                           class="form-control form-control-solid form-control-sm larghezza ricalcola intero"
                                           name="larghezza" value="{{$selected}}">
                                </div>
                                <div class="col-md-2">
                                    @php($selected=old('dati_colli.altezza',$sede['altezza']))
                                    <input type="text"
                                           class="form-control form-control-solid form-control-sm altezza intero ricalcola"
                                           name="altezza" value="{{$selected}}">
                                </div>
                                <div class="col-md-2">
                                    @php($selected=old('dati_colli.profondita',$sede['profondita']))
                                    <input type="text"
                                           class="form-control form-control-solid form-control-sm profondita intero ricalcola"
                                           name="profondita" value="{{$selected}}">
                                </div>
                                <div class="col-md-2">
                                    @php($selected=old('dati_colli.peso_reale',$sede['peso_reale']))
                                    <input type="text"
                                           class="form-control form-control-solid form-control-sm peso_reale ricalcola"
                                           name="peso_reale" value="{{$selected}}">
                                </div>
                                <div class="col-md-2">
                                    <span type="text" class="form-control form-control-sm peso-vol"
                                    ></span>
                                    <input type="hidden" name="peso_volumetrico" class="peso_volumetrico">
                                </div>
                                <div class="col-md-2">
                                    <a href="javascript:;" data-repeater-delete=""
                                       class="btn btn-sm btn-icon btn-light-danger">
                                        <i class="la la-trash-o fs-3"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <!--end::Form group-->
            <!--begin::Form group-->
            <div class="form-group">
                <a href="javascript:;" data-repeater-create="" class="btn btn-sm btn-light-primary">
                    <i class="la la-plus"></i>Aggiungi collo</a>
            </div>
            <div id="avviso-multicollo" style="display: none;" class="pt-2 fw-bold">
                FINO A 5 COLLI NON PALLETTIZZATI
            </div>
            <!--end::Form group-->
        </div>
    </div>
</div>
<!--end::Repeater-->
