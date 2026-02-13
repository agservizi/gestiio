@extends('Backend._layout._main')
@section('toolbar')
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
                        @include('Backend._inputs.inputText',['campo'=>'da_peso','testo'=>'Da peso','required'=>true,'autocomplete'=>'off','classe' => 'peso'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'a_peso','testo'=>'A peso','autocomplete'=>'off','classe' => 'peso'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'home_delivery','testo'=>'Home delivery',"classe"=>"autonumericImporto"])
                    </div>

                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'brt_fermopoint','testo'=>'Brt fermopoint',"classe"=>"autonumericImporto"])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'contrassegno','testo'=>'Contrassegno',"classe"=>"autonumericImporto"])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSwitch',['campo'=>'al_kg','testo'=>'Al kg',])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit"
                                id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\ListinoBrt::NOME_SINGOLARE}}</button>
                    </div>
                    @if($vecchio)
                        <div class="col-md-4 text-end">
                            @if($eliminabile===true)
                                <a class="btn btn-danger mt-3" id="elimina"
                                   href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
                            @elseif(is_string($eliminabile))
                                <span data-bs-toggle="tooltip" title="{{$eliminabile}}">
                                    <a class="btn btn-danger mt-3 disabled" href="javascript:void(0)">Elimina</a>
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

            </form>
        </div>
    </div>
@endsection
@push('customScript')
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            autonumericImporto('autonumericImporto');

            autonumericPeso('peso');

            function autonumericPeso(classe) {
                var slides = document.getElementsByClassName(classe);
                for (var i = 0; i < slides.length; i++) {
                    if (AutoNumeric.getAutoNumericElement(slides.item(i)) === null) {
                        new AutoNumeric(slides.item(i), {
                            decimalCharacter: ",",
                            decimalPlaces: 1,
                            digitGroupSeparator: ".",
                            watchExternalChanges: true,
                        });
                    }
                }
            }
        });
    </script>
@endpush
