@php($breadcrumbs=[])

@extends('Backend._layout._main')

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

                    <form id="chat-send-form" class="pt-5 border-top mt-5">
                        @csrf
                        <div class="d-flex gap-3 align-items-end">
                            <div class="flex-grow-1">
                                <label class="form-label fw-bold fs-7 mb-2">Messaggio</label>
                                <textarea name="messaggio" id="chat-messaggio" class="form-control" rows="3" placeholder="Scrivi qui..." {{$threadAttivo ? '' : 'disabled'}}></textarea>
                            </div>
                            <button type="submit" id="chat-send-button" class="btn btn-primary" {{$threadAttivo ? '' : 'disabled'}}>Invia</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('customScript')
    <script>
        $(function () {
            let activeThreadId = @json($threadAttivo?->id);
            const messagesUrlTemplate = @json(url('/chat-interna/THREAD_ID/messages'));
            const sendUrlTemplate = @json(url('/chat-interna/THREAD_ID/messages'));
            const pollUrl = @json(action([$controller, 'poll']));

            function messagesUrl(threadId) {
                return messagesUrlTemplate.replace('THREAD_ID', threadId);
            }

            function sendUrl(threadId) {
                return sendUrlTemplate.replace('THREAD_ID', threadId);
            }

            function scrollToBottom() {
                const box = $('#chat-messages');
                box.scrollTop(box[0].scrollHeight);
            }

            function setComposerEnabled(enabled) {
                $('#chat-messaggio').prop('disabled', !enabled);
                $('#chat-send-button').prop('disabled', !enabled);
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
                        $('.js-chat-unread-total').text(response.nonLettiTotali);
                        if (parseInt(response.nonLettiTotali, 10) > 0) {
                            $('.js-chat-unread-wrap').removeClass('d-none');
                        } else {
                            $('.js-chat-unread-wrap').addClass('d-none');
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
                if (!text) {
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: sendUrl(activeThreadId),
                    data: {
                        _token: $('meta[name="_token"]').attr('content'),
                        messaggio: text,
                    },
                    success: function () {
                        textarea.val('');
                        loadMessages(activeThreadId);
                        refreshPoll();
                    },
                    error: function (error) {
                        console.error(error);
                        Swal.fire('Errore', 'Invio messaggio non riuscito', 'error');
                    }
                });
            });

            if (activeThreadId) {
                setComposerEnabled(true);
                scrollToBottom();
            } else {
                setComposerEnabled(false);
            }

            setInterval(refreshPoll, 12000);
        });
    </script>
@endpush
