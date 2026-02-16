@php($breadcrumbs=[])

@extends('Backend._layout._main')

@push('customCss')
    <style>
        .typing-dots {
            display: inline-flex;
            gap: 4px;
            margin-left: 6px;
            transform: translateY(2px);
        }

        .typing-dots span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.35;
            animation: chatTypingDots 1.1s infinite;
        }

        .typing-dots span:nth-child(2) {
            animation-delay: 0.15s;
        }

        .typing-dots span:nth-child(3) {
            animation-delay: 0.3s;
        }

        .chat-image-thumb {
            max-width: 180px;
            max-height: 180px;
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            object-fit: cover;
            cursor: zoom-in;
        }

        @keyframes chatTypingDots {
            0%, 80%, 100% { opacity: 0.25; transform: translateY(0); }
            40% { opacity: 1; transform: translateY(-2px); }
        }
    </style>
@endpush

@section('toolbar')
    <form method="POST" action="{{action([$controller, 'storeThread'])}}" class="d-flex align-items-center gap-2">
        @csrf
        <select name="destinatario_id" class="form-select form-select-sm w-250px" required>
            <option value="">Nuova chat con...</option>
            @foreach($utentiDisponibili as $utente)
                <option value="{{$utente->id}}">{{$utente->nominativo()}}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Avvia</button>
    </form>
@endsection

