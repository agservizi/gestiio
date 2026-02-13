<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Data ora</th>
            <th class="">Servizio</th>
            <th class="">Url</th>
            <th class="">Method</th>
            <th class="">Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td class="">{{$record->created_at->format('d/m/Y H:i:s')}}</td>
                <td class="">{{$record->servizio}}</td>
                <td class="">{{$record->url}}</td>
                <td class="">{{$record->method}}</td>
                <td class="">{{$record->status}}</td>
                <td class="text-end text-nowrap">
                    <a data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                       class="btn btn-sm btn-light btn-active-light-success"
                       href="{{action([$controller,'show'],$record->id)}}">Vedi</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator )
    <div class="w-100 text-center">
        {{$records->links()}}
    </div>
@endif
