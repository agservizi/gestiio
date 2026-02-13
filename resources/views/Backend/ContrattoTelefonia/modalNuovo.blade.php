@extends('Backend._components.modal')
@section('content')
    <form id="form-aggiorna-stato"
          action="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'create'])}}" method="GET"
          class="mt-4 mb-6">

        <div class="row">
            @if(Auth::user()->hasPermissionTo('admin'))
                <div class="col-md-12">
                    @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                </div>
            @else
                <input type="hidden" id="agente_id" name="agente_id" value="{{old('agente_id',$record->agente_id)}}">
            @endif
        </div>

        @include("Backend._components.inputSelect2",["campo"=>"tipo_contratto_id","testo"=>"Tipo contratto","required"=>true,'selected'=>null,'classe'=>'flex-fill'])
        <div class="w-100 text-center d-none">
            <input type="button" class="btn btn-primary" id="crea" value="Crea contratto" disabled>
        </div>
    </form>
    <script src="/assets_backend/js-miei/select2_it.js"></script>

    <script>
        $(function () {

            if ($('#agente_id').is('select')) {

                $('#agente_id').select2({
                    placeholder: 'Seleziona agente',
                    minimumInputLength: -1,
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#kt_modal_content'),
                    ajax: {
                        quietMillis: 150,
                        url: "/backend/select2?agente_id",
                        dataType: 'json',
                        data: function (term, page) {
                            return {
                                term: term.term
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data
                            };
                        }
                    }
                });
            }

            $('#tipo_contratto_id').select2({
                placeholder: 'Seleziona una tipo contratto',
                minimumInputLength: -1,
                allowClear: true,
                width: '100%',
                dropdownParent: $('#kt_modal_content'),
                ajax: {
                    quietMillis: 150,
                    url: function () {
                        return "/backend/select2?tipo_contratto_id&agente_id=" + $('#agente_id').val();
                    },
                    dataType: 'json',
                    data: function (term, page) {
                        return {
                            term: term.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    }
                }
            }).on('select2:select', function (e) {
                // Access to full data

                var agenteid = '';
                if ($('#agente_id').val()) {
                    agenteid = '&agente_id=' + $('#agente_id').val();
                }
                location.href = '{{action([$controller,'create'])}}?new&tipo_contratto_id=' + e.params.data.id + agenteid;
                //$('#crea').prop('disabled',false);
                //$('#crea-multiplo').prop('disabled',false);
            }).on('select2:clear', function (e) {
                // Access to full data
                $('#crea').prop('disabled', true);
            });
            $('#crea').click(function () {
                location.href = '{{action([$controller,'create'])}}?tipo_contratto_id=' + $('#tipo_contratto_id').val();
            });

        });
    </script>
@endsection
