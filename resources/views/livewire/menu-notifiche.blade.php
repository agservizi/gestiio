<div wire:poll.15000ms>
    <div class="d-flex flex-column bgi-no-repeat rounded-top"
         style="background-image:url('/assets_backend/media/misc/menu-header-bg.jpg')">
        <h3 class="text-white fw-semibold px-9 mt-10 mb-6 w-100">{{$conteggio}}
            <span class=" opacity-75 ps-3"> Nuove notifiche</span>
            @can('admin')
                <a href="{{action([\App\Http\Controllers\Backend\NotificaController::class,'create'])}}"
                   class="btn btn-sm btn-primary float-end py-1">Nuova notifica</a>
            @endcan
        </h3>
    </div>
    @if($conteggio)
        <script>
            const el = document.getElementById('nuove');
            el.setAttribute('style', 'display:block');
        </script>
    @endif
    <div class="scroll-y mh-325px my-5 px-2">
        @foreach($notifiche as $record)
            <div class="d-flex flex-stack py-4 {{$record->tipo=='error'?'bg-danger':''}} px-2">
                <div class="d-flex align-items-center">
                    <div class="mb-0">
                        <div class="d-flex align-content-between">
                            <span class="badge badge-light fs-8">{{$record->created_at->diffForHumans()}}</span>
                            @if(!$record->letture_count)
                                <span class="badge badge-danger fs-8 ms-2">Nuova</span>
                            @endif
                            <a data-target="kt_modal" data-toggle="modal-ajax"
                               class="btn btn-sm btn-success py-0 ms-2 " style="float: right;"
                               href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['vedi-notifica',$record->id])}}">Vedi</a>
                        </div>
                        <div class="text-gray-900 fs-6 fw-bold">
                            {!! $record->titolo??$record->testo !!}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @can('admin')
        <div class="py-3 d-flex justify-content-between border-top">
            <a class="btn btn-sm btn-light-success btn-active-light-success  mx-2"
               href="{{action([\App\Http\Controllers\Backend\NotificaController::class,'index'])}}">Vedi tutte</a>
            @if($nuove)
                <button type="button"
                        class="btn btn-sm btn-light-warning btn-active-light-warning me-2"
                        wire:click="letteTutte()">Lette tutte
                </button>
            @endif
        </div>
    @endcan
</div>
