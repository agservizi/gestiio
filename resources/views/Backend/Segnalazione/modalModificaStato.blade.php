@extends('Backend._components.modal')
@section('content')
    <form id="form-aggiorna-stato" action="{{action([$controller,'aggiornaStato'],$record->id)}}" method="POST">
        <input type="hidden" name="aggiorna" value="{{old('aggiorna',request()->input('aggiorna'))}}">
        <input type="hidden" name="ruolo" value="admin">

        <div class="fv-row">
            <label class="d-flex align-items-center fs-5 fw-bold mb-4">
                <span class="required">Stato</span>
            </label>
            <div class="row">
                @php($selected=$record->esito_id)
                @foreach($stati as $stato)
                    <div class="col-6">
                        <label class="d-flex flex-stack mb-1 cursor-pointer rounded-2" style="padding: 10px; background-color: {{$stato->colore_hex}}; color: white;">
                            <span class="d-flex align-items-center me-2">
                                <span class="d-flex flex-column ">
                                    <span class="fw-bolder fs-6 " id="testo_{{$stato->id}}">{{$stato->nome}}</span>
                                </span>
                            </span>
                            <span class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input stato" type="radio" name="esito_id" value="{{$stato->id}}" {{$selected==$stato->id?'checked':''}}
                                data-motivo="{{$stato->chiedi_motivo}}"
                                >
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="row">
            <div class="col-12 text-center">
                <button class="btn btn-primary mt-3" type="submit">Aggiorna dati</button>
            </div>
        </div>
    </form>
@endsection
@push('customScript')

    <script>
        $(function () {

            impostaDivMotivazione($('.stato:checked'));
            $('.stato').click(function () {
                impostaDivMotivazione($(this));
            });

            $('.motivo_ko').off();
            $('.motivo_ko').click(function () {
                $('#motivo_ko').val($(this).text());
                if ($('#motivo_ko').val() === '') {

                }
            });

            function impostaDivMotivazione(stato) {
                if (stato.data('motivo') == 1) {
                    $('#div_motivazione').slideDown();
                } else {
                    $('#div_motivazione').slideUp();
                }
            }

            $('#form-aggiorna-stato').off().submit(function (e) {
                e.preventDefault();
                var url = $(this).attr('action');
                console.log(url);
                var data = $(this).serialize();
                console.log(data);
                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: data,
                    success: function (response) {
                        if (response.success) {
                            $('#tr_' + response.id).replaceWith(base64_decode(response.html));
                            $('#kt_modal').modal('hide');
                            modalAjax();
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });

        });
    </script>
@endpush
