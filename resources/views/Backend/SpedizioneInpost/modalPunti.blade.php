@extends('Backend._components.modal')
@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-row-bordered">
                <thead>
                <tr class="fw-bolder fs-6 text-gray-800">
                    <th>Nome</th>
                    <th>Indirizzo</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($points as $point)
                    <tr>
                        <td>{{$point['name']}}</td>
                        <td>{{$point['address']}}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary seleziona-punto"
                                    data-id="{{$point['id']}}" data-label="{{$point['name']}} {{$point['address']}}">Seleziona
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">Nessun punto trovato</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        $(function () {
            $('.seleziona-punto').click(function () {
                $('#punto_inpost_id').val($(this).data('id'));
                $('#punto_inpost_label').val($(this).data('label'));
                $('#kt_modal').modal('hide');
            });
        });
    </script>
@endpush
