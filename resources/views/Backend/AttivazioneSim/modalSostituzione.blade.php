@extends('Backend._components.modal')
@section('content')

    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'store'])}}">
                @csrf
                @method('POST')
                @include('Backend._inputs.inputHidden',['campo'=>'attivazione_sim_id'])

                <div class="w-100 text-center pb-6">
                    <h5>Seriale vecchia sim</h5>
                    <h3>{{$attivazione->codice_sim}}</h3>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'motivazione','testo'=>'Motivazione','required'=>true,'array'=>\App\Models\SostituzioneSim::MOTIVAZIONI])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        @include('Backend._inputs.inputText',['campo'=>'seriale_nuova_sim','testo'=>'Seriale nuova sim','autocomplete'=>'off','required'=>true])
                    </div>
                </div>
                <div class="w-100 text-center">
                    <button class="btn btn-primary mt-3" type="submit" id="submit">Crea sostituzione</button>
                </div>
            </form>
        </div>
    </div>

@endsection
@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script>
        $(function () {

            formSubmitAndDelete();
        });
    </script>
@endpush
