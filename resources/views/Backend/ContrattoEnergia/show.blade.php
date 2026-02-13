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
                @if(false)
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
                    @if( Auth::id()==2 && $record->agente_id!=2)
                        <div class="menu-item px-3">
                            <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'create'],['contratto_id'=>$record->id])}}"
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
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_contratto_id','testo'=>'Tipo contratto','valore'=>$record->gestore->nome])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','autocomplete'=>'off','classe'=>'uppercase'])
                    </div>
                </div>

                @if($record->prodotto)
                    @include("Backend.ContrattoEnergia.Prodotti.{$record->gestore->model_prodotto}Show",['record'=>$record->prodotto,'contratto'=>$record])
                @endif
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'note','testo'=>'Note','col'=>2])
                    </div>
                    <div class="col-md-6">
                        <h4>Allegati</h4>
                        <div class="table-responsive">
                            <table id="kt_file_manager_list" class="table align-middle table-row-dashed fs-6 gy-5">
                                <!--begin::Table head-->
                                <thead>
                                <!--begin::Table row-->
                                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-250px">Nome</th>
                                    <th class="min-w-10px text-end">Dimensione</th>
                                    <th class="min-w-125px">Data caricamento</th>
                                </tr>
                                <!--end::Table row-->
                                </thead>
                                <!--end::Table head-->
                                <!--begin::Table body-->
                                <tbody class="fw-semibold text-gray-600">
                                @foreach($record->allegati as $allegato)
                                    <tr>
                                        <!--begin::Name=-->
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @switch($allegato->tipo_file)
                                                    @case('pdf')
                                                        <!--begin::Svg Icon | path: icons/duotune/files/fil003.svg-->
                                                        <span class="svg-icon svg-icon-2x svg-icon-primary me-4">
																	<svg width="24" height="24" viewBox="0 0 24 24"
                                                                         fill="none" xmlns="http://www.w3.org/2000/svg">
																		<path opacity="0.3"
                                                                              d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                                                              fill="currentColor"/>
																		<path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z"
                                                                              fill="currentColor"/>
																	</svg>
																</span>
                                                        <!--end::Svg Icon-->
                                                        @break


                                                    @case('immagine')
                                                        <!--begin::Svg Icon | path: icons/duotune/general/gen006.svg-->
                                                        <span class="svg-icon svg-icon-2x svg-icon-primary me-4">
																	<svg width="24" height="24" viewBox="0 0 24 24"
                                                                         fill="none" xmlns="http://www.w3.org/2000/svg">
																		<path opacity="0.3"
                                                                              d="M22 5V19C22 19.6 21.6 20 21 20H19.5L11.9 12.4C11.5 12 10.9 12 10.5 12.4L3 20C2.5 20 2 19.5 2 19V5C2 4.4 2.4 4 3 4H21C21.6 4 22 4.4 22 5ZM7.5 7C6.7 7 6 7.7 6 8.5C6 9.3 6.7 10 7.5 10C8.3 10 9 9.3 9 8.5C9 7.7 8.3 7 7.5 7Z"
                                                                              fill="currentColor"/>
																		<path d="M19.1 10C18.7 9.60001 18.1 9.60001 17.7 10L10.7 17H2V19C2 19.6 2.4 20 3 20H21C21.6 20 22 19.6 22 19V12.9L19.1 10Z"
                                                                              fill="currentColor"/>
																	</svg>
																</span>
                                                        <!--end::Svg Icon-->
                                                        @break

                                                @endswitch
                                                <a href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'downloadAllegato'],[$allegato->contratto_energia_id,$allegato->id])}}"
                                                   class="text-gray-800 text-hover-primary">{{$allegato->filename_originale}}</a>
                                            </div>
                                        </td>
                                        <td class="text-end">{{\App\humanFileSize($allegato->dimensione_file)}}</td>
                                        <td>{{$allegato->created_at->format('d/m/Y')}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
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
