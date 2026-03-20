@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        <a class="btn btn-sm btn-primary fw-bold" href="{{action([$controller,'create'])}}">Nuovo reso InPost</a>
    </div>
@endsection
@section('content')
    <div class="card pt-4">
        <div class="card-body pt-0 pb-5 fs-6">
            <div class="table-responsive">
                <table class="table table-row-bordered">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th>Data</th><th>Ref</th><th>Remote ID</th><th>Stato</th><th>Punto</th><th>Agente</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($records as $record)
                        <tr>
                            <td>{{$record->created_at->format('d/m/Y')}}</td>
                            <td>{{$record->customer_reference}}</td>
                            <td>{{$record->remote_id}}</td>
                            <td>{{$record->status}}</td>
                            <td>{{$record->point_id}}</td>
                            <td>{{$record->agente?->aliasAgente()}}</td>
                            <td><a class="btn btn-sm btn-light-primary" href="{{action([$controller,'show'],$record->id)}}">Vedi</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{$records->links()}}
        </div>
    </div>
@endsection
