@extends('Backend._layout._main')
@section('toolbar') @endsection

@section('content')
    <div class="row">
        <div class="col-md-9">
            <div class="card">
                <div class="card-body p-8">
                    @include('Backend._components.alertErrori')
                    <form method="POST"
                          action="{{$record->id ? action([\App\Http\Controllers\Backend\InpostPickupController::class,'update'],$record->id) : action([\App\Http\Controllers\Backend\InpostPickupController::class,'store'])}}"
                          id="form-pickup">
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
                                <h3 class="fw-bolder fs-3 mb-6">Contatto per il ritiro</h3>
                                <div class="row">
                                    <div class="col-md-12">
                                        @include('Backend._inputs.inputText',['campo'=>'contact_name','testo'=>'Nome contatto','required'=>true])
                                    </div>
                                    <div class="col-md-6">
                                        @include('Backend._inputs.inputText',['campo'=>'contact_email','testo'=>'Email contatto'])
                                    </div>
                                    <div class="col-md-6">
                                        @include('Backend._inputs.inputText',['campo'=>'contact_phone','testo'=>'Telefono contatto','required'=>true])
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card rounded border border-gray-300 mb-6">
                            <div class="card-body p-6">
                                <h3 class="fw-bolder fs-3 mb-6">Indirizzo di ritiro</h3>
                                <div class="row">
                                    <div class="col-md-8">
                                        @include('Backend._inputs.inputText',['campo'=>'street','testo'=>'Via/Corso','required'=>true])
                                    </div>
                                    <div class="col-md-4">
                                        @include('Backend._inputs.inputText',['campo'=>'building_number','testo'=>'Civico'])
                                    </div>
                                    <div class="col-md-4">
                                        @include('Backend._inputs.inputText',['campo'=>'post_code','testo'=>'CAP','required'=>true])
                                    </div>
                                    <div class="col-md-4">
                                        @include('Backend._inputs.inputText',['campo'=>'city','testo'=>'Città','required'=>true])
                                    </div>
                                    <div class="col-md-4">
                                        @include('Backend._inputs.inputText',['campo'=>'country_code','testo'=>'Codice paese','required'=>true])
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card rounded border border-gray-300 mb-6">
                            <div class="card-body p-6">
                                <h3 class="fw-bolder fs-3 mb-6">Data e colli</h3>
                                <div class="row">
                                    <div class="col-md-4">
                                        @include('Backend._inputs.inputText',['campo'=>'pickup_date','testo'=>'Data ritiro','required'=>true,'type'=>'date'])
                                    </div>
                                    <div class="col-md-4">
                                        @include('Backend._inputs.inputText',['campo'=>'parcel_count','testo'=>'Numero colli','required'=>true,'type'=>'number'])
                                    </div>
                                    <div class="col-md-4">
                                        @include('Backend._inputs.inputText',['campo'=>'customer_reference','testo'=>'Riferimento'])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        @include('Backend._inputs.inputText',['campo'=>'note','testo'=>'Note (es. campanello, piano)'])
                                    </div>
                                </div>
                                <div id="cutoff-info" class="mt-3 text-muted fs-7" style="display:none;">
                                    <i class="fas fa-clock me-1"></i> <span id="cutoff-text"></span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit">Prenota ritiro InPost</button>
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
                        <li class="mb-2">Inserisci il contatto che sarà presente al ritiro.</li>
                        <li class="mb-2">Specifica l'indirizzo esatto e la data desiderata.</li>
                        <li class="mb-2">Il corriere InPost passerà nella finestra 09:00–18:00.</li>
                        <li>Riceverai un ID ritiro per il tracciamento.</li>
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

            $.post('{{action([\App\Http\Controllers\Backend\InpostPickupController::class,'cutoffTime'])}}',
                {_token: '{{csrf_token()}}'},
                function (res) {
                    if (res && res.success && res.data) {
                        var cutoff = res.data.cutoff_time || res.data.cutoffTime || null;
                        if (cutoff) {
                            $('#cutoff-text').text('Orario limite prenotazione odierna: ' + cutoff);
                            $('#cutoff-info').show();
                        }
                    }
                }
            ).fail(function () {});
        });
    </script>
@endpush
