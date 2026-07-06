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
    <div class="notification-actions px-4 py-3">
        <a class="btn btn-sm btn-light-primary"
           href="{{action([\App\Http\Controllers\Backend\NotificaController::class,'index'])}}">Vedi tutte</a>
        @if($nuove)
            <button type="button"
                    class="btn btn-sm btn-light-warning"
                    wire:click="letteTutte()">Lette tutte
            </button>
        @endif
        @can('admin')
            <a href="{{action([\App\Http\Controllers\Backend\NotificaController::class,'create'])}}"
               class="btn btn-sm btn-light-success">Nuova</a>
        @endcan
    </div>
    <div class="scroll-y mh-325px px-2 pb-4">
        @foreach($notifiche as $record)
            <div class="notification-item {{$record->tipo=='error'?'notification-item-danger':''}} {{$record->letture_count ? 'notification-item-read' : ''}}">
                <div class="notification-item-main">
                    <div class="notification-meta">
                            <span class="badge badge-light fs-8">{{$record->created_at->diffForHumans()}}</span>
                            @if(!$record->letture_count)
                                <span class="badge badge-danger fs-8 ms-2">Nuova</span>
                            @endif
                    </div>
                    <div class="notification-title">
                        {{ $record->titolo??$record->testo }}
                    </div>
                </div>
                <div class="notification-cta">
                            <a data-target="kt_modal" data-toggle="modal-ajax"
                       class="btn btn-sm btn-success"
                               href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['vedi-notifica',$record->id])}}">Vedi</a>
                    @if(!$record->letture_count)
                        <button type="button" class="btn btn-sm btn-light-warning"
                                wire:click="visto({{$record->id}})">Letta</button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    <style>
        .notification-actions {
            display: flex;
            gap: .5rem;
            border-bottom: 1px solid #eef3f8;
            background: #fff;
        }

        .notification-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: .75rem;
            padding: .85rem .75rem;
            border-bottom: 1px solid #eef3f8;
            background: #fff;
        }

        .notification-item:last-child {
            border-bottom: 0;
        }

        .notification-item-danger {
            background: #fff5f8;
        }

        .notification-item-read {
            opacity: .72;
        }

        .notification-meta {
            display: flex;
            align-items: center;
            gap: .25rem;
            margin-bottom: .35rem;
        }

        .notification-title {
            color: #162033;
            font-size: .95rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .notification-cta {
            display: flex;
            align-items: flex-start;
            gap: .4rem;
        }

        @media (max-width: 420px) {
            .notification-item {
                grid-template-columns: 1fr;
            }

            .notification-cta {
                justify-content: flex-start;
            }
        }
    </style>
</div>
