@extends('Backend._layout._main')
@section('toolbar')
    <a class="btn btn-danger btn-sm" id="elimina"
       href="{{action([$controller,'annulla'],$record->id)}}">Annulla spedizione</a>
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}">
                @csrf
                @method($record->id?'PATCH':'POST')

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'esito','testo'=>'Esito','valore'=> $record->esito() ])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'ragione_sociale_destinatario','testo'=>'Ragione sociale destinatario','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_destinatario','testo'=>'Indirizzo destinatario','autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_destinatario','testo'=>'Cap destinatario','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'localita_destinazione','testo'=>'Localita destinazione','autocomplete'=>'off'])
                    </div>

                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'nazione_destinazione','testo'=>'Nazione destinazione','selected'=>\App\Models\Nazione::find($record->nazione_destinazione)->langIT])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'mobile_referente_consegna','testo'=>'Mobile referente consegna','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'numero_pacchi','testo'=>'Numero pacchi','autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @if($record->response['createResponse']??false)
                            @foreach($record->response['createResponse']['labels']['label']??[] as $label)
                                <a class="btn btn-sm btn-primary"
                                   href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'etichetta'],['id'=>$record->id,$loop->index])}}">Etichetta {{$loop->index+1}}</a>
                            @endforeach
                        @endif
                        @if($record->nazione_destinazione=='CH')
                                <a class="btn btn-sm btn-primary"
                                   href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'pdf'],['id'=>$record->id,'proforma_ch'])}}">Proforma</a>
                            @endif
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'peso_totale','testo'=>'Peso totale','classe'=>'peso'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'volume_totale','testo'=>'Volume totale'])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'pudo_id','testo'=>'Pudo','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'nome_mittente','testo'=>'Nome mittente','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'email_mittente','testo'=>'Email mittente','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'mobile_mittente','testo'=>'Mobile mittente','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'contrassegno','testo'=>'Contrassegno','autocomplete'=>'off'])
                    </div>
                </div>
                <h4>Colli</h4>
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
                @foreach($record->dati_colli as $sede)
                    <div data-repeater-item="" class="item-collo">
                        <div class="form-group row mb-5">
                            <div class="col-md-2">
                                @php($selected=old('dati_colli.larghezza',$sede['larghezza']))
                                <span type="text"
                                      class="form-control  form-control-sm larghezza ricalcola intero"
                                      name="larghezza" value="">{{$selected}}</span>
                            </div>
                            <div class="col-md-2">
                                @php($selected=old('dati_colli.altezza',$sede['altezza']))
                                <span type="text"
                                      class="form-control  form-control-sm altezza intero ricalcola"
                                      name="altezza" value="{{$selected}}">{{$selected}}</span>
                            </div>
                            <div class="col-md-2">
                                @php($selected=old('dati_colli.profondita',$sede['profondita']))
                                <span type="text"
                                      class="form-control  form-control-sm profondita intero ricalcola"
                                      name="profondita" value="{{$selected}}">{{$selected}}</span>
                            </div>
                            <div class="col-md-2">
                                @php($selected=old('dati_colli.peso_reale',$sede['peso_reale']))
                                <span type="text"
                                      class="form-control  form-control-sm peso_reale ricalcola"
                                      name="peso_reale" value="{{$selected}}">{{$selected}}</span>
                            </div>
                            @php($selected=$sede['peso_volumetrico'])
                            <div class="col-md-2">
                                   <span type="text"
                                         class="form-control  form-control-sm peso_reale ricalcola"
                                         name="peso_reale" value="{{$selected}}">{{$selected}}</span>
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
                @can('admin')
                    @foreach($record->chiamate as $record)
                        <h4>{{$record->created_at->format('d/m/Y H:i:s')}}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputTextReadonly',['campo'=>'servizio','testo'=>'Servizio','required'=>true,'autocomplete'=>'off'])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputTextReadonly',['campo'=>'url','testo'=>'Url','required'=>true,'autocomplete'=>'off'])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputTextReadonly',['campo'=>'method','testo'=>'Method','required'=>true,'autocomplete'=>'off'])
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputTextReadonly',['campo'=>'status','testo'=>'Status','autocomplete'=>'off'])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                Request
                                <pre>
                                {!! print_r($record->request) !!}
                                </pre>
                            </div>
                            <div class="col-md-6">
                                Response
                                <pre>
                                {!! print_r($record->response) !!}
                                </pre>
                            </div>
                        </div>
                    @endforeach
                @endcan
            </form>
        </div>
    </div>
@endsection

@push('customScript')
    <script>
        $(function (){
            eliminaHandler('Questa spedizione verrà annullata');
        });
    </script>
@endpush
