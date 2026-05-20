@extends('Backend._layout._main')
@section('toolbar') @endsection

@section('content')
    <div class="row">
        <div class="col-md-9">
            <div class="card">
                <div class="card-body p-8">
                    @include('Backend._components.alertErrori')
                    <form method="POST"
                          action="{{$record->id ? action([\App\Http\Controllers\Backend\InpostReturnController::class,'update'],$record->id) : action([\App\Http\Controllers\Backend\InpostReturnController::class,'store'])}}"
                          id="form-return">
                        @csrf
                        @method($record->id ? 'PATCH' : 'POST')

                        @if(Auth::user()->hasAnyPermission(['admin']))
                            <div class="row mb-6">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="agente_id" value="{{old('agente_id',$record->agente_id)}}">
                        @endif

                        <div class="card rounded border border-gray-300 mb-6">
                            <div class="card-body p-6">
                                <h3 class="fw-bolder fs-3 mb-6">Destinatario del reso</h3>
                                <div class="row">
                                    <div class="col-md-12">
                                        @include('Backend._inputs.inputText',['campo'=>'receiver_name','testo'=>'Nome destinatario','required'=>true])
                                    </div>
                                    <div class="col-md-6">
                                        @include('Backend._inputs.inputText',['campo'=>'receiver_email','testo'=>'Email'])
                                    </div>
                                    <div class="col-md-6">
                                        @include('Backend._inputs.inputText',['campo'=>'receiver_phone','testo'=>'Telefono'])
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card rounded border border-gray-300 mb-6">
                            <div class="card-body p-6">
                                <h3 class="fw-bolder fs-3 mb-6">Punto InPost di consegna</h3>
                                <p class="text-muted mb-5">Il cliente consegna il pacco in questo locker o punto di ritiro.</p>
                                <div class="row">
                                    <div class="col-md-9">
                                        @include('Backend._inputs.inputTextButton',['campo'=>'point_id','testo'=>'ID Punto InPost','testoButton'=>'Cerca','classe'=>'cerca-punto'])
                                    </div>
                                    <div class="col-md-3">
                                        @include('Backend._inputs.inputText',['campo'=>'point_label','testo'=>'Etichetta punto'])
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card rounded border border-gray-300 mb-6">
                            <div class="card-body p-6">
                                <h3 class="fw-bolder fs-3 mb-4">Riferimento</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('Backend._inputs.inputText',['campo'=>'customer_reference','testo'=>'Riferimento ordine'])
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit">Crea reso InPost</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fs-4 fw-bolder">Come funziona</h3>
                </div>
                <div class="card-body pt-2 fs-6 text-muted">
                    <ol class="ps-4 mb-0">
                        <li class="mb-2">Inserisci il destinatario del reso (il tuo cliente).</li>
                        <li class="mb-2">Scegli il punto InPost più comodo per il cliente.</li>
                        <li class="mb-2">Crea il reso: il cliente riceverà un QR code da scansionare al punto.</li>
                        <li>Traccia il pacco con il numero di tracking generato.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('customScript')
    <script>
        $(function () {
            if ($('#agente_id').length && $('#agente_id').is('select')) {
                select2UniversaleBackend('agente_id', 'un agente', 1);
            }

            $(document).on('click', '#button-point_id', function (e) {
                e.preventDefault();
                var url = '{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['inpost_return_points'])}}';
                apriModal(url);
            });
        });
    </script>
@endpush
