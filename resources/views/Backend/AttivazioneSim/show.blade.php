@extends('Backend._layout._main')
@section('toolbar')
    @if($record->id)
        <div class="me-0">
            <a href="#" class="btn btn-sm btn-flex bg-body btn-color-gray-700 btn-active-color-primary fw-bold"
               data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"
               data-kt-menu-flip="top-end">Azioni
                <!--begin::Svg Icon | path: icons/duotone/Navigation/Angle-down.svg-->
                <span class="svg-icon svg-icon-5 m-0">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px"
                     height="24px" viewBox="0 0 24 24" version="1.1">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <polygon points="0 0 24 0 24 24 0 24"/>
                        <path
                                d="M6.70710678,15.7071068 C6.31658249,16.0976311 5.68341751,16.0976311 5.29289322,15.7071068 C4.90236893,15.3165825 4.90236893,14.6834175 5.29289322,14.2928932 L11.2928932,8.29289322 C11.6714722,7.91431428 12.2810586,7.90106866 12.6757246,8.26284586 L18.6757246,13.7628459 C19.0828436,14.1360383 19.1103465,14.7686056 18.7371541,15.1757246 C18.3639617,15.5828436 17.7313944,15.6103465 17.3242754,15.2371541 L12.0300757,10.3841378 L6.70710678,15.7071068 Z"
                                fill="#000000" fill-rule="nonzero"
                                transform="translate(12.000003, 11.999999) rotate(-180.000000) translate(-12.000003, -11.999999)"/>
                    </g>
                </svg>
            </span>
                <!--end::Svg Icon-->
            </a>
            <!--begin::Menu-->
            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-200px py-4"
                 data-kt-menu="true">
                <div class="menu-item px-3">
                    <a href="{{action([$controller,'edit'],$record->id)}}"
                       class="menu-link px-3">Modifica</a>
                    <a href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['sostituzione-sim',$record->id])}}"
                       class="menu-link px-3" data-target="kt_modal" data-toggle="modal-ajax">Sostituzione Sim</a>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}">
                @csrf
                @method($record->id?'PATCH':'POST')
                @php($uid=old('uid',$record->uid))
                <input type="hidden" name="uid" id="uid" value="{{$uid}}">
                <div class="row">
                    @if(Auth::user()->hasAnyPermission(['admin','operatore']))
                        <div class="col-md-6">
                            @include('Backend._inputs.inputTextReadonly',['campo'=>'agente_id','testo'=>'Agente','valore'=>$record->agente->nominativo()])
                        </div>
                        <div class="col-md-6">
                            @include('Backend._inputs.inputTextReadonly',['campo'=>'data','testo'=>'Data','valore'=>$record->data->format('d/m/Y')])
                        </div>
                        <div class="col-md-6">
                            @include('Backend._inputs.inputTextReadonly',['campo'=>'caricato_da_user_id','testo'=>'Caricato da','valore'=>$record->caricatoDa?->nominativo()])
                        </div>
                    @endif
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'gestore','testo'=>'Gestore','valore'=>$record->gestore->nome])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','classe'=>'uppercase'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'nome','testo'=>'Nome'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'cognome','testo'=>'Cognome'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'email','testo'=>'Email'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'cellulare','testo'=>'Cellulare'])
                    </div>
                </div>

                <h3 class="card-title">Indirizzo</h3>
                <div class="row">
                    <div class="col-md-12">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo','testo'=>'Indirizzo','col'=>2])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'citta','testo'=>'Citta','valore'=>$record->comune?->comuneConTarga()])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'cap','testo'=>'Cap'])
                    </div>
                </div>

                <h3 class="card-title">Sim</h3>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_sim','testo'=>'Seriale sim nuova'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'mnp','testo'=>'Seriale sim vecchia'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'numero_da_portare','testo'=>'Numero da portare'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'puk','testo'=>'Puk'])
                    </div>
                </div>
                @includeIf('Backend.AttivazioneSim.GestoriShow.'.$nomeGestore)

                <h3 class="card-title">Dati documento</h3>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_documento','testo'=>'Tipo documento','valore'=>$record->tipo_documento?\App\Models\ContrattoTelefonia::TIPI_DOCUMENTO[$record->tipo_documento]:'' ])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">@include('Backend._inputs.inputTextReadonly',['campo'=>'numero_documento','testo'=>'Numero Documento','required'=>false])</div>
                    <div class="col-md-6">@include('Backend._inputs.inputTextReadonly',['campo'=>'data_scadenza','testo'=>'Data scadenza','valore'=>$record->data_scadenza?->format('d/m/Y')])</div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'note','testo'=>'Note','col'=>2])
                    </div>
                </div>
            </form>
        </div>
    </div>
    <h4 class="mt-6">Allegati</h4>
    @include('Backend.ContrattoTelefonia.allegati',['downloadController' => \App\Http\Controllers\Backend\AttivazioneSimController::class,'idPadre'=>$record->id])

    @includeWhen($sostituzioni->count(),'Backend.AttivazioneSim.elencoSostituzioni',['records'=>$sostituzioni])
@endsection
@push('customScript')
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
        });
    </script>
@endpush
