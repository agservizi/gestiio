<script>
    $(function () {
        const path = window.location.pathname || '';
        if (path.includes('/chat-interna')) {
            return;
        }

        const pollGlobalUrl = @json(action([\App\Http\Controllers\Backend\ChatController::class, 'pollGlobalNotifications']));
        const authUserId = @json((int) Auth::id());
        const soundUrl = '/sound/universfield-new-notification-036-485897.mp3';
        const storageKey = 'chat_last_notified_message_id_' + authUserId;

        const notificationSound = new Audio(soundUrl);
        notificationSound.preload = 'auto';

        let soundUnlocked = false;
        let lastNotifiedMessageId = parseInt(localStorage.getItem(storageKey) || '0', 10) || 0;

        function ensureDesktopPermissionOnInteraction() {
            if (!("Notification" in window)) return;
            if (Notification.permission !== 'default') return;

            const askPermission = function () {
                if (Notification.permission === 'default') {
                    Notification.requestPermission().catch(function () {});
                }
            };

            $(document).one('click', askPermission);
            $(document).one('keydown', askPermission);
            $(document).one('touchstart', askPermission);
        }

        function unlockSoundOnce() {
            if (soundUnlocked) return;

            const unlock = function () {
                if (soundUnlocked) return;
                notificationSound.muted = true;
                notificationSound.currentTime = 0;

                const maybePromise = notificationSound.play();
                if (maybePromise && typeof maybePromise.then === 'function') {
                    maybePromise.then(function () {
                        notificationSound.pause();
                        notificationSound.currentTime = 0;
                        notificationSound.muted = false;
                        soundUnlocked = true;
                    }).catch(function () {
                        notificationSound.muted = false;
                    });
                } else {
                    notificationSound.pause();
                    notificationSound.currentTime = 0;
                    notificationSound.muted = false;
                    soundUnlocked = true;
                }
            };

            $(document).one('click', unlock);
            $(document).one('keydown', unlock);
            $(document).one('touchstart', unlock);
        }

        function playNotificationSound() {
            try {
                notificationSound.currentTime = 0;
                notificationSound.play().catch(function () {});
            } catch (e) {
            }
        }

        function showToast(notif) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: (notif.sender || 'Utente') + ' · ' + (notif.thread_name || 'Conversazione'),
                text: notif.excerpt || 'Nuovo messaggio',
                showConfirmButton: false,
                timer: 3800,
                timerProgressBar: true,
                didOpen: function (toast) {
                    toast.style.cursor = 'pointer';
                    toast.title = 'Apri conversazione';
                    toast.addEventListener('click', function () {
                        const threadId = parseInt(notif.thread_id || 0, 10);
                        if (threadId > 0) {
                            window.location.href = '/backend/chat-interna?thread=' + threadId;
                        } else {
                            window.location.href = '/backend/chat-interna';
                        }
                    });
                },
            });
        }

        function showDesktopNotification(notif) {
            if (!("Notification" in window)) return;
            if (Notification.permission !== 'granted') return;
            if (!document.hidden) return;

            try {
                const desktop = new Notification((notif.sender || 'Utente') + ' · ' + (notif.thread_name || 'Conversazione'), {
                    body: notif.excerpt || 'Nuovo messaggio',
                    icon: '/images/logo_small_icon_only.png',
                    tag: 'chat-thread-' + (notif.thread_id || 'generic'),
                });

                desktop.onclick = function () {
                    window.focus();
                    const threadId = parseInt(notif.thread_id || 0, 10);
                    if (threadId > 0) {
                        window.location.href = '/backend/chat-interna?thread=' + threadId;
                    } else {
                        window.location.href = '/backend/chat-interna';
                    }
                };
            } catch (e) {
            }
        }

        function refreshGlobalChatNotifications() {
            $.get(pollGlobalUrl, {since_id: lastNotifiedMessageId}, function (response) {
                if (response.nonLettiTotali !== undefined) {
                    const totale = parseInt(response.nonLettiTotali, 10) || 0;
                    $('.js-chat-unread-total').text(totale);
                    if (totale > 0) {
                        $('.js-chat-unread-wrap').removeClass('d-none');
                    } else {
                        $('.js-chat-unread-wrap').addClass('d-none');
                    }
                }

                if (response.notificationMessage && response.notificationMessage.id) {
                    const notif = response.notificationMessage;
                    const notifId = parseInt(notif.id || 0, 10) || 0;
                    const senderId = parseInt(notif.sender_id || 0, 10) || 0;

                    if (notifId > lastNotifiedMessageId && senderId !== authUserId) {
                        playNotificationSound();
                        showToast(notif);
                        showDesktopNotification(notif);
                        lastNotifiedMessageId = notifId;
                        localStorage.setItem(storageKey, String(lastNotifiedMessageId));
                    }
                }
            });
        }

        unlockSoundOnce();
        ensureDesktopPermissionOnInteraction();
        refreshGlobalChatNotifications();
        setInterval(refreshGlobalChatNotifications, 3000);
    });
</script>
