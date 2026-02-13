@extends('Backend._components.modal')
@section('toolbar')
@endsection

@section('content')

    @php($vecchio=false)
    <div class="card">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-row-bordered" id="tabella-elenco">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th class="">Da contratti</th>
                        <th class="text-end">Importo per contratto</th>
                        <th class="text-end">Importo bonus</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($records as $record)
                        <tr class="">
                            <td class="">
                                {{$record->da_contratti}}<br>
                            </td>
                            <td class="text-end">
                                {!! \App\importo($record->importo_per_contratto) !!}
                            </td>
                            <td class="text-end">
                                {!! \App\importo($record->importo_bonus) !!}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('customScript')
@endpush
