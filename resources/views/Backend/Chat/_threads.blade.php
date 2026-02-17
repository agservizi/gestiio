@php($threadAttivoId = $threadAttivo?->id)
@php($onlineMap = $onlineMap ?? [])

@forelse($threads as $thread)
    @php($altro = $thread->getRelation('altroPartecipante'))
    @php($isOnline = $altro && isset($onlineMap[$altro->id]) && $onlineMap[$altro->id])
    <button type="button"
            class="list-group-item list-group-item-action border-0 py-3 px-4 chat-thread-item {{$threadAttivoId === $thread->id ? 'active' : ''}}"
            data-thread-id="{{$thread->id}}">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="d-flex flex-column text-start">
                <span class="fw-bolder fs-6 text-gray-900">
                    <span class="chat-online-dot {{$isOnline ? 'online' : 'offline'}}"></span>
                    {{$altro?->nominativo() ?? 'Conversazione'}}
                </span>
                <span class="text-muted fs-8 mt-1">{{\Illuminate\Support\Str::limit(strip_tags($thread->ultimoMessaggio?->messaggio ?? 'Nessun messaggio'), 60)}}</span>
            </div>
            <div class="d-flex flex-column align-items-end">
                <span class="text-muted fs-8">{{$thread->ultimoMessaggio?->created_at?->format('d/m H:i') ?? $thread->created_at?->format('d/m H:i')}}</span>
                @if((int)($thread->unread_count ?? 0) > 0)
                    <span class="badge badge-danger mt-2">{{$thread->unread_count}}</span>
                @endif
            </div>
        </div>
    </button>
@empty
    <div class="px-4 py-6 text-center text-muted fs-7">
        Nessuna conversazione disponibile.
    </div>
@endforelse
