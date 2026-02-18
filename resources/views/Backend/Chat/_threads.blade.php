@php
    $threadAttivoId = $threadAttivo?->id;
    $onlineMap = $onlineMap ?? [];
@endphp

@forelse($threads as $thread)
    @php
        $altro = $thread->getRelation('altroPartecipante');
        $isOnline = $altro && isset($onlineMap[$altro->id]) && $onlineMap[$altro->id];
        $isActive = $threadAttivoId === $thread->id;
        $canChat = (bool) ($thread->can_chat ?? true);
    @endphp
    <div class="list-group-item list-group-item-action border-0 py-3 px-4 chat-thread-item {{$isActive ? 'active' : ''}}"
         data-thread-id="{{$thread->id}}"
         data-chat-allowed="{{$canChat ? 1 : 0}}"
         style="cursor: {{$canChat ? 'pointer' : 'not-allowed'}};">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="d-flex flex-column text-start">
                <span class="fw-bolder fs-6 {{$isActive ? 'text-white' : 'text-gray-900'}}">
                    <span class="chat-online-dot {{$isOnline ? 'online' : 'offline'}}"></span>
                    {{$altro?->nominativo() ?? 'Conversazione'}}
                    @if(!$canChat)
                        <span class="badge badge-light-danger ms-1">Accesso revocato</span>
                    @endif
                    @if(!empty($thread->is_muted))
                        <span class="badge badge-light ms-1">🔕</span>
                    @endif
                </span>
                <span class="{{$isActive ? 'text-white opacity-75' : 'text-gray-700'}} fs-8 mt-1">{{\Illuminate\Support\Str::limit(strip_tags($thread->ultimoMessaggio?->messaggio ?? 'Nessun messaggio'), 60)}}</span>
            </div>
            <div class="d-flex flex-column align-items-end">
                <div class="d-flex align-items-center gap-2">
                    <span class="{{$isActive ? 'text-white opacity-75' : 'text-muted'}} fs-8">{{$thread->ultimoMessaggio?->created_at?->format('d/m H:i') ?? $thread->created_at?->format('d/m H:i')}}</span>
                    <button type="button"
                            class="btn btn-icon btn-sm {{$isActive ? 'btn-light' : 'btn-light-secondary'}} chat-thread-mute"
                            data-thread-id="{{$thread->id}}"
                            title="Silenzia conversazione"
                            aria-label="Silenzia conversazione">
                        <i class="fas fa-bell-slash fs-8"></i>
                    </button>
                    <button type="button"
                            class="btn btn-icon btn-sm {{$isActive ? 'btn-light' : 'btn-light-secondary'}} chat-thread-close"
                            data-thread-id="{{$thread->id}}"
                            title="Chiudi conversazione"
                            aria-label="Chiudi conversazione">
                        <i class="fas fa-times fs-8"></i>
                    </button>
                </div>
                @if((int)($thread->unread_count ?? 0) > 0)
                    <span class="badge badge-danger mt-2">{{$thread->unread_count}}</span>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="px-4 py-6 text-center text-muted fs-7">
        Nessuna conversazione disponibile.
    </div>
@endforelse
