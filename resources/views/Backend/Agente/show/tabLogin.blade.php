<div class="table-responsive">
    <table class="table align-middle table-row-dashed fw-bold text-gray-600 fs-6 gy-5" id="kt_table_customers_logs">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th> Data ora</th>
            <th> Email</th>
            <th class="text-center"> Remember</th>
            <th> Ip</th>
            <th> Os e browser</th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="{{$record->riuscito===0?'text-danger':''}}">
                <td> {{$record->created_at->format('d/m/Y H:i:s')}} </td>
                <td> {{$record->email}}
                    @if(!$record->user_id)
                        <strong class="text-danger">: email errata</strong>
                    @elseif($record->riuscito===0)
                        <strong class="text-danger">: password errata</strong>
                    @endif
                </td>
                <td class="text-center"> @if($record->remember)<i class="fa fa-check"></i>@endif </td>
                <td> {{$record->ip}}
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
        @endforeach
        </tbody>
    </table>
</div>
