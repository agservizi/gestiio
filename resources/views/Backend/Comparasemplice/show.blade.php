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
            </div>
        </div>
    @endif
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card pt-4">
        <div class="card-body pt-0 pb-5 fs-6 text-center" id="tabella">
            <a href="https://promo.comparasemplice.it/segnalatori-multiprodotto/?campaign=WS_AMB_AGSERVIZI_CAVALIERE_CARMINE&pr=161"
               target="_blank" class="btn btn-primary mt-3">Vai a ComparaSemplice</a>
        </div>
    </div>
    <div class="card mt-6">
        <div class="card-body">
            <div class="row">
                @if(Auth::user()->hasPermissionTo('admin'))
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'valore'=>$record->agente->nominativo()])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'data','testo'=>'Data','valore'=>$record->data->format('d/m/Y')])
                    </div>
                @else
                    <input type="hidden" id="agente_id" name="agente_id"
                           value="{{old('agente_id',$record->agente_id)}}">
                    <input type="hidden" name="data" value="{{old('data',$record->data->format('d/m/Y'))}}">
                @endif
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_segnalazione','testo'=>'Tipo servizi','required'=>true,'valore'=>implode(', ',$record->tipo_segnalazione)])
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
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'email','testo'=>'Email','autocomplete'=>'off','required'=>true])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'cellulare','testo'=>'Cellulare','autocomplete'=>'off','required'=>true])
                </div>

            </div>

        </div>
    </div>
    <h4 class="mt-6">Allegati</h4>
    @include('Backend.ContrattoTelefonia.allegati',['downloadController' => \App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'idPadre'=>$record->id])
@endsection
@push('customScript')
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
        });
    </script>
@endpush
