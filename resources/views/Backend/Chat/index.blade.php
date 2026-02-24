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

        /* Stato online/offline */
        .chat-online-dot {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        .chat-online-dot.online { background: #50cd89; box-shadow: 0 0 0 2px rgba(80,205,137,.3); }
        .chat-online-dot.offline { background: #b5b5c3; }

        /* Header stato online */
        #chat-header-online-status {
            font-size: 0.78rem;
            margin-left: 8px;
        }

        /* Hover azioni sui messaggi */
        .chat-bubble-wrap:hover .chat-msg-actions {
            opacity: 1 !important;
        }

        /* Reply banner */
        #chat-reply-banner {
            background: #f1f1f2;
            border-left: 3px solid #009ef7;
            border-radius: 0 6px 6px 0;
        }

        /* Reaction picker popup */
        .chat-reaction-picker {
            position: absolute;
            z-index: 10;
            background: #fff;
            border: 1px solid #e4e6ef;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,.12);
            padding: 4px;
            display: flex;
            gap: 2px;
        }
        .chat-reaction-picker button {
            font-size: 1.1rem;
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 3px 5px;
            border-radius: 4px;
            transition: background .15s;
        }
        .chat-reaction-picker button:hover {
            background: #f1f1f2;
        }

        /* Drag & drop overlay */
        #chat-dropzone-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 158, 247, 0.08);
            border: 2px dashed #009ef7;
            border-radius: 8px;
            z-index: 20;
            display: none;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        #chat-dropzone-overlay.active {
            display: flex;
        }

        /* Ricerca */
        #chat-search-results {
            max-height: 300px;
            overflow-y: auto;
        }

        #chat-mention-suggest {
            position: absolute;
            z-index: 30;
            background: #fff;
            border: 1px solid #e4e6ef;
            border-radius: 8px;
            min-width: 220px;
            max-height: 220px;
            overflow-y: auto;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .12);
        }

        #chat-mention-suggest .chat-mention-item {
            padding: 6px 10px;
            cursor: pointer;
            font-size: .85rem;
        }

        #chat-mention-suggest .chat-mention-item:hover {
            background: #f1f1f2;
        }

        .chat-mention-link {
            cursor: pointer;
            text-decoration: underline;
        }

        .chat-layout {
            height: calc(100vh - 230px);
        }

        .chat-layout .card {
            height: 100%;
        }

        #chat-threads {
            height: 100%;
            overflow-y: auto;
        }

        #chat-messages-wrap {
            min-height: 0;
        }

        #chat-messages {
            height: 100%;
            max-height: none !important;
        }

        #chat-send-form {
            flex-shrink: 0;
        }

        @media (max-width: 991.98px) {
            .chat-layout {
                height: auto;
            }

            #chat-messages {
                min-height: 42vh;
            }
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
    <button type="button" class="btn btn-sm btn-light-info ms-3" id="chat-search-toggle" title="Cerca messaggi">
        <i class="fas fa-search"></i> Cerca
    </button>
@endsection

