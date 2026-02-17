@php
    $altroReadAt = isset($altroLastReadAt) ? \Carbon\Carbon::parse($altroLastReadAt) : null;
@endphp

@if($messaggi->isEmpty())
    <div class="h-100 d-flex align-items-center justify-content-center text-muted fs-6 py-10">
        Nessun messaggio in questa conversazione.
    </div>
@else
    @foreach($messaggi as $messaggio)
        @php($mio = (int)$messaggio->user_id === (int)Auth::id())
        <div class="d-flex mb-4 {{$mio ? 'justify-content-end' : 'justify-content-start'}} chat-msg-row" data-msg-id="{{$messaggio->id}}">
            <div class="rounded px-4 py-3 {{$mio ? 'bg-light-primary' : 'bg-light'}} position-relative chat-bubble-wrap" style="max-width: 75%;">
                <div class="fw-bolder fs-8 text-gray-700 mb-1">{{$mio ? 'Tu' : ($messaggio->mittente?->nominativo() ?? 'Utente')}}</div>

                {{-- Risposte inline (quote del messaggio originale) --}}
                @if($messaggio->relationLoaded('replyTo') && $messaggio->replyTo)
                    <div class="border-start border-3 border-primary ps-3 py-1 mb-2 bg-light-info rounded-end" style="font-size: 0.8rem;">
                        <div class="fw-bold text-primary">{{$messaggio->replyTo->mittente?->nominativo() ?? 'Utente'}}</div>
                        <div class="text-gray-600">{{\Illuminate\Support\Str::limit(strip_tags($messaggio->replyTo->messaggio ?? '📎 Allegato'), 100)}}</div>
                    </div>
                @endif

                <div class="fs-6 text-gray-900">{!! nl2br(e($messaggio->messaggio)) !!}</div>

                @if($messaggio->allegati->isNotEmpty())
                    <div class="mt-2">
                        @foreach($messaggio->allegati as $allegato)
                            @php($allegatoUrl = asset('storage/'.$allegato->path_filename))
                            @php($isImage = \Illuminate\Support\Str::startsWith((string)$allegato->mime_type, 'image/'))
                            <div>
                                @if($isImage)
                                    <a href="{{$allegatoUrl}}" class="d-inline-block mt-2 chat-image-preview" data-full="{{$allegatoUrl}}" data-name="{{$allegato->filename_originale}}">
                                        <img src="{{$allegatoUrl}}" alt="{{$allegato->filename_originale}}" class="chat-image-thumb">
                                    </a>
                                    <div>
                                        <a class="fs-8 fw-bold" href="{{$allegatoUrl}}" target="_blank" rel="noopener">
                                            📎 {{$allegato->filename_originale}}
                                        </a>
                                    </div>
                                @else
                                    <a class="fs-8 fw-bold" href="{{$allegatoUrl}}" target="_blank" rel="noopener">
                                        📎 {{$allegato->filename_originale}}
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Reazioni emoji sotto il messaggio --}}
                @if($messaggio->relationLoaded('reazioni') && $messaggio->reazioni->isNotEmpty())
                    <div class="chat-reactions-display mt-2 d-flex flex-wrap gap-1">
                        @php
                            $grouped = $messaggio->reazioni->groupBy('emoji');
                        @endphp
                        @foreach($grouped as $emoji => $reazioniGruppo)
                            @php
                                $myReaction = $reazioniGruppo->contains('user_id', Auth::id());
                                $nomi = $reazioniGruppo->map(fn($r) => $r->utente?->nominativo() ?? 'Utente')->implode(', ');
                            @endphp
                            <button type="button"
                                    class="btn btn-sm px-2 py-0 chat-reaction-toggle {{$myReaction ? 'btn-light-primary border-primary' : 'btn-light'}}"
                                    data-msg-id="{{$messaggio->id}}"
                                    data-emoji="{{$emoji}}"
                                    title="{{$nomi}}">
                                {{$emoji}} <span class="fs-9">{{$reazioniGruppo->count()}}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mt-2 gap-3">
                    {{-- Pulsanti azioni --}}
                    <div class="chat-msg-actions d-flex gap-1" style="opacity: 0; transition: opacity .15s;">
                        <button type="button" class="btn btn-icon btn-sm btn-light chat-reply-btn" data-msg-id="{{$messaggio->id}}" data-author="{{$messaggio->mittente?->nominativo() ?? 'Utente'}}" data-text="{{Str::limit(strip_tags($messaggio->messaggio ?? '📎 Allegato'), 80)}}" title="Rispondi">
                            <i class="fas fa-reply fs-8"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-sm btn-light chat-react-btn" data-msg-id="{{$messaggio->id}}" title="Reagisci">
                            <i class="far fa-smile fs-8"></i>
                        </button>
                    </div>

                    <div class="text-muted fs-8 text-end text-nowrap">
                        {{$messaggio->created_at?->format('d/m/Y H:i')}}
                        @if($mio)
                            @if($altroReadAt && $messaggio->created_at && $messaggio->created_at->lte($altroReadAt))
                                <span class="text-primary ms-1" title="Letto">✓✓</span>
                            @else
                                <span class="text-muted ms-1" title="Inviato">✓</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
