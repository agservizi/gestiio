@extends('Backend._components.modal')
@section('content')

    <div class="card">
        <div class="card-body">

            <table class="table table-row-bordered" id="tabella-elenco">
                <thead>
                <tr class="fw-bolder fs-6 text-gray-800">
                    <th class="">Nome</th>
                    <th class="">Indirizzo</th>
                </tr>
                </thead>
                <tbody>
                @foreach($pudos as $pudo)
                    <tr>
                        <td>
                            {{$pudo['pointName']}}
                        </td>
                        <td>
                            {{$pudo['fullAddress']}}
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary seleziona-pudo"
                                    name="{{$pudo['pudoId']}}">Seleziona
                            </button>
                        </td>
                    </tr>
                @endforeach


                </tbody>

            </table>


        </div>
    </div>

@endsection
@push('customScript')

    <script>
        $(function () {


            $('.seleziona-pudo').click(function () {
                var pudoId = $(this).attr('name');

                $('#pudo_id').val(pudoId);
                window.aggiornaPrezzoBrt();
                $('#kt_modal').modal('hide');

            });

        });
    </script>

@endpush