@section('content')
    @include('Backend._components.alertErrori')

    <div class="row g-5 chat-layout">
        <div class="col-12 col-lg-4 col-xl-3 h-100">
            <div class="card h-100">
                <div class="card-header border-0 pt-5 pb-3">
                    <h3 class="card-title align-items-start flex-column m-0">
                        <span class="card-label fw-bolder fs-4">Conversazioni</span>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div id="chat-threads" class="list-group list-group-flush">
                        @include('Backend.Chat._threads', ['threads' => $threads, 'threadAttivo' => $threadAttivo])
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8 col-xl-9 h-100">
            <div class="card h-100 overflow-hidden">
                <div class="card-header border-0 pt-5 pb-3">
                    <h3 class="card-title m-0 fw-bolder fs-4">
                        @if($threadAttivo && $threadAttivo->getRelation('altroPartecipante'))
                            Chat con {{$threadAttivo->getRelation('altroPartecipante')->nominativo()}}
                            <span id="chat-header-online-status" class="fw-normal text-muted"></span>
                        @else
                            Chat interna
                        @endif
                    </h3>
                </div>
                <div class="card-body d-flex flex-column pt-0 px-5 pb-5 overflow-hidden">
                    {{-- Pannello ricerca --}}
                    <div id="chat-search-panel" class="d-none mb-3">
                        <div class="input-group input-group-sm">
                            <input type="text" id="chat-search-input" class="form-control" placeholder="Cerca nei messaggi...">
                            <button type="button" class="btn btn-light-info" id="chat-search-btn"><i class="fas fa-search"></i></button>
                            <button type="button" class="btn btn-light" id="chat-search-close"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-md-3"><input type="date" id="chat-search-from" class="form-control form-control-sm"></div>
                            <div class="col-md-3"><input type="date" id="chat-search-to" class="form-control form-control-sm"></div>
                            <div class="col-md-3"><select id="chat-search-priority" class="form-select form-select-sm"><option value="">Priorità</option><option value="2">Alta</option><option value="1">Media</option><option value="0">Normale</option></select></div>
                            <div class="col-md-3 d-flex align-items-center gap-3">
                                <label class="form-check form-check-sm m-0"><input class="form-check-input" type="checkbox" id="chat-search-attachments"><span class="form-check-label">Allegati</span></label>
                                <label class="form-check form-check-sm m-0"><input class="form-check-input" type="checkbox" id="chat-search-favorites"><span class="form-check-label">Preferiti</span></label>
                                <label class="form-check form-check-sm m-0"><input class="form-check-input" type="checkbox" id="chat-search-mentions"><span class="form-check-label">Menzioni</span></label>
                            </div>
                        </div>
                        <div id="chat-search-results" class="mt-2"></div>
                    </div>

                    <div id="chat-forward-toolbar" class="d-none mb-2 p-2 bg-light-primary rounded d-flex align-items-center justify-content-between">
                        <div class="fs-8 fw-bold">Messaggi selezionati: <span id="chat-forward-count">0</span></div>
                        <div class="d-flex align-items-center gap-2">
                            <select id="chat-forward-target" class="form-select form-select-sm w-200px"></select>
                            <button type="button" class="btn btn-sm btn-primary" id="chat-forward-send">Inoltra</button>
                            <button type="button" class="btn btn-sm btn-light" id="chat-forward-cancel">Annulla</button>
                        </div>
                    </div>

                    <div id="chat-pinned-panel" class="mb-2 p-2 bg-light rounded">
                        <div class="fw-bold fs-8 mb-1">Messaggi in evidenza</div>
                        <div id="chat-pinned-content">
                            @include('Backend.Chat._pinned', ['pinnedMessages' => $pinnedMessages ?? collect()])
                        </div>
                    </div>

                    {{-- Reply banner --}}
                    <div id="chat-reply-banner" class="d-none px-3 py-2 mb-2 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold text-primary fs-8" id="chat-reply-author"></div>
                            <div class="text-gray-600 fs-8" id="chat-reply-text"></div>
                        </div>
                        <button type="button" class="btn btn-icon btn-sm btn-light" id="chat-reply-close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div id="chat-messages-wrap" class="position-relative flex-grow-1">
                        <div id="chat-dropzone-overlay">
                            <span class="fw-bold text-primary fs-5">Rilascia i file qui</span>
                        </div>
                        <div id="chat-messages" class="overflow-auto pe-2 px-2 py-2">
                            @include('Backend.Chat._messages', ['messaggi' => $messaggi, 'altroLastReadAt' => $altroLastReadAt])
                        </div>
                    </div>
                    <div id="chat-typing-indicator" class="text-muted fs-8 mt-3 d-none">
                        <span class="fw-bold" id="chat-typing-name"></span> sta scrivendo
                        <span class="typing-dots" aria-hidden="true"><span></span><span></span><span></span></span>
                    </div>

                    <form id="chat-send-form" class="pt-5 border-top mt-5">
                        @csrf
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <button type="button" id="chat-emoji-toggle" class="btn btn-sm btn-light-primary">😊 Emoticon</button>
                            <select id="chat-template-select" class="form-select form-select-sm w-250px">
                                <option value="">Template rapidi...</option>
                                @foreach($quickTemplates as $template)
                                    <option value="{{e($template->contenuto)}}">{{$template->titolo}}</option>
                                @endforeach
                            </select>
                            <button type="button" id="chat-template-save" class="btn btn-sm btn-light-info">Salva template</button>
                            <select id="chat-priority" class="form-select form-select-sm w-150px">
                                <option value="0">Priorità normale</option>
                                <option value="1">Priorità media</option>
                                <option value="2">Priorità alta</option>
                            </select>
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
                                <div id="chat-mention-suggest" class="d-none"></div>
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

    <div style="display:none" aria-hidden="true">
        Sound Effect by <a href="https://pixabay.com/it/users/universfield-28281460/?utm_source=link-attribution&utm_medium=referral&utm_campaign=music&utm_content=485897">Universfield</a> from <a href="https://pixabay.com/sound-effects//?utm_source=link-attribution&utm_medium=referral&utm_campaign=music&utm_content=485897">Pixabay</a>
    </div>
@endsection

