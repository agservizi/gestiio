@extends('Backend._components.modal')
@section('content')

    <div class="card">
        <div class="card-body">
            <table class="table table-row-bordered" id="tabella-elenco">
                <thead>
                <tr class="fw-bolder fs-6 text-gray-800">
                    <th class="">Larghezza (cm)</th>
                    <th class="">Altezza (cm)</th>
                    <th class="">Prodondità (cm)</th>

                </tr>
                </thead>
                <tbody>
                @for($n=1;$n<=$colli;$n++)
                    <tr>
                        @php($campo='larghezza'.$n)
                        <td>
                            <div class="fv-row fv-plugins-icon-container mb-6">

                                <input type="text" id="{{$campo}}" name="{{$campo}}"
                                       class="form-control form-control-solid intero"
                                       placeholder="{{$placeholder??''}}"
                                       value="{{ old($campo) }}" data-required="{{$required??''}}"
                                       @if($required??false) required @endif
                                       autocomplete="{{$autocomplete??''}}"
                                >

                                @if($help??false)
                                    <div class="form-text">{{$help}}</div>
                                @endif
                            </div>
                        </td>
                        @php($campo='altezza'.$n)
                        <td>
                            <div class="fv-row fv-plugins-icon-container mb-6">

                                <input type="text" id="{{$campo}}" name="{{$campo}}"
                                       class="form-control form-control-solid intero"
                                       placeholder="{{$placeholder??''}}"
                                       value="{{ old($campo) }}" data-required="{{$required??''}}"
                                       @if($required??false) required @endif
                                       autocomplete="{{$autocomplete??''}}"
                                >

                                @if($help??false)
                                    <div class="form-text">{{$help}}</div>
                                @endif
                            </div>
                        </td>
                        @php($campo='profondita'.$n)
                        <td>
                            <div class="fv-row fv-plugins-icon-container mb-6">

                                <input type="text" id="{{$campo}}" name="{{$campo}}"
                                       class="form-control form-control-solid intero"
                                       placeholder="{{$placeholder??''}}"
                                       value="{{ old($campo) }}" data-required="{{$required??''}}"
                                       @if($required??false) required @endif
                                       autocomplete="{{$autocomplete??''}}"
                                >

                                @if($help??false)
                                    <div class="form-text">{{$help}}</div>
                                @endif
                            </div>
                        </td>

                    </tr>
                @endfor
                </tbody>

            </table>
            <span id="volume"
                  class="form-control form-control-solid intero">
            </span>

            <div class="w-100 text-center">
                <button type="button" id="chiudi" class="btn btn-sm btn-primary">Chiudi</button>
            </div>
        </div>
    </div>

@endsection
@push('customScript')
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script src="/assets_backend/js-miei/numeral.min.js"></script>
    <script src="/assets_backend/js-miei/numeralIt.min.js"></script>
    <script>
        $(function () {
            var volumeTotale;
            numeral.locale('it');
            autonumericIntero('intero');
            const colli = {{$colli}};
            $('.intero').keyup(function () {
                calcolaTotali();
            });

            $('#chiudi').click(function () {
                $('#volume_totale').val(volumeTotale);
                $('#kt_modal').modal('hide');
            });


            function calcolaTotali() {
                volumeTotale=0;

                var larghezza, altezza, profondita;
                var volumecollo = 0;

                for (n = 1; n <= colli; n++) {
                    larghezza = numeral($('#larghezza' + n).val()).value() ?? 0;
                    altezza = numeral($('#altezza' + n).val()).value() ?? 0;
                    profondita = numeral($('#profondita' + n).val()).value() ?? 0;


                    volumecollo = larghezza / 100 * altezza / 100 * profondita / 100;
                    //console.log('volumecollo' + n + ': ' + volumecollo);
                    volumeTotale += volumecollo;


                }

                $('#volume').text(new Intl.NumberFormat('it-IT', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(volumeTotale));


            }
        });
    </script>

@endpush
