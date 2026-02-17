@if(isset($pinnedMessages) && $pinnedMessages->isNotEmpty())
    <div class="d-flex flex-wrap gap-2">
        @foreach($pinnedMessages as $pin)
            <button type="button"
                    class="btn btn-sm btn-light-primary chat-pinned-jump"
                    data-msg-id="{{$pin->id}}"
                    title="Vai al messaggio">
                📌 {{$pin->mittente?->nominativo() ?? 'Utente'}}: {{\Illuminate\Support\Str::limit(strip_tags($pin->messaggio ?? ''), 45)}}
            </button>
        @endforeach
    </div>
@else
    <div class="text-muted fs-8">Nessun messaggio pinnato.</div>
@endif