@push('customScript')
    <script>
        $(function () {
            const authUserId = @json((int) Auth::id());
            let activeThreadId = @json($threadAttivo?->id);
            let ultimoTotaleNonLetti = parseInt($('.js-chat-unread-total').first().text() || '0', 10);
            let lastNotifiedMessageId = @json((int) ($lastNotificationMessageId ?? 0));
            const chatBaseUrl = @json(rtrim(action([$controller, 'index']), '/'));
            const pollUrl = @json(action([$controller, 'poll']));
            const searchApiUrl = @json(action([$controller, 'search']));
            let typingTimeout = null;
            let typingSent = false;
            let replyToId = null;
            let oldestLoadedMessageId = null;
            let hasMoreHistory = true;
            let loadingHistory = false;
            let selectedForwardIds = [];
            let activeLastMessageId = null;
            const mentionUsers = @json($mentionUsers ?? []);

            /* ================= SUONO NOTIFICA ================= */
            const notificationSound = new Audio('/sound/universfield-new-notification-036-485897.mp3');
            notificationSound.preload = 'auto';
            let notificationSoundUnlocked = false;

            function unlockNotificationSound() {
                if (notificationSoundUnlocked) return;

                const tryUnlock = function () {
                    if (notificationSoundUnlocked) return;
                    notificationSound.muted = true;
                    notificationSound.currentTime = 0;

                    const maybePromise = notificationSound.play();
                    if (maybePromise && typeof maybePromise.then === 'function') {
                        maybePromise.then(function () {
                            notificationSound.pause();
                            notificationSound.currentTime = 0;
                            notificationSound.muted = false;
                            notificationSoundUnlocked = true;
                        }).catch(function () {
                            notificationSound.muted = false;
                        });
                    } else {
                        notificationSound.pause();
                        notificationSound.currentTime = 0;
                        notificationSound.muted = false;
                        notificationSoundUnlocked = true;
                    }
                };

                $(document).one('click', tryUnlock);
                $(document).one('keydown', tryUnlock);
                $(document).one('touchstart', tryUnlock);
            }

            function playNotificationSound() {
                try { notificationSound.currentTime = 0; notificationSound.play().catch(()=>{}); } catch(e) {}
            }

            /* ================= URL HELPERS ================= */
            function messagesUrl(threadId) { return chatBaseUrl + '/' + threadId + '/messages'; }
            function sendUrl(threadId) { return chatBaseUrl + '/' + threadId + '/messages'; }
            function typingUrl(threadId) { return chatBaseUrl + '/' + threadId + '/typing'; }
            function closeThreadUrl(threadId) { return chatBaseUrl + '/' + threadId + '/close'; }
            function muteThreadUrl(threadId) { return chatBaseUrl + '/' + threadId + '/mute'; }
            function forwardUrl(threadId) { return chatBaseUrl + '/' + threadId + '/forward'; }
            function reactionUrl(msgId) { return chatBaseUrl.replace(/\/chat-interna$/, '/chat-interna/message/' + msgId + '/reaction'); }
            function pinUrl(msgId) { return chatBaseUrl.replace(/\/chat-interna$/, '/chat-interna/message/' + msgId + '/pin'); }
            function favoriteUrl(msgId) { return chatBaseUrl.replace(/\/chat-interna$/, '/chat-interna/message/' + msgId + '/favorite'); }
            function messageUrl(msgId) { return chatBaseUrl.replace(/\/chat-interna$/, '/chat-interna/message/' + msgId); }
            function templatesUrl() { return chatBaseUrl.replace(/\/chat-interna$/, '/chat-interna/templates'); }
            function resolveMentionUrl() { return chatBaseUrl.replace(/\/chat-interna$/, '/chat-interna/mention/resolve'); }
            function vapidPublicKeyUrl() { return chatBaseUrl.replace(/\/chat-interna$/, '/chat-interna/push/vapid-public-key'); }
            function pushSubscribeUrl() { return chatBaseUrl.replace(/\/chat-interna$/, '/chat-interna/push/subscribe'); }
            function pushUnsubscribeUrl() { return chatBaseUrl.replace(/\/chat-interna$/, '/chat-interna/push/unsubscribe'); }

            function urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                const rawData = atob(base64);
                const outputArray = new Uint8Array(rawData.length);
                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            }

            async function initWebPushNotifications() {
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    return;
                }

                try {
                    const vapidResponse = await $.get(vapidPublicKeyUrl());
                    if (!vapidResponse || !vapidResponse.configured || !vapidResponse.publicKey) {
                        return;
                    }

                    const registration = await navigator.serviceWorker.register('/sw-chat-push.js');

                    let permission = Notification.permission;
                    if (permission === 'default') {
                        permission = await Notification.requestPermission();
                    }

                    if (permission !== 'granted') {
                        const existing = await registration.pushManager.getSubscription();
                        if (existing) {
                            await $.post(pushUnsubscribeUrl(), {
                                _token: $('meta[name="_token"]').attr('content'),
                                endpoint: existing.endpoint,
                            });
                        }
                        return;
                    }

                    let subscription = await registration.pushManager.getSubscription();
                    if (!subscription) {
                        subscription = await registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(vapidResponse.publicKey),
                        });
                    }

                    const payload = subscription.toJSON();
                    await $.post(pushSubscribeUrl(), {
                        _token: $('meta[name="_token"]').attr('content'),
                        endpoint: payload.endpoint,
                        keys: payload.keys,
                        contentEncoding: 'aesgcm',
                    });
                } catch (error) {
                    console.warn('Web push init failed', error);
                }
            }

            function showDesktopNotification(notif) {
                if (!('Notification' in window) || Notification.permission !== 'granted') {
                    return;
                }

                if (!document.hidden) {
                    return;
                }

                try {
                    const desktop = new Notification((notif.sender || 'Utente') + ' · ' + (notif.thread_name || 'Conversazione'), {
                        body: notif.excerpt || 'Nuovo messaggio in chat',
                        icon: '/images/logo_small_icon_only.png',
                        tag: 'chat-thread-' + (notif.thread_id || 'generic'),
                    });

                    desktop.onclick = function () {
                        window.focus();
                        const threadId = parseInt(notif.thread_id || 0, 10);
                        if (threadId > 0) {
                            loadMessages(threadId, true);
                        }
                    };
                } catch (error) {
                    console.warn('Desktop notification error', error);
                }
            }

            /* ================= UTILITY ================= */
            function scrollToBottom() {
                const box = $('#chat-messages');
                box.scrollTop(box[0].scrollHeight);
            }

            function showMessagesLoading() {
                $('#chat-messages').html(
                    '<div class="h-100 d-flex align-items-center justify-content-center text-muted fs-6 py-10">' +
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                    'Caricamento conversazione...' +
                    '</div>'
                );
            }

            function captureMessagesScrollState() {
                const box = $('#chat-messages')[0];
                if (!box) return null;

                const distanceFromBottom = box.scrollHeight - (box.scrollTop + box.clientHeight);
                return {
                    top: box.scrollTop,
                    height: box.scrollHeight,
                    nearBottom: distanceFromBottom <= 90,
                };
            }

            function restoreMessagesScrollState(state) {
                if (!state) return;

                const $box = $('#chat-messages');
                const box = $box[0];
                if (!box) return;

                const deltaHeight = box.scrollHeight - state.height;
                $box.scrollTop(Math.max(0, state.top + deltaHeight));
            }

            function syncThreadActiveStyles() {
                $('.chat-thread-item').each(function () {
                    const $item = $(this);
                    const isActive = $item.hasClass('active');

                    $item.find('.chat-thread-title')
                        .toggleClass('text-white', isActive)
                        .toggleClass('text-gray-900', !isActive);

                    $item.find('.chat-thread-preview')
                        .toggleClass('text-white', isActive)
                        .toggleClass('opacity-75', isActive)
                        .toggleClass('text-gray-700', !isActive);

                    $item.find('.chat-thread-time')
                        .toggleClass('text-white', isActive)
                        .toggleClass('opacity-75', isActive)
                        .toggleClass('text-muted', !isActive);

                    $item.find('.chat-thread-action-btn')
                        .toggleClass('btn-light', isActive)
                        .toggleClass('btn-light-secondary', !isActive);
                });
            }

            function setComposerEnabled(enabled) {
                $('#chat-messaggio').prop('disabled', !enabled);
                $('#chat-send-button').prop('disabled', !enabled);
                $('#chat-emoji-toggle').prop('disabled', !enabled);
                $('#chat-allegati').prop('disabled', !enabled);
            }

            function handleChatForbidden(error) {
                const isForbidden = parseInt(error?.status || 0, 10) === 403;
                if (!isForbidden) return false;

                const backendMessage = error?.responseJSON?.message || 'Conversazione non consentita: verifica i servizi attivi assegnati nel profilo.';

                Swal.fire({
                    icon: 'warning',
                    title: 'Conversazione bloccata',
                    text: backendMessage,
                });

                activeThreadId = null;
                const nextUrl = new URL(window.location.href);
                nextUrl.searchParams.delete('thread');
                window.history.replaceState({}, '', nextUrl.toString());
                loadMessages(null, false);
                refreshPoll();

                return true;
            }

            function insertAtCursor(el, text) {
                const start = el.selectionStart ?? el.value.length;
                const end = el.selectionEnd ?? el.value.length;
                el.value = el.value.substring(0, start) + text + el.value.substring(end);
                const pos = start + text.length;
                el.selectionStart = el.selectionEnd = pos;
                el.focus();
            }

            function hideMentionSuggest() {
                $('#chat-mention-suggest').addClass('d-none').empty();
            }

            function renderMentionSuggest(matches, replaceToken) {
                const $box = $('#chat-mention-suggest');
                if (!matches.length) {
                    hideMentionSuggest();
                    return;
                }

                $box.empty();
                matches.slice(0, 6).forEach(function (user) {
                    $box.append('<div class="chat-mention-item" data-tag="' + $('<span>').text(user.tag).html() + '" data-replace="' + $('<span>').text(replaceToken).html() + '">@' + $('<span>').text(user.tag).html() + ' <span class="text-muted">(' + $('<span>').text(user.name).html() + ')</span></div>');
                });
                $box.removeClass('d-none');
            }

            function suggestMentions(textarea) {
                const cursorPos = textarea.selectionStart ?? textarea.value.length;
                const before = textarea.value.slice(0, cursorPos);
                const match = before.match(/(?:^|\s)@([a-zA-Z0-9._-]{1,40})$/);

                if (!match) {
                    hideMentionSuggest();
                    return;
                }

                const needle = (match[1] || '').toLowerCase();
                const replaceToken = '@' + match[1];
                const matches = mentionUsers.filter(function (u) {
                    const tag = (u.tag || '').toLowerCase();
                    const name = (u.name || '').toLowerCase();
                    return tag.includes(needle) || name.includes(needle);
                });

                renderMentionSuggest(matches, replaceToken);
            }

            /* ================= TYPING STATUS ================= */
            function sendTypingStatus(isTyping) {
                if (!activeThreadId) return;
                if (isTyping === typingSent) return;
                typingSent = isTyping;
                $.post(typingUrl(activeThreadId), {
                    _token: $('meta[name="_token"]').attr('content'),
                    typing: isTyping ? 1 : 0,
                });
            }

            function scheduleTypingOff() {
                if (typingTimeout) clearTimeout(typingTimeout);
                typingTimeout = setTimeout(function () {
                    sendTypingStatus(false);
                }, 4500);
            }

            /* ================= REPLY ================= */
            function setReply(msgId, author, text) {
                replyToId = msgId;
                $('#chat-reply-author').text(author);
                $('#chat-reply-text').text(text);
                $('#chat-reply-banner').removeClass('d-none');
                $('#chat-messaggio').focus();
            }

            function clearReply() {
                replyToId = null;
                $('#chat-reply-banner').addClass('d-none');
                $('#chat-reply-author').text('');
                $('#chat-reply-text').text('');
            }

            function renderForwardTargets() {
                const $select = $('#chat-forward-target');
                $select.html('<option value="">Seleziona chat...</option>');
                $('.chat-thread-item').each(function () {
                    const id = $(this).data('thread-id');
                    const label = $(this).find('.fw-bolder').text().trim();
                    if (!id) return;
                    $select.append('<option value="' + id + '">' + $('<span>').text(label).html() + '</option>');
                });
            }

            function appendHistory(html) {
                const $box = $('#chat-messages');
                const prevHeight = $box[0].scrollHeight;
                $box.prepend(html);
                const newHeight = $box[0].scrollHeight;
                $box.scrollTop(newHeight - prevHeight + $box.scrollTop());
            }

            /* ================= LOAD MESSAGES ================= */
            function loadMessages(threadId, pushState = false, beforeId = null) {
                if (!threadId) {
                    $('#chat-messages').html('<div class="h-100 d-flex align-items-center justify-content-center text-muted fs-6 py-10">Seleziona o crea una conversazione.</div>');
                    setComposerEnabled(false);
                    $('.chat-thread-item').removeClass('active');
                    syncThreadActiveStyles();
                    oldestLoadedMessageId = null;
                    hasMoreHistory = false;
                    activeLastMessageId = null;
                    return;
                }

                const payload = {};
                if (beforeId) payload.before_id = beforeId;

                $.get(messagesUrl(threadId), payload, function (response) {
                    activeThreadId = threadId;
                    if (response.ultimoId !== undefined && response.ultimoId !== null) {
                        activeLastMessageId = parseInt(response.ultimoId, 10) || null;
                    }
                    if (response.isPrepend) {
                        appendHistory(response.html);
                    } else {
                        $('#chat-messages').html(response.html);
                    }
                    oldestLoadedMessageId = response.oldestId || oldestLoadedMessageId;
                    hasMoreHistory = !!response.hasMore;
                    $('.chat-thread-item').removeClass('active');
                    $('.chat-thread-item[data-thread-id="' + threadId + '"]').addClass('active');
                    syncThreadActiveStyles();
                    setComposerEnabled(true);
                    sendTypingStatus(false);
                    clearReply();
                    if (!response.isPrepend) {
                        scrollToBottom();
                    }
                    if (response.pinnedHtml !== undefined) {
                        $('#chat-pinned-content').html(response.pinnedHtml);
                    }
                    renderForwardTargets();

                    if (pushState) {
                        const nextUrl = new URL(window.location.href);
                        nextUrl.searchParams.set('thread', threadId);
                        window.history.replaceState({}, '', nextUrl.toString());
                    }
                }).fail(function (error) {
                    if (handleChatForbidden(error)) {
                        return;
                    }

                    const erroreBackend = error?.responseJSON?.message || 'Caricamento conversazione non riuscito';
                    Swal.fire('Errore', erroreBackend, 'error');
                });
            }

            /* ================= POLLING ================= */
            function refreshPoll() {
                $.get(pollUrl, {thread_id: activeThreadId}, function (response) {
                    if (response.threadsHtml !== undefined) {
                        $('#chat-threads').html(response.threadsHtml);
                        if (activeThreadId) {
                            $('.chat-thread-item').removeClass('active');
                            $('.chat-thread-item[data-thread-id="' + activeThreadId + '"]').addClass('active');
                        }
                        syncThreadActiveStyles();
                        renderForwardTargets();
                    }
                    if (activeThreadId && response.messaggiHtml !== undefined && !loadingHistory) {
                        const incomingLastId = parseInt(response.activeLastMessageId || 0, 10) || null;
                        const incomingSenderId = parseInt(response.activeLastMessageSenderId || 0, 10) || null;
                        const scrollState = captureMessagesScrollState();

                        $('#chat-messages').html(response.messaggiHtml);
                        if (scrollState && !scrollState.nearBottom) {
                            restoreMessagesScrollState(scrollState);
                        } else {
                            scrollToBottom();
                        }

                        if (incomingLastId) {
                            activeLastMessageId = incomingLastId;
                        }
                    }
                    if (response.nonLettiTotali !== undefined) {
                        const nuovoTotale = parseInt(response.nonLettiTotali, 10) || 0;
                        $('.js-chat-unread-total').text(nuovoTotale);
                        if (nuovoTotale > 0) {
                            $('.js-chat-unread-wrap').removeClass('d-none');
                        } else {
                            $('.js-chat-unread-wrap').addClass('d-none');
                        }
                        ultimoTotaleNonLetti = nuovoTotale;
                    }

                    if (response.notificationMessage && response.notificationMessage.id) {
                        const notif = response.notificationMessage;
                        const notifId = parseInt(notif.id, 10) || 0;
                        const notifSenderId = parseInt(notif.sender_id || 0, 10) || null;

                        if (notifId > lastNotifiedMessageId && notifSenderId !== authUserId) {
                            playNotificationSound();
                            showDesktopNotification(notif);
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'info',
                                title: (notif.sender || 'Utente') + ' · ' + (notif.thread_name || 'Conversazione'),
                                text: notif.excerpt || 'Nuovo messaggio',
                                showConfirmButton: false,
                                timer: 3800,
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                    toast.style.cursor = 'pointer';
                                    toast.title = 'Apri conversazione';
                                    toast.addEventListener('click', function () {
                                        const threadId = parseInt(notif.thread_id || 0, 10);
                                        if (threadId > 0) {
                                            loadMessages(threadId, true);
                                        }
                                        Swal.close();
                                    });
                                },
                            });
                        }

                        if (notifId > lastNotifiedMessageId) {
                            lastNotifiedMessageId = notifId;
                        }
                    }

                    if (response.pinnedHtml !== undefined) {
                        $('#chat-pinned-content').html(response.pinnedHtml);
                    }

                    // Typing
                    if (response.typing !== undefined) {
                        if (response.typing.active) {
                            $('#chat-typing-name').text(response.typing.name || 'Utente');
                            $('#chat-typing-indicator').removeClass('d-none');
                        } else {
                            $('#chat-typing-indicator').addClass('d-none');
                            $('#chat-typing-name').text('');
                        }
                    }

                    // Online status nell'header
                    if (response.altroOnline !== undefined) {
                        const $onlineEl = $('#chat-header-online-status');
                        if (response.altroOnline) {
                            $onlineEl.html('<span class="chat-online-dot online"></span> online');
                        } else {
                            $onlineEl.html('<span class="chat-online-dot offline"></span> offline');
                        }
                        if (response.threadMuted) {
                            $onlineEl.append(' <span class="ms-2">🔕 silenziata</span>');
                        }
                    }
                }).fail(function (error) {
                    if (handleChatForbidden(error)) {
                        return;
                    }
                });
            }

            /* ================= THREAD SELECTION ================= */
            $(document).on('click', '.chat-thread-item', function () {
                const canChat = parseInt($(this).data('chat-allowed') || 0, 10) === 1;
                if (!canChat) {
                    Swal.fire('Attenzione', 'Conversazione non disponibile: servizio non attivo o non più condiviso.', 'warning');
                    return;
                }
                const threadId = parseInt($(this).data('thread-id'), 10);
                if (threadId) {
                    activeThreadId = threadId;
                    $('.chat-thread-item').removeClass('active');
                    $('.chat-thread-item[data-thread-id="' + threadId + '"]').addClass('active');
                    syncThreadActiveStyles();
                    showMessagesLoading();
                }
                loadMessages(threadId, true);
            });

            $(document).on('click', '.chat-thread-close', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const threadId = parseInt($(this).data('thread-id'), 10);
                if (!threadId) return;

                $.post(closeThreadUrl(threadId), {
                    _token: $('meta[name="_token"]').attr('content')
                }, function () {
                    if (activeThreadId === threadId) {
                        activeThreadId = null;
                        const nextUrl = new URL(window.location.href);
                        nextUrl.searchParams.delete('thread');
                        window.history.replaceState({}, '', nextUrl.toString());
                        loadMessages(null, false);
                    }
                    refreshPoll();
                }).fail(function (error) {
                    const erroreBackend = error?.responseJSON?.message || 'Chiusura conversazione non riuscita';
                    Swal.fire('Errore', erroreBackend, 'error');
                });
            });

            $(document).on('click', '.chat-thread-mute', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const threadId = parseInt($(this).data('thread-id'), 10);
                if (!threadId) return;

                $.post(muteThreadUrl(threadId), {
                    _token: $('meta[name="_token"]').attr('content')
                }, function (response) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: response.muted ? 'Conversazione silenziata' : 'Conversazione riattivata',
                        showConfirmButton: false,
                        timer: 1800,
                    });
                    refreshPoll();
                });
            });

            /* ================= SEND MESSAGE ================= */
            $('#chat-send-form').on('submit', function (event) {
                event.preventDefault();
                if (!activeThreadId) return;

                const textarea = $('#chat-messaggio');
                const text = textarea.val().trim();
                const allegatiInput = $('#chat-allegati')[0];
                const allegatiCount = allegatiInput?.files?.length || 0;

                if (!text && allegatiCount === 0) return;

                const formData = new FormData(document.getElementById('chat-send-form'));
                formData.set('messaggio', text);
                formData.set('_token', $('meta[name="_token"]').attr('content'));

                if (replyToId) {
                    formData.set('reply_to_id', replyToId);
                }
                formData.set('priority', $('#chat-priority').val() || '0');

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
                        clearReply();
                        loadMessages(activeThreadId);
                        refreshPoll();
                    },
                    error: function (error) {
                        if (handleChatForbidden(error)) {
                            return;
                        }
                        console.error(error);
                        const erroreBackend = error?.responseJSON?.message || error?.responseText || 'Invio messaggio non riuscito';
                        Swal.fire('Errore', erroreBackend, 'error');
                    }
                });
            });

            /* ================= KEYBOARD ================= */
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

                suggestMentions(this);
            });

            $('#chat-messaggio').on('blur', function () {
                sendTypingStatus(false);
                setTimeout(hideMentionSuggest, 150);
            });

            $(document).on('click', '.chat-mention-item', function () {
                const mentionTag = $(this).data('tag');
                const replaceToken = $(this).data('replace');
                const textarea = document.getElementById('chat-messaggio');
                if (!textarea || !mentionTag || !replaceToken) return;

                const cursorPos = textarea.selectionStart ?? textarea.value.length;
                const before = textarea.value.slice(0, cursorPos);
                const after = textarea.value.slice(cursorPos);
                const idx = before.lastIndexOf(replaceToken);
                if (idx === -1) {
                    hideMentionSuggest();
                    return;
                }

                const nextBefore = before.slice(0, idx) + '@' + mentionTag + ' ';
                textarea.value = nextBefore + after;
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = nextBefore.length;
                $('#chat-messaggio').trigger('input');
                hideMentionSuggest();
            });

            $(document).on('click', '.chat-mention-link', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const tag = ($(this).data('tag') || '').toString().trim();
                if (!tag) return;

                $.post(resolveMentionUrl(), {
                    _token: $('meta[name="_token"]').attr('content'),
                    tag: tag,
                }, function (response) {
                    if (response && response.thread_id) {
                        loadMessages(parseInt(response.thread_id, 10), true);
                    }
                }).fail(function (error) {
                    const message = error?.responseJSON?.message || 'Impossibile aprire la chat della menzione';
                    Swal.fire('Attenzione', message, 'warning');
                });
            });

            /* ================= ALLEGATI ================= */
            $('#chat-allegati').on('change', function () {
                const count = this.files ? this.files.length : 0;
                if (count > 0) {
                    $('#chat-allegati-info').text(count + (count === 1 ? ' file selezionato' : ' file selezionati'));
                } else {
                    $('#chat-allegati-info').text('');
                }
            });

            /* ================= EMOJI PICKER (composizione) ================= */
            $('#chat-emoji-toggle').on('click', function () {
                $('#chat-emoji-panel').toggleClass('d-none');
            });

            $('#chat-template-select').on('change', function () {
                const templateText = $(this).val();
                if (!templateText) return;
                const textarea = document.getElementById('chat-messaggio');
                if (!textarea) return;
                insertAtCursor(textarea, (textarea.value ? "\n" : "") + templateText);
                $(this).val('');
                $('#chat-messaggio').trigger('input');
            });

            $('#chat-template-save').on('click', function () {
                const testo = ($('#chat-messaggio').val() || '').trim();
                if (!testo) {
                    Swal.fire('Attenzione', 'Scrivi prima un testo da salvare come template', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Titolo template',
                    input: 'text',
                    inputPlaceholder: 'es. Conferma appuntamento',
                    showCancelButton: true,
                }).then(function (result) {
                    if (!result.isConfirmed || !result.value) return;

                    $.post(templatesUrl(), {
                        _token: $('meta[name="_token"]').attr('content'),
                        titolo: result.value,
                        contenuto: testo,
                    }, function (response) {
                        if (response.template) {
                            $('#chat-template-select').append('<option value="' + $('<span>').text(response.template.contenuto).html() + '">' + $('<span>').text(response.template.titolo).html() + '</option>');
                        }
                        Swal.fire({toast: true, position: 'top-end', icon: 'success', title: 'Template salvato', showConfirmButton: false, timer: 1600});
                    });
                });
            });

            $(document).on('click', '.chat-emoji-item', function () {
                const emoji = $(this).text();
                const textarea = document.getElementById('chat-messaggio');
                if (textarea && !textarea.disabled) {
                    insertAtCursor(textarea, emoji);
                    $('#chat-messaggio').trigger('input');
                }
            });

            /* ================= LIGHTBOX IMMAGINI ================= */
            $(document).on('click', '.chat-image-preview', function (event) {
                event.preventDefault();
                const imageUrl = $(this).data('full');
                const imageName = $(this).data('name') || 'Anteprima immagine';
                if (!imageUrl) return;

                $('#chatImageModalTitle').text(imageName);
                $('#chatImageModalPreview').attr('src', imageUrl).attr('alt', imageName);
                const modalElement = document.getElementById('chatImageModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                modal.show();
            });

            /* ================= REPLY ================= */
            $(document).on('click', '.chat-reply-btn', function () {
                const msgId = $(this).data('msg-id');
                const author = $(this).data('author');
                const text = $(this).data('text');
                setReply(msgId, author, text);
            });

            $('#chat-reply-close').on('click', function () {
                clearReply();
            });

            /* ================= REAZIONI EMOJI ================= */
            const reactionEmojis = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

            $(document).on('click', '.chat-react-btn', function (e) {
                e.stopPropagation();
                // Rimuove picker precedenti
                $('.chat-reaction-picker').remove();

                const msgId = $(this).data('msg-id');
                const $btn = $(this);
                const $picker = $('<div class="chat-reaction-picker"></div>');

                reactionEmojis.forEach(function (emoji) {
                    $picker.append(
                        $('<button type="button"></button>')
                            .text(emoji)
                            .on('click', function () {
                                $.post(reactionUrl(msgId), {
                                    _token: $('meta[name="_token"]').attr('content'),
                                    emoji: emoji,
                                }, function () {
                                    $picker.remove();
                                    refreshPoll();
                                });
                            })
                    );
                });

                $btn.closest('.chat-bubble-wrap').append($picker);

                // Chiudi il picker cliccando altrove
                setTimeout(function () {
                    $(document).one('click', function () {
                        $picker.remove();
                    });
                }, 50);
            });

            // Toggle reaction esistente
            $(document).on('click', '.chat-reaction-toggle', function () {
                const msgId = $(this).data('msg-id');
                const emoji = $(this).data('emoji');
                $.post(reactionUrl(msgId), {
                    _token: $('meta[name="_token"]').attr('content'),
                    emoji: emoji,
                }, function () {
                    refreshPoll();
                });
            });

            $(document).on('click', '.chat-pin-btn', function () {
                const msgId = $(this).data('msg-id');
                $.post(pinUrl(msgId), {
                    _token: $('meta[name="_token"]').attr('content'),
                }, function () {
                    refreshPoll();
                });
            });

            $(document).on('click', '.chat-favorite-btn', function () {
                const msgId = $(this).data('msg-id');
                $.post(favoriteUrl(msgId), {
                    _token: $('meta[name="_token"]').attr('content'),
                }, function () {
                    refreshPoll();
                });
            });

            $(document).on('click', '.chat-edit-btn', function () {
                const msgId = $(this).data('msg-id');
                const oldText = $(this).data('text') || '';

                Swal.fire({
                    title: 'Modifica messaggio',
                    input: 'textarea',
                    inputValue: oldText,
                    showCancelButton: true,
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        type: 'PATCH',
                        url: messageUrl(msgId),
                        data: {
                            _token: $('meta[name="_token"]').attr('content'),
                            messaggio: result.value || '',
                        },
                        success: function () { refreshPoll(); },
                    });
                });
            });

            $(document).on('click', '.chat-delete-btn', function () {
                const msgId = $(this).data('msg-id');
                Swal.fire({
                    title: 'Eliminare messaggio?',
                    icon: 'warning',
                    showCancelButton: true,
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        type: 'DELETE',
                        url: messageUrl(msgId),
                        data: {
                            _token: $('meta[name="_token"]').attr('content'),
                        },
                        success: function () { refreshPoll(); },
                    });
                });
            });

            $(document).on('click', '.chat-forward-select-btn', function () {
                const msgId = parseInt($(this).data('msg-id'), 10);
                if (!msgId) return;

                if (selectedForwardIds.includes(msgId)) {
                    selectedForwardIds = selectedForwardIds.filter(function (id) { return id !== msgId; });
                    $(this).removeClass('btn-light-primary').addClass('btn-light');
                } else {
                    selectedForwardIds.push(msgId);
                    $(this).removeClass('btn-light').addClass('btn-light-primary');
                }

                $('#chat-forward-count').text(selectedForwardIds.length);
                $('#chat-forward-toolbar').toggleClass('d-none', selectedForwardIds.length === 0);
            });

            $('#chat-forward-cancel').on('click', function () {
                selectedForwardIds = [];
                $('#chat-forward-count').text('0');
                $('#chat-forward-toolbar').addClass('d-none');
                $('.chat-forward-select-btn').removeClass('btn-light-primary').addClass('btn-light');
            });

            $('#chat-forward-send').on('click', function () {
                const targetThread = parseInt($('#chat-forward-target').val(), 10);
                if (!activeThreadId || !targetThread || selectedForwardIds.length === 0) {
                    Swal.fire('Attenzione', 'Seleziona almeno un messaggio e la chat di destinazione', 'warning');
                    return;
                }

                $.post(forwardUrl(activeThreadId), {
                    _token: $('meta[name="_token"]').attr('content'),
                    target_thread_id: targetThread,
                    message_ids: selectedForwardIds,
                }, function () {
                    Swal.fire({toast: true, position: 'top-end', icon: 'success', title: 'Messaggi inoltrati', showConfirmButton: false, timer: 1800});
                    $('#chat-forward-cancel').trigger('click');
                });
            });

            $('#chat-messages').on('scroll', function () {
                if (!activeThreadId || loadingHistory || !hasMoreHistory) return;
                if ($(this).scrollTop() > 20) return;

                loadingHistory = true;
                loadMessages(activeThreadId, false, oldestLoadedMessageId);
                setTimeout(function () { loadingHistory = false; }, 500);
            });

            /* ================= RICERCA MESSAGGI ================= */
            $('#chat-search-toggle').on('click', function () {
                $('#chat-search-panel').toggleClass('d-none');
                if (!$('#chat-search-panel').hasClass('d-none')) {
                    $('#chat-search-input').focus();
                }
            });

            $('#chat-search-close').on('click', function () {
                $('#chat-search-panel').addClass('d-none');
                $('#chat-search-input').val('');
                $('#chat-search-results').html('');
            });

            function eseguiRicerca() {
                const q = $('#chat-search-input').val().trim();
                if (q.length > 0 && q.length < 2) return;

                $.get(searchApiUrl, {
                    q: q,
                    thread_id: activeThreadId || '',
                    date_from: $('#chat-search-from').val(),
                    date_to: $('#chat-search-to').val(),
                    with_attachments: $('#chat-search-attachments').is(':checked') ? 1 : 0,
                    favorites_only: $('#chat-search-favorites').is(':checked') ? 1 : 0,
                    mentions_only: $('#chat-search-mentions').is(':checked') ? 1 : 0,
                    priority: $('#chat-search-priority').val(),
                }, function (response) {
                    const $container = $('#chat-search-results');
                    $container.html('');

                    if (!response.risultati || response.risultati.length === 0) {
                        $container.html('<div class="text-muted fs-8 py-2">Nessun risultato trovato.</div>');
                        return;
                    }

                    response.risultati.forEach(function (r) {
                        const shortMsg = r.messaggio.length > 80 ? r.messaggio.substring(0, 80) + '...' : r.messaggio;
                        $container.append(
                            '<div class="chat-search-result border-bottom py-2 px-2 cursor-pointer" data-thread-id="' + r.thread_id + '" data-msg-id="' + r.id + '">' +
                            '<div class="fw-bold fs-8">' + $('<span>').text(r.mittente).html() + ' <span class="text-muted fw-normal">' + $('<span>').text(r.data).html() + '</span></div>' +
                            '<div class="fs-8 text-gray-700">' + $('<span>').text(shortMsg).html() + '</div>' +
                            '</div>'
                        );
                    });
                });
            }

            $('#chat-search-btn').on('click', eseguiRicerca);
            $('#chat-search-input').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    eseguiRicerca();
                }
            });

            $(document).on('click', '.chat-search-result', function () {
                const threadId = parseInt($(this).data('thread-id'), 10);
                const msgId = $(this).data('msg-id');

                // Naviga al thread se diverso
                if (threadId !== activeThreadId) {
                    loadMessages(threadId, true);
                }

                // Prova a scrollare al messaggio dopo un breve delay per loadMessages
                setTimeout(function () {
                    const $msg = $('.chat-msg-row[data-msg-id="' + msgId + '"]');
                    if ($msg.length) {
                        $msg[0].scrollIntoView({behavior: 'smooth', block: 'center'});
                        $msg.find('.chat-bubble-wrap').css('box-shadow', '0 0 0 2px #009ef7');
                        setTimeout(function () {
                            $msg.find('.chat-bubble-wrap').css('box-shadow', '');
                        }, 2000);
                    }
                }, 500);
            });

            $(document).on('click', '.chat-pinned-jump', function () {
                const msgId = $(this).data('msg-id');
                const $msg = $('.chat-msg-row[data-msg-id="' + msgId + '"]');
                if ($msg.length) {
                    $msg[0].scrollIntoView({behavior: 'smooth', block: 'center'});
                    $msg.find('.chat-bubble-wrap').css('box-shadow', '0 0 0 2px #009ef7');
                    setTimeout(function () {
                        $msg.find('.chat-bubble-wrap').css('box-shadow', '');
                    }, 1600);
                }
            });

            /* ================= DRAG & DROP ================= */
            const $messagesWrap = $('#chat-messages-wrap');
            const $dropOverlay = $('#chat-dropzone-overlay');
            let dragCounter = 0;

            $messagesWrap.on('dragenter', function (e) {
                e.preventDefault();
                dragCounter++;
                $dropOverlay.addClass('active');
            });

            $messagesWrap.on('dragleave', function (e) {
                e.preventDefault();
                dragCounter--;
                if (dragCounter <= 0) {
                    dragCounter = 0;
                    $dropOverlay.removeClass('active');
                }
            });

            $messagesWrap.on('dragover', function (e) {
                e.preventDefault();
            });

            $messagesWrap.on('drop', function (e) {
                e.preventDefault();
                dragCounter = 0;
                $dropOverlay.removeClass('active');

                if (!activeThreadId) return;

                const files = e.originalEvent.dataTransfer?.files;
                if (!files || files.length === 0) return;

                // Aggiungi i file al campo file
                const allegatiInput = $('#chat-allegati')[0];
                const dataTransfer = new DataTransfer();

                // Aggiungi file precedenti
                if (allegatiInput.files) {
                    for (let i = 0; i < allegatiInput.files.length; i++) {
                        dataTransfer.items.add(allegatiInput.files[i]);
                    }
                }

                // Aggiungi file droppati
                for (let i = 0; i < files.length; i++) {
                    dataTransfer.items.add(files[i]);
                }

                allegatiInput.files = dataTransfer.files;
                const count = dataTransfer.files.length;
                $('#chat-allegati-info').text(count + (count === 1 ? ' file selezionato' : ' file selezionati'));

                // Feedback visivo
                Swal.fire({
                    toast: true,
                    position: 'bottom-end',
                    icon: 'success',
                    title: count + ' file aggiunt' + (count === 1 ? 'o' : 'i'),
                    showConfirmButton: false,
                    timer: 2000,
                });
            });

            /* ================= INIT ================= */
            if (activeThreadId) {
                setComposerEnabled(true);
                scrollToBottom();
                const lastRenderedId = parseInt($('.chat-msg-row').last().data('msg-id') || 0, 10);
                if (lastRenderedId > 0) {
                    activeLastMessageId = lastRenderedId;
                }
            } else {
                setComposerEnabled(false);
            }

            renderForwardTargets();
            syncThreadActiveStyles();
            initWebPushNotifications();
            unlockNotificationSound();

            setInterval(refreshPoll, 3000);
        });
    </script>
@endpush
