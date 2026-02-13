@extends('Backend._layout._main')
@section('titolo','Registro login')
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered ">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th> Data ora</th>
                        <th> Email</th>
                        <th class="text-center"> Remember</th>
                        <th> Ip</th>
                        <th> Utente</th>
                        <th> Os e browser</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php($last=null)
                    @foreach($records as $record)
                        @php($giorno=$record->created_at->format('d/m/Y'))
                        @if($giorno!=$last)
                            <tr class="bg-light">
                                <td colspan="6" style="padding-left: 10px;">
                                    <strong class="fs-4">{{$giorno}}</strong>
                                </td>
                            </tr>
                        @endif
                        <tr class="{{$record->riuscito===0?'text-danger':''}}">
                            <td> {{$record->created_at->format('d/m/Y H:i:s')}} </td>
                            <td> {{$record->email}}
                                @if(!$record->user_id)
                                    <strong>: email errata</strong>
                                @elseif($record->riuscito===0)
                                    <strong>: password errata</strong>
                                @endif
                            </td>
                            <td class="text-center"> @if($record->remember)<i class="fa fa-check"></i>@endif </td>
                            <td> {{$record->ip}} </td>
                            <td>
                                @if($record->utente)
                                    {{$record->utente->nominativo()}}
                                @endif
                                @if($record->impersonatoDa)
                                    impersonato da {{$record->impersonatoDa->nominativo()}}
                                @endif
                            </td>
                            <td>
                                @if($record->user_agent)
                                    @php($result=Browser::parse($record->user_agent))
                                    {{$result->platformFamily()}}, {{$result->browserFamily()}}, {{$result->deviceModel()}}
                                @endif
                            </td>
                        </tr>
                        @php($last=$giorno)
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="w-100 text-center py-4">
            {{$records->links()}}
        </div>
    </div>
@endsection

