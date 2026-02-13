@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertMessage')
            <div class="row">
                <div class="col-md-6 pt-sm-8 pt-md-0">
                    <h4>Ricarica agente</h4>
                    <form method="POST"
                          action="{{action([\App\Http\Controllers\Backend\RicaricaPlafonController::class,'store'])}}"
                          class="card-form mt-3 mb-3">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                @include('Backend._inputs.inputSelect2Enum',['campo'=>'portafoglio','testo'=>'Portafoglio','required'=>true,'cases'=>\App\Enums\TipiPortafoglioEnum::class])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                @include('Backend._inputs.inputText',['campo'=>'importo','testo'=>'Importo','required'=>true,"classe"=>"autonumericImporto"])
                            </div>
                        </div>

                        <div class="w-100 text-center">
                            <button class="btn btn-primary mt-3" type="submit" id="submit">Carica</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-5">
        <div class="col-md-7 col-lg-8">
        </div>
    </div>
@endsection



@push('customCss')
@endpush
@push('customScript')
    <script>
        $(function () {
            select2UniversaleBackend('agente_id', 'un agente', 1, 'agente_id');
        });
    </script>
@endpush
