@php
    $altroReadAt = isset($altroLastReadAt) ? \Carbon\Carbon::parse($altroLastReadAt) : null;
    $authId = (int) Auth::id();
@endphp

@if($messaggi->isEmpty())
    <div class="h-100 d-flex align-items-center justify-content-center text-muted fs-6 py-10">
        Nessun messaggio in questa conversazione.
    </div>
@else
    @foreach($messaggi as $messaggio)
        @php
            $mio = (int) $messaggio->user_id === (int) Auth::id();
        @endphp
        <div class="d-flex mb-4 {{$mio ? 'justify-content-end' : 'justify-content-start'}} chat-msg-row" data-msg-id="{{$messaggio->id}}">
            <div class="rounded px-4 py-3 {{$mio ? 'bg-light-primary' : 'bg-light'}} position-relative chat-bubble-wrap" style="max-width: 75%;">
                <div class="fw-bolder fs-8 text-gray-700 mb-1">{{$mio ? 'Tu' : ($messaggio->mittente?->nominativo() ?? 'Utente')}}</div>

                @if((int)($messaggio->priority ?? 0) > 0)
                    <span class="badge badge-light-danger mb-2">Priorità alta</span>
                @endif

                @if($messaggio->inoltratoDa)
                    <div class="fs-8 text-muted mb-2">↪ Messaggio inoltrato</div>
                @endif

                {{-- Risposte inline (quote del messaggio originale) --}}
                @if($messaggio->relationLoaded('replyTo') && $messaggio->replyTo)
                    <div class="border-start border-3 border-primary ps-3 py-1 mb-2 bg-light-info rounded-end" style="font-size: 0.8rem;">
                        <div class="fw-bold text-primary">{{$messaggio->replyTo->mittente?->nominativo() ?? 'Utente'}}</div>
                        <div class="text-gray-600">{{\Illuminate\Support\Str::limit(strip_tags($messaggio->replyTo->messaggio ?? '📎 Allegato'), 100)}}</div>
                    </div>
                @endif

                @php
                    $escapedMessage = e($messaggio->messaggio ?? '');
                    $messageWithMentions = preg_replace_callback(
                        '/@([a-zA-Z0-9._-]{2,40})/u',
                        function ($match) {
                            $tag = \Illuminate\Support\Str::lower($match[1]);
                            return '<span class="text-primary fw-bold chat-mention-link" data-tag="' . e($tag) . '" title="Apri chat con @' . e($tag) . '">@' . e($match[1]) . '</span>';
                        },
                        $escapedMessage
                    );
                @endphp
                <div class="fs-6 text-gray-900">{!! nl2br($messageWithMentions) !!}</div>

                @if($messaggio->allegati->isNotEmpty())
                    <div class="mt-2">
                        @foreach($messaggio->allegati as $allegato)
                            @php
                                $relativePath = ltrim((string) $allegato->path_filename, '/');
                                $fileExists = $relativePath !== ''
                                    && (
                                        \Illuminate\Support\Facades\Storage::disk('local')->exists($relativePath)
                                        || \Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath)
                                    );
                                $cacheBust = (int) ($allegato->updated_at?->timestamp ?? $allegato->id);
                                $allegatoUrl = action([\App\Http\Controllers\Backend\ChatController::class, 'attachment'], ['id' => $allegato->id]).'?v='.$cacheBust;
                                $allegatoDownloadUrl = action([\App\Http\Controllers\Backend\ChatController::class, 'attachment'], ['id' => $allegato->id]).'?download=1&v='.$cacheBust;
                                $mime = strtolower((string) $allegato->mime_type);
                                $isSvg = str_contains($mime, 'svg');
                                $isImage = str_starts_with($mime, 'image/') && ! $isSvg;
                                $isPdf = $mime === 'application/pdf' || str_ends_with(strtolower((string) $allegato->filename_originale), '.pdf');
                            @endphp
                            <div class="mt-2">
                                @if(! $fileExists)
                                    <span class="badge badge-light-warning">
                                        Allegato non disponibile: {{ $allegato->filename_originale }}
                                    </span>
                                @else
                                    @if($isImage)
                                        <a href="{{$allegatoUrl}}" class="d-inline-block chat-image-preview" data-full="{{$allegatoUrl}}" data-name="{{$allegato->filename_originale}}">
                                            <img src="{{$allegatoUrl}}"
                                                 alt="{{$allegato->filename_originale}}"
                                                 class="chat-image-thumb"
                                                 loading="lazy"
                                                 onerror="this.onerror=null;this.style.display='none';var s=document.createElement('span');s.className='badge badge-light-danger';s.textContent='Anteprima non disponibile';this.parentNode&&this.parentNode.appendChild(s);">
                                        </a>
                                    @elseif($isPdf)
                                        <button type="button"
                                                class="btn btn-sm btn-light-danger chat-pdf-preview"
                                                data-url="{{$allegatoUrl}}"
                                                data-name="{{$allegato->filename_originale}}">
                                            <i class="fas fa-file-pdf me-1"></i> Anteprima PDF
                                        </button>
                                    @endif
                                    <div>
                                        <a class="fs-8 fw-bold" href="{{$allegatoDownloadUrl}}" target="_blank" rel="noopener">
                                            {{$allegato->filename_originale}}
                                        </a>
                                    </div>
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
                                $nomiArray = [];
                                foreach ($reazioniGruppo as $reazione) {
                                    $nomeUtente = 'Utente';
                                    if (isset($reazione->utente) && $reazione->utente) {
                                        $nomeUtente = $reazione->utente->nominativo();
                                    }
                                    $nomiArray[] = $nomeUtente;
                                }
                                $nomi = implode(', ', $nomiArray);
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
                        <button type="button" class="btn btn-icon btn-sm btn-light chat-forward-select-btn" data-msg-id="{{$messaggio->id}}" title="Seleziona per inoltro">
                            <i class="fas fa-check-square fs-8"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-sm btn-light chat-reply-btn" data-msg-id="{{$messaggio->id}}" data-author="{{$messaggio->mittente?->nominativo() ?? 'Utente'}}" data-text="{{Str::limit(strip_tags($messaggio->messaggio ?? '📎 Allegato'), 80)}}" title="Rispondi">
                            <i class="fas fa-reply fs-8"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-sm btn-light chat-pin-btn" data-msg-id="{{$messaggio->id}}" title="Metti in evidenza">
                            <i class="fas fa-thumbtack fs-8"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-sm btn-light chat-favorite-btn" data-msg-id="{{$messaggio->id}}" title="Preferito">
                            <i class="fas fa-star fs-8"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-sm btn-light chat-react-btn" data-msg-id="{{$messaggio->id}}" title="Reagisci">
                            <i class="far fa-smile fs-8"></i>
                        </button>
                        @if($mio || Auth::user()?->hasPermissionTo('admin'))
                            <button type="button" class="btn btn-icon btn-sm btn-light chat-edit-btn" data-msg-id="{{$messaggio->id}}" data-text="{{e($messaggio->messaggio)}}" title="Modifica">
                                <i class="fas fa-pen fs-8"></i>
                            </button>
                            <button type="button" class="btn btn-icon btn-sm btn-light chat-delete-btn" data-msg-id="{{$messaggio->id}}" title="Elimina">
                                <i class="fas fa-trash fs-8"></i>
                            </button>
                        @endif
                        @if($messaggio->edited_at || $messaggio->deleted_at)
                            <button type="button" class="btn btn-icon btn-sm btn-light chat-history-btn" data-msg-id="{{$messaggio->id}}" title="Storico modifiche">
                                <i class="fas fa-history fs-8"></i>
                            </button>
                        @endif
                    </div>

                    <div class="text-muted fs-8 text-end text-nowrap">
                        {{$messaggio->created_at?->format('d/m/Y H:i')}}
                        @if($messaggio->edited_at)
                            <span class="ms-1" title="Modificato">(mod.)</span>
                        @endif
                        @if($mio)
                            @if($altroReadAt && $messaggio->created_at && $messaggio->created_at->lte($altroReadAt))
                                <span class="text-primary ms-1" title="Letto">✓✓</span>
                            @elseif($messaggio->delivered_at)
                                <span class="text-info ms-1" title="Consegnato {{$messaggio->delivered_at?->format('d/m H:i')}}">✓✓</span>
                            @else
                                <span class="text-muted ms-1" title="Inviato">✓</span>
                            @endif
                        @endif
                    </div>
                </div>

                @php
                    $pinned = $messaggio->relationLoaded('pin') ? $messaggio->pin->contains('user_id', $authId) : false;
                    $favorite = $messaggio->relationLoaded('preferiti') ? $messaggio->preferiti->contains('user_id', $authId) : false;
                @endphp
                @if($pinned || $favorite)
                    <div class="mt-1 fs-8 text-muted">
                        @if($pinned) 📌 In evidenza @endif
                        @if($favorite) ⭐ Preferito @endif
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endif
