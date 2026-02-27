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
                    <a href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'edit'],$record->id)}}"
                       class="menu-link px-3">Modifica</a>
                </div>
                @if($puoCreare || $record->prodotto || Auth::user()->hasAnyPermission(['admin','supervisore']))
                    @if($puoCreare)
                        <div class="menu-item px-3">
                                     <a href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'create'],['duplica'=>$record->id])}}"
                               class="menu-link px-3">Duplica</a>
                        </div>
                    @endif
                    @if($record->prodotto)
                        <div class="menu-item px-3">
                                     <a href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'pda'],$record->id)}}"
                               class="menu-link px-3">PDA</a>
                        </div>
                    @endif
                    @if(Auth::user()->hasAnyPermission(['admin','supervisore']))
                        <div class="menu-item px-3">
                            <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'create'],['servizio_id'=>$record->id,'servizio_type'=>'contratto-energia'])}}"
                               data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                               class="menu-link px-3">Nuovo ticket</a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif
@endsection

@section('content')
    @php
        $vecchio = $record->id;
    @endphp
    <div class="card card-flush">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}">
                @csrf
                @method($record->id?'PATCH':'POST')
                @php
                    $uid = old('uid', $record->uid);
                @endphp
                <input type="hidden" name="uid" id="uid" value="{{$uid}}">
                <div class="mb-5">
                    <h4 class="fw-bold mb-1">Dati pratica</h4>
                    <div class="text-muted fs-7">Informazioni generali e assegnazione</div>
                </div>
                <div class="row">
                    @if(Auth::user()->hasAnyPermission(['admin','operatore','supervisore']))
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
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_contratto_id','testo'=>'Tipo contratto','valore'=>$record->gestore->nome])
                    </div>
                </div>

                <div class="separator separator-dashed my-6"></div>
                <div class="mb-5">
                    <h4 class="fw-bold mb-1">Dati cliente</h4>
                    <div class="text-muted fs-7">Anagrafica essenziale per il contratto</div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','autocomplete'=>'off','classe'=>'uppercase'])
                    </div>
                </div>

                @if($record->prodotto)
                    <div class="separator separator-dashed my-6"></div>
                    <div class="mb-5">
                        <h4 class="fw-bold mb-1">Dettagli fornitura</h4>
                        <div class="text-muted fs-7">Campi specifici del prodotto energia</div>
                    </div>
                    @include("Backend.ContrattoEnergia.Prodotti.{$record->gestore->model_prodotto}Show",['record'=>$record->prodotto,'contratto'=>$record])
                @endif
                <div class="separator separator-dashed my-6"></div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'note','testo'=>'Note','col'=>2])
                    </div>
                    <div class="col-md-6">
                        @include('Backend.ContrattoEnergia.allegatiTabella', [
                            'downloadController' => \App\Http\Controllers\Backend\ContrattoEnergiaController::class,
                            'idPadre' => $record->id,
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
        });
    </script>
@endpush
