<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Data</th>
            <th class="">Testo</th>
            <th class="">Recipients</th>
            <th class="text-end">Credito</th>
            <th class="">Esito</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td class="">{{$record->created_at->format('d/m/Y H:i')}}</td>
                <td class="">{{$record->testo}}</td>
                <td class="">{{count($record->recipients)}}</td>
                <td class="text-end">{{$record->credito}}</td>
                <td class="">
                {!! \App\Enums\EsitiSmsEnum::tryFrom($record->esito)?->badge() !!}
                </td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-icon btn-sm btn-light btn-active-light-primary" href="{{action([$controller,'show'],$record->id)}}"
                       data-bs-toggle="tooltip" data-bs-placement="top" title="Vedi"

                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                        </svg>
                    </a>
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
