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
                    <a href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'edit'],$record->id)}}"
                       class="menu-link px-3">Modifica</a>
                </div>
                @if($puoCreare)
                    <div class="menu-item px-3">
                        <a href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'create'],['duplica'=>$record->id])}}"
                           class="menu-link px-3">Duplica</a>
                    </div>
                @endif
                @if($record->prodotto)
                    <div class="menu-item px-3">
                        <a href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'pda'],$record->id)}}"
                           class="menu-link px-3">PDA</a>
                    </div>
                @endif
                @if($record->natura_giuridica)
                    <div class="menu-item px-3">
                        <a href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['richiedi_visura',$record->id])}}"
                           data-target="kt_modal" data-toggle="modal-ajax"
                           class="menu-link px-3">Richiedi visura</a>
                    </div>
                @endif
                @if( Auth::id()==2 && $record->agente_id!=2)
                    <div class="menu-item px-3">
                        <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'create'],['contratto_id'=>$record->id])}}"
                           data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                           class="menu-link px-3">Nuovo ticket</a>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card card-flush">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            @if($record->sollecito_gestore && $record->esito_id!=='attivo')
                @include('Backend._components.notice',['level' => 'success','titolo'=>'Sollecito','testo' => 'Sollecito al gestore inviato il '.$record->sollecito_gestore->format('d/m/Y H:i')])
            @endif
            <div class="mb-5">
                <h4 class="fw-bold mb-1">Dati pratica</h4>
                <div class="text-muted fs-7">Informazioni generali e assegnazione</div>
            </div>
            <div class="row">
                @if(Auth::user()->hasAnyPermission(['admin','operatore']))
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'valore'=>$record->agente->nominativo()])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'data','testo'=>'Data','valore'=>$record->data->format('d/m/Y')])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'caricato_da_user_id','testo'=>'Caricato da','valore'=>$record->caricatoDa?->nominativo()])
                    </div>
                @endif
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_contratto_id','testo'=>'Tipo contratto','required'=>true,'valore'=>$record->tipoContratto->nome])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_cliente','testo'=>'Codice cliente','required'=>false,'autocomplete'=>'off'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_contratto','testo'=>'Codice contratto','required'=>false,'autocomplete'=>'off'])
                </div>
            </div>

            <div class="separator separator-dashed my-6"></div>
            <div class="mb-5">
                <h4 class="fw-bold mb-1">Dati cliente</h4>
                <div class="text-muted fs-7">Anagrafica, contatti e pagamento</div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','required'=>true,'autocomplete'=>'off','classe'=>'uppercase'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'natura_giuridica','testo'=>'Natura giuridica'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'nome','testo'=>'Nome','required'=>true,'autocomplete'=>'off'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'cognome','testo'=>'Cognome','required'=>true,'autocomplete'=>'off'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'ragione_sociale','testo'=>'Ragione sociale','required'=>false,'autocomplete'=>'off'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'partita_iva','testo'=>'Partita iva','required'=>false,'autocomplete'=>'off'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'iban','testo'=>'Iban'])
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-4 text-end">

                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputTextReadonly',['campo'=>'carta_di_credito','testo'=>'Carta di credito','mask'=>'9999 9999 9999 9999','placeholder'=>'Numero carta'])
                                </div>
                                <div class="col-md-3">
                                    @include('Backend._inputs.inputTextReadonly',['campo'=>'carta_di_credito_scadenza','testo'=>'Scadenza','mask'=>'99/99','placeholder'=>'Data scadenza'])
                                </div>
                                <div class="col-md-3">
                                    @include('Backend._inputs.inputTextReadonly',['campo'=>'carta_di_credito_cvc','testo'=>'Cvc','mask'=>'999','placeholder'=>'CVC'])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'email','testo'=>'Email','autocomplete'=>'off','required'=>true])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'telefono','testo'=>'Telefono','required'=>true,'autocomplete'=>'off'])
                </div>
            </div>

            <div class="separator separator-dashed my-6"></div>
            <div class="mb-5">
                <h4 class="fw-bold mb-1">Indirizzo</h4>
                <div class="text-muted fs-7">Recapito e ubicazione cliente</div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo','testo'=>'Indirizzo','autocomplete'=>'off','col'=>2])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'citta','testo'=>'Citta','valore'=>$record->comune?->comuneConTarga()])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'cap','testo'=>'Cap','autocomplete'=>'off'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'nome_citofono','testo'=>'Nome citofono','autocomplete'=>'off'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'scala','testo'=>'Scala','autocomplete'=>'off'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'piano','testo'=>'Piano','autocomplete'=>'off'])
                </div>
            </div>

            <div class="separator separator-dashed my-6"></div>
            <div class="mb-5">
                <h4 class="fw-bold mb-1">Dati documento</h4>
                <div class="text-muted fs-7">Riferimenti documento identificativo</div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_documento','testo'=>'Tipo documento','valore'=>$record->tipo_documento?\App\Models\ContrattoTelefonia::TIPI_DOCUMENTO[$record->tipo_documento]:'' ])
                </div>

            </div>

            <div class="row">
                <div class="col-md-6">@include('Backend._inputs.inputTextReadonly',['campo'=>'numero_documento','testo'=>'Numero Documento','required'=>false,'autocomplete'=>'off'])</div>
                <div class="col-md-6">@include('Backend._inputs.inputTextReadonly',['campo'=>'rilasciato_da','testo'=>'Rilasciato da','required'=>false,'array'=>['COMUNE'=>'COMUNE','MIT UCO'=>'MIT UCO', 'MC'=>'MC', 'MI'=>'MI']])</div>
            </div>
            <div class="row">
                <div class="col-md-6">@include('Backend._inputs.inputTextReadonly',['campo'=>'data_rilascio','testo'=>'Data rilascio','valore'=>$record->data_rilascio?->format('d/m/Y')])</div>
                <div class="col-md-6">@include('Backend._inputs.inputTextReadonly',['campo'=>'data_scadenza','testo'=>'Data scadenza','valore'=>$record->data_scadenza?->format('d/m/Y')])</div>
            </div>
            <div class="row">
                <div class="col-md-6">@include('Backend._inputs.inputTextReadonly',['campo'=>'cittadinanza','testo'=>'Cittadinanza','valore'=>\App\Models\Nazione::selected(old('cittadinanza',$record->cittadinanza))])</div>
            </div>
            @if($record->prodotto)
                <div class="separator separator-dashed my-6"></div>
                @include('Backend.ContrattoTelefonia.Prodotti.'.$record->tipoContratto->prodotto.'Show',['record'=>$record->prodotto])
            @endif
            <div class="row">
                <div class="col-md-12">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'note','testo'=>'Note','col'=>2])
                </div>
                <div class="col-md-12">
                </div>

            </div>
            <div class="separator separator-dashed my-6"></div>
            <div class="mb-5">
                <h4 class="fw-bold mb-1">Altri dati</h4>
                <div class="text-muted fs-7">Metadati e reminder</div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'created_at','testo'=>'Creato il','valore'=>$record->created_at->format('d/m/Y H:i')])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'sollecito_gestore','testo'=>'Sollecito gestore','valore'=>$record->sollecito_gestore?->format('d/m/Y H:i')])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'data_reminder','testo'=>'Data reminder','valore'=>$record->data_reminder?->format('d/m/Y')])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'reminder_inviato','testo'=>'Reminder inviato il','valore'=>$record->reminder_inviato?->format('d/m/Y')])
                </div>
            </div>

        </div>
    </div>
    <div class="card card-flush mt-6">
        <div class="card-header border-0 pt-5 pb-2">
            <h3 class="card-title">Allegati</h3>
        </div>
        <div class="card-body pt-0">
            @include('Backend.ContrattoTelefonia.allegati',['downloadController' => \App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'idPadre'=>$record->id])
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
