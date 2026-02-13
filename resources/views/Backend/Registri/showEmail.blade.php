@extends('Backend._components.modal')
@section('content')
    <div class="card mb-6">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="font-weight-bold"> Data invio</td>
                    <td>
                        {{$record->data->format('d/m/Y H:i:s')}}
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold"> A</td>
                    <td>
                        {{$record->to}}
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold"> Da</td>
                    <td>
                        {{$record->from}}
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold"> Oggetto</td>
                    <td>
                        {{$record->subject}}
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold"> Allegati</td>
                    <td>
                        {{$record->attachments}}
                    </td>
                </tr>

                </tbody>
            </table>
            <div class="card card-body">
                {!! $record->body !!}
            </div>

        </div>
    </div>
@endsection

