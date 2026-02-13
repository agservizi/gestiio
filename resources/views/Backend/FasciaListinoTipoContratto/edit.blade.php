@extends('Backend._components.modal')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=false)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" id="form-fasce" action="{{action([$controller,'update'],[$agenteId,$tipoContrattoId])}}">
                @csrf
                @method('PATCH')

                <div class="row fw-bolder mb-4">
                    <div class="col-md-3">
                        Da contratti
                    </div>
                    <div class="col-md-4">
                        Importo per contratto
                    </div>
                    <div class="col-md-4">
                        Importo bonus
                    </div>
                </div>

                <div id="repeater-fasce">
                    <div data-repeater-list="fasce">

                        @if(count($records)==0)
                            @php($loopMain=0)
                            @include('Backend.FasciaListinoTipoContratto.repeaterFasciaOrdini',['record'=>new \App\Models\FasciaListinoTipoContratto()])
                        @endif


                        @foreach($records as $soglia)
                            @php($loopMain=$loop->index)
                            @include('Backend.FasciaListinoTipoContratto.repeaterFasciaOrdini',['record'=>$soglia])
                        @endforeach
                    </div>
                    <button data-repeater-create class="btn btn-primary btn-sm" type="button" style="display: block;"><i class="fa fa-plus"></i>Aggiungi fascia</button>
                </div>


                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit"
                                id="submit">{{'Salva modifiche'}}</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
@endsection
@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script src="/assets_backend/plugins/custom/formrepeater/formrepeater.bundle.js"></script>

    <script>
        $(function () {

            formSubmitAndDelete();
            autonumericImporto('autonumericImporto');

            repeaterSoglie();

            function repeaterSoglie() {
                $("#repeater-fasce").repeater({
                    show: function () {
                        $(this).slideDown();
                        autonumericImporto('autonumericImporto');
                    },
                    hide: function (deleteElement) {
                        //if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                        //}
                    },
                    ready: function (setIndexes) {
                    },
                    defaultValues: {}
                });

            }

        });
    </script>
@endpush
