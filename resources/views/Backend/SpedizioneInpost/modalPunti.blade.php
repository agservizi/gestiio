@extends('Backend._components.modal')
@section('content')
    <div class="card">
        <div class="card-body">
            @if(!empty($errorMessage))
                <div class="alert alert-warning mb-5">
                    Ricerca punti non disponibile al momento.
                    <div class="small text-muted mt-2">{{$errorMessage}}</div>
                </div>
            @endif

            @if(!empty($source))
                <div class="text-muted fs-7 mb-3">Fonte risultati: {{$source}}</div>
            @endif

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