@section('content')
    @include('Backend._components.alertErrori')

    <div class="row g-5">
        <div class="col-12 col-lg-4 col-xl-3">
            <div class="card h-100">
                <div class="card-header border-0 pt-5 pb-3">
                    <h3 class="card-title align-items-start flex-column m-0">
                        <span class="card-label fw-bolder fs-4">Conversazioni</span>
                        <span class="text-muted mt-1 fw-bold fs-7">Admin ↔ Agente/Supervisore</span>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div id="chat-threads" class="list-group list-group-flush">
                        @include('Backend.Chat._threads', ['threads' => $threads, 'threadAttivo' => $threadAttivo])
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8 col-xl-9">
            <div class="card" style="min-height: 70vh;">
                <div class="card-header border-0 pt-5 pb-3">
                    <h3 class="card-title m-0 fw-bolder fs-4">
                        @if($threadAttivo && $threadAttivo->getRelation('altroPartecipante'))
                            Chat con {{$threadAttivo->getRelation('altroPartecipante')->nominativo()}}
                        @else
                            Chat interna
                        @endif
                    </h3>
                </div>
                <div class="card-body d-flex flex-column pt-0">
                    <div id="chat-messages" class="flex-grow-1 overflow-auto pe-2" style="max-height: 56vh;">
                        @include('Backend.Chat._messages', ['messaggi' => $messaggi])
                    </div>
                    <div id="chat-typing-indicator" class="text-muted fs-8 mt-3 d-none">
                        <span class="fw-bold" id="chat-typing-name"></span> sta scrivendo
                        <span class="typing-dots" aria-hidden="true"><span></span><span></span><span></span></span>
                    </div>

                    <form id="chat-send-form" class="pt-5 border-top mt-5">
                        @csrf
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <button type="button" id="chat-emoji-toggle" class="btn btn-sm btn-light-primary">😊 Emoticon</button>
                            <div id="chat-emoji-panel" class="d-none">
                                <button type="button" class="btn btn-sm btn-light chat-emoji-item">😀</button>
                                <button type="button" class="btn btn-sm btn-light chat-emoji-item">😂</button>
                                <button type="button" class="btn btn-sm btn-light chat-emoji-item">😉</button>
                                <button type="button" class="btn btn-sm btn-light chat-emoji-item">😍</button>
                                <button type="button" class="btn btn-sm btn-light chat-emoji-item">👍</button>
                                <button type="button" class="btn btn-sm btn-light chat-emoji-item">🙏</button>
                                <button type="button" class="btn btn-sm btn-light chat-emoji-item">🎉</button>
                                <button type="button" class="btn btn-sm btn-light chat-emoji-item">🔥</button>
                            </div>
                        </div>
                        <div class="d-flex gap-3 align-items-end">
                            <div class="flex-grow-1">
                                <label class="form-label fw-bold fs-7 mb-2">Messaggio</label>
                                <textarea name="messaggio" id="chat-messaggio" class="form-control" rows="3" placeholder="Scrivi qui..." {{$threadAttivo ? '' : 'disabled'}}></textarea>
                                <div class="mt-3">
                                    <input type="file" id="chat-allegati" name="allegati[]" class="form-control form-control-sm" multiple {{$threadAttivo ? '' : 'disabled'}}>
                                    <div id="chat-allegati-info" class="text-muted fs-8 mt-1"></div>
                                </div>
                            </div>
                            <button type="submit" id="chat-send-button" class="btn btn-primary" {{$threadAttivo ? '' : 'disabled'}}>Invia</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="chatImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header py-3">
                    <h5 class="modal-title fs-6" id="chatImageModalTitle">Anteprima immagine</h5>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="chatImageModalPreview" src="" alt="Anteprima" class="img-fluid rounded" style="max-height: 75vh;">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('customScript')
    <script>
        $(function () {
            let activeThreadId = @json($threadAttivo?->id);
            let ultimoTotaleNonLetti = parseInt($('.js-chat-unread-total').first().text() || '0', 10);
            const chatBaseUrl = @json(rtrim(action([$controller, 'index']), '/'));
            const pollUrl = @json(action([$controller, 'poll']));
            let typingTimeout = null;
            let typingSent = false;

            function messagesUrl(threadId) {
                return chatBaseUrl + '/' + threadId + '/messages';
            }

            function sendUrl(threadId) {
                return chatBaseUrl + '/' + threadId + '/messages';
            }

            function typingUrl(threadId) {
                return chatBaseUrl + '/' + threadId + '/typing';
            }

            function scrollToBottom() {
                const box = $('#chat-messages');
                box.scrollTop(box[0].scrollHeight);
            }

            function setComposerEnabled(enabled) {
                $('#chat-messaggio').prop('disabled', !enabled);
                $('#chat-send-button').prop('disabled', !enabled);
                $('#chat-emoji-toggle').prop('disabled', !enabled);
                $('#chat-allegati').prop('disabled', !enabled);
            }

            function insertAtCursor(el, text) {
                const start = el.selectionStart ?? el.value.length;
                const end = el.selectionEnd ?? el.value.length;
                el.value = el.value.substring(0, start) + text + el.value.substring(end);
                const pos = start + text.length;
                el.selectionStart = el.selectionEnd = pos;
                el.focus();
            }

            function sendTypingStatus(isTyping) {
                if (!activeThreadId) {
                    return;
                }

                if (isTyping === typingSent) {
                    return;
                }

                typingSent = isTyping;
                $.post(typingUrl(activeThreadId), {
                    _token: $('meta[name="_token"]').attr('content'),
                    typing: isTyping ? 1 : 0,
                });
            }

            function scheduleTypingOff() {
                if (typingTimeout) {
                    clearTimeout(typingTimeout);
                }
                typingTimeout = setTimeout(function () {
                    sendTypingStatus(false);
                }, 4500);
            }

            function loadMessages(threadId, pushState = false) {
                if (!threadId) {
                    $('#chat-messages').html('<div class="h-100 d-flex align-items-center justify-content-center text-muted fs-6 py-10">Seleziona o crea una conversazione.</div>');
                    setComposerEnabled(false);
                    return;
                }

                $.get(messagesUrl(threadId), function (response) {
                    activeThreadId = threadId;
                    $('#chat-messages').html(response.html);
                    $('.chat-thread-item').removeClass('active');
                    $('.chat-thread-item[data-thread-id="' + threadId + '"]').addClass('active');
                    setComposerEnabled(true);
                    sendTypingStatus(false);
                    scrollToBottom();

                    if (pushState) {
                        const nextUrl = new URL(window.location.href);
                        nextUrl.searchParams.set('thread', threadId);
                        window.history.replaceState({}, '', nextUrl.toString());
                    }
                });
            }

            function refreshPoll() {
                $.get(pollUrl, {thread_id: activeThreadId}, function (response) {
                    if (response.threadsHtml !== undefined) {
                        $('#chat-threads').html(response.threadsHtml);
                    }
                    if (activeThreadId && response.messaggiHtml !== undefined) {
                        $('#chat-messages').html(response.messaggiHtml);
                        scrollToBottom();
                    }
                    if (response.nonLettiTotali !== undefined) {
                        const nuovoTotale = parseInt(response.nonLettiTotali, 10) || 0;
                        $('.js-chat-unread-total').text(nuovoTotale);
                        if (nuovoTotale > 0) {
                            $('.js-chat-unread-wrap').removeClass('d-none');
                        } else {
                            $('.js-chat-unread-wrap').addClass('d-none');
                        }

                        if (nuovoTotale > ultimoTotaleNonLetti) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'info',
                                title: 'Nuovo messaggio in chat',
                                showConfirmButton: false,
                                timer: 3500,
                                timerProgressBar: true,
                            });
                        }
                        ultimoTotaleNonLetti = nuovoTotale;
                    }

                    if (response.typing !== undefined) {
                        if (response.typing.active) {
                            $('#chat-typing-name').text(response.typing.name || 'Utente');
                            $('#chat-typing-indicator').removeClass('d-none');
                        } else {
                            $('#chat-typing-indicator').addClass('d-none');
                            $('#chat-typing-name').text('');
                        }
                    }
                });
            }

            $(document).on('click', '.chat-thread-item', function () {
                const threadId = parseInt($(this).data('thread-id'), 10);
                loadMessages(threadId, true);
            });

            $('#chat-send-form').on('submit', function (event) {
                event.preventDefault();

                if (!activeThreadId) {
                    return;
                }

                const textarea = $('#chat-messaggio');
                const text = textarea.val().trim();
                const allegatiInput = $('#chat-allegati')[0];
                const allegatiCount = allegatiInput?.files?.length || 0;

                if (!text && allegatiCount === 0) {
                    return;
                }

                const formData = new FormData(document.getElementById('chat-send-form'));
                formData.set('messaggio', text);
                formData.set('_token', $('meta[name="_token"]').attr('content'));

                $.ajax({
                    type: 'POST',
                    url: sendUrl(activeThreadId),
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function () {
                        textarea.val('');
                        $('#chat-allegati').val('');
                        $('#chat-allegati-info').text('');
                        sendTypingStatus(false);
                        loadMessages(activeThreadId);
                        refreshPoll();
                    },
                    error: function (error) {
                        console.error(error);
                        const erroreBackend = error?.responseJSON?.message || error?.responseText || 'Invio messaggio non riuscito';
                        Swal.fire('Errore', erroreBackend, 'error');
                    }
                });
            });

            $('#chat-messaggio').on('keydown', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    $('#chat-send-form').trigger('submit');
                }
            });

            $('#chat-messaggio').on('input', function () {
                const text = $(this).val().trim();
                if (text.length > 0) {
                    sendTypingStatus(true);
                    scheduleTypingOff();
                } else {
                    sendTypingStatus(false);
                }
            });

            $('#chat-messaggio').on('blur', function () {
                sendTypingStatus(false);
            });

            $('#chat-allegati').on('change', function () {
                const count = this.files ? this.files.length : 0;
                if (count > 0) {
                    $('#chat-allegati-info').text(count + (count === 1 ? ' file selezionato' : ' file selezionati'));
                } else {
                    $('#chat-allegati-info').text('');
                }
            });

            $('#chat-emoji-toggle').on('click', function () {
                $('#chat-emoji-panel').toggleClass('d-none');
            });

            $(document).on('click', '.chat-emoji-item', function () {
                const emoji = $(this).text();
                const textarea = document.getElementById('chat-messaggio');
                if (textarea && !textarea.disabled) {
                    insertAtCursor(textarea, emoji);
                    $('#chat-messaggio').trigger('input');
                }
            });

            $(document).on('click', '.chat-image-preview', function (event) {
                event.preventDefault();

                const imageUrl = $(this).data('full');
                const imageName = $(this).data('name') || 'Anteprima immagine';

                if (!imageUrl) {
                    return;
                }

                $('#chatImageModalTitle').text(imageName);
                $('#chatImageModalPreview').attr('src', imageUrl).attr('alt', imageName);

                const modalElement = document.getElementById('chatImageModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                modal.show();
            });

            if (activeThreadId) {
                setComposerEnabled(true);
                scrollToBottom();
            } else {
                setComposerEnabled(false);
            }

            setInterval(refreshPoll, 3000);
        });
    </script>
@endpush
