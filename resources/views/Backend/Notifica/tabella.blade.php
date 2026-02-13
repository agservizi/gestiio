<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Data/Destinatario</th>
            <th class="">Testo</th>
            <th class="text-center">Letture</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td class="">
                    {{$record->created_at->format('d/m/Y')}}<br>
                    {{$record->destinatario}}
                </td>
                <td class="">
                    @if($record->titolo)
                        <strong>{{$record->titolo}}</strong>
                    @endif
                    {!! $record->testo !!}
                </td>
                <td class="text-center">{!! $record->letture_count !!}</td>
                <td class="text-end text-nowrap">
                    @if($record->destinatario=='agente' && $record->letture_count==0)
                        <a data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                           class="btn btn-sm btn-light btn-active-light-primary"
                           href="{{action([$controller,'edit'],$record->id)}}">Modifica</a>
                    @endif
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
