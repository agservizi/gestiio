@if($messaggi->isEmpty())
    <div class="h-100 d-flex align-items-center justify-content-center text-muted fs-6 py-10">
        Nessun messaggio in questa conversazione.
    </div>
@else
    @foreach($messaggi as $messaggio)
        @php($mio = (int)$messaggio->user_id === (int)Auth::id())
        <div class="d-flex mb-4 {{$mio ? 'justify-content-end' : 'justify-content-start'}}">
            <div class="rounded px-4 py-3 {{$mio ? 'bg-light-primary' : 'bg-light'}}" style="max-width: 75%;">
                <div class="fw-bolder fs-8 text-gray-700 mb-1">{{$mio ? 'Tu' : ($messaggio->mittente?->nominativo() ?? 'Utente')}}</div>
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
                <div class="text-muted fs-8 mt-2 text-end">{{$messaggio->created_at?->format('d/m/Y H:i')}}</div>
            </div>
        </div>
    @endforeach
@endif
