@extends('Backend._layout._main')

@section('content')
    {{-- gestiio-pdf-tools-v5-no-desktop-bar --}}
    <style>
        #kt_app_content {
            padding: 0 !important;
            margin: 0 !important;
        }

        #kt_app_content_container {
            max-width: none !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .pdf-tools-wrap {
            position: relative;
            width: 100%;
            height: calc(100vh - 65px);
            overflow: hidden;
            background: #fff;
        }

        .pdf-tools-notice {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.65rem 1rem;
            background: #fff8e6;
            border-bottom: 1px solid #f0d78c;
            color: #6b5420;
            font-size: 0.875rem;
            line-height: 1.4;
            z-index: 3;
        }

        .pdf-tools-notice strong {
            color: #5a4518;
        }

        .pdf-tools-notice-close {
            margin-left: auto;
            border: 0;
            background: transparent;
            color: #6b5420;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
            padding: 0 0.25rem;
        }

        .pdf-tools-wrap.has-notice {
            height: calc(100vh - 65px);
            display: flex;
            flex-direction: column;
        }

        .pdf-tools-wrap.has-notice .pdf-tools-frame,
        .pdf-tools-wrap.has-notice .pdf-tools-boot {
            flex: 1;
            min-height: 0;
        }

        .pdf-tools-wrap.has-notice .pdf-tools-boot {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .pdf-tools-frame {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .pdf-tools-boot {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: #64748b;
            font-size: 0.95rem;
            z-index: 2;
        }

        @media (max-width: 991.98px) {
            .pdf-tools-wrap {
                height: calc(100vh - 55px);
            }
        }
    </style>

    <div class="pdf-tools-wrap {{ !empty($noStorageNotice) ? 'has-notice' : '' }}">
        @if (!empty($noStorageNotice))
            <div id="pdf-tools-notice" class="pdf-tools-notice" role="status">
                <div>
                    <strong>I file non vengono salvati sul server.</strong>
                    Elabora e scarica subito i risultati: non c’è archivio condiviso tra utenti (My Files disattivato).
                </div>
                <button type="button" class="pdf-tools-notice-close" id="pdf-tools-notice-close" aria-label="Chiudi avviso">&times;</button>
            </div>
        @endif
        <div id="pdf-tools-boot" class="pdf-tools-boot">Accesso automatico PDF Tools…</div>
        <iframe
            id="pdf-tools-iframe"
            src="about:blank"
            title="PDF Tools"
            class="pdf-tools-frame"
            referrerpolicy="same-origin"
            allow="clipboard-read; clipboard-write"
        ></iframe>
    </div>

    <script>
        (function () {
            var iframe = document.getElementById('pdf-tools-iframe');
            var boot = document.getElementById('pdf-tools-boot');
            var enterUrl = @json($enterUrl);
            var displayName = @json($displayName ?? '');
            var noticeClose = document.getElementById('pdf-tools-notice-close');
            if (displayName) {
                try { localStorage.setItem('gestiio_display_name', displayName); } catch (e) {}
            }
            if (noticeClose) {
                noticeClose.addEventListener('click', function () {
                    var n = document.getElementById('pdf-tools-notice');
                    if (n) n.remove();
                    var wrap = document.querySelector('.pdf-tools-wrap');
                    if (wrap) wrap.classList.remove('has-notice');
                });
            }
            if (!iframe) return;

            function forceHideUnavailable(win, doc) {
                try {
                    var key = 'stirlingpdf_preferences';
                    var prefs = {};
                    try {
                        prefs = JSON.parse(win.localStorage.getItem(key) || '{}') || {};
                    } catch (e) {}
                    prefs.hideUnavailableTools = false;
                    prefs.hideUnavailableConversions = false;
                    prefs.language = 'it-IT';
                    prefs.locale = 'it-IT';
                    prefs.languageCode = 'it-IT';
                    win.localStorage.setItem(key, JSON.stringify(prefs));
                    win.localStorage.setItem('i18nextLng', 'it-IT');
                    win.localStorage.setItem('i18nextLng-source', '3');
                    ['language', 'languageCode', 'lng', 'locale'].forEach(function (k) {
                        win.localStorage.setItem(k, 'it-IT');
                    });
                    if (win.i18next && win.i18next.changeLanguage) {
                        win.i18next.changeLanguage('it-IT');
                    }
                    if (displayName) {
                        try { win.localStorage.setItem('gestiio_display_name', displayName); } catch (e) {}
                        var initial = (displayName.replace(/^\s+/, '') || 'U').charAt(0).toUpperCase();
                        doc.querySelectorAll('span,div,p,button,a,li').forEach(function (el) {
                            if (el.children && el.children.length) return;
                            var t = (el.textContent || '').replace(/\s+/g, ' ').trim();
                            if (t === 'admin' || t === 'Admin' || t === 'gestiio' || /^gestiio-\d+$/i.test(t)) {
                                el.textContent = displayName;
                                el.setAttribute('title', displayName);
                            }
                        });
                        doc.querySelectorAll('[class*=avatar],[class*=Avatar],button').forEach(function (el) {
                            var t = (el.textContent || '').replace(/\s+/g, ' ').trim();
                            if (t === 'A' || t === 'a' || t === 'G') el.textContent = initial;
                        });
                    }
                } catch (e) {}

                var style = doc.getElementById('gestiio-hide-devtools');
                if (!style) {
                    style = doc.createElement('style');
                    style.id = 'gestiio-hide-devtools';
                    doc.head.appendChild(style);
                }
                style.textContent = [
                    '[data-tool-id="show-javascript"],',
                    '[data-tool-id="dev-api-docs"],',
                    '[data-tool-id="dev-folder-scanning-docs"],',
                    '[data-tool-id="dev-sso-guide-docs"],',
                    '[data-tool-id="dev-airgapped-docs"],',
                    'a[href*="show-javascript"],',
                    'a[href*="/api"],',
                    'a[href*="folder-scanning"],',
                    'a[href*="sso"],',
                    'a[href*="air-gapped"],',
                    'a[href*="airgapped"],',
                    'a[href*="stirlingpdf.com/pricing"],',
                    'a[href*="server-plan"],',
                    '[data-testid*="upgrade"],',
                    '[data-testid*="pricing"],',
                    '[class*="UpgradeBanner"],',
                    '[class*="upgrade-banner"],',
                    // My Files / archiviazione disattivata
                    'a[href*="/files"],',
                    'a[href*="my-files"],',
                    'button[aria-label*="My Files"],',
                    'button[aria-label*="I miei file"] { display: none !important; }'
                ].join('\\n');

                var nodes = doc.querySelectorAll('h1,h2,h3,h4,h5,h6,div,span,p,button');
                nodes.forEach(function (el) {
                    var text = (el.textContent || '').replace(/\\s+/g, ' ').trim().toUpperCase();
                    if (text !== 'STRUMENTI PER SVILUPPATORI' && text !== 'DEVELOPER TOOLS') {
                        return;
                    }
                    var section = el.closest('section, article, li, [class*="category"], [class*="group"], [class*="section"]') || el.parentElement;
                    if (section) {
                        section.style.setProperty('display', 'none', 'important');
                    }
                });

                // Chiudi "Salta per ora" / Skip e nascondi upsell licenza
                doc.querySelectorAll('button,[role=button],a').forEach(function (b) {
                    var label = (b.textContent || '').replace(/\\s+/g, ' ').trim();
                    if (/^(Salta per ora|Skip for now|Skip|Not now|Non ora)$/i.test(label)) {
                        try { b.click(); } catch (e) {}
                    }
                });

                doc.querySelectorAll('div,aside,section,[role=dialog],.modal').forEach(function (el) {
                    var t = (el.textContent || '').replace(/\\s+/g, ' ').trim();
                    if (/App Windows Stirling/i.test(t) && t.length < 400) {
                        el.style.setProperty('display', 'none', 'important');
                    }
                    if (/Welcome to Stirling V2|Licenza server|Open-Core|Upgrade to Server|Server Plan|posti illimitati|Vedi piani|See plans/i.test(t) && t.length < 2500) {
                        var dlg = el.closest('[role=dialog],.modal,[class*=Modal]') || el;
                        dlg.style.setProperty('display', 'none', 'important');
                    }
                    // Banner viola "Upgrade to Server Plan"
                    if (/Upgrade to Server Plan|Passa al piano Server|Upgrade to Server/i.test(t) && t.length < 200) {
                        var bar = el.closest('div,aside,header,section') || el;
                        bar.style.setProperty('display', 'none', 'important');
                    }
                });
            }

            function bind() {
                try {
                    var win = iframe.contentWindow;
                    var doc = iframe.contentDocument;
                    if (!win || !doc || !doc.body) return;
                    forceHideUnavailable(win, doc);
                    if (doc.__gestiioDevToolsObserver) return;
                    var observer = new MutationObserver(function () {
                        forceHideUnavailable(win, doc);
                    });
                    observer.observe(doc.body, { childList: true, subtree: true });
                    doc.__gestiioDevToolsObserver = observer;
                } catch (e) {}
            }

            function hideBoot() {
                if (boot) boot.style.display = 'none';
            }

            try {
                ['i18nextLng', 'language', 'languageCode', 'lng', 'locale'].forEach(function (k) {
                    localStorage.setItem(k, 'it-IT');
                });
                localStorage.setItem('i18nextLng-source', '3');
                var prefs0 = JSON.parse(localStorage.getItem('stirlingpdf_preferences') || '{}') || {};
                prefs0.language = 'it-IT';
                prefs0.locale = 'it-IT';
                prefs0.languageCode = 'it-IT';
                localStorage.setItem('stirlingpdf_preferences', JSON.stringify(prefs0));
            } catch (e) {}

            try {
                sessionStorage.removeItem('stirling_sso_auto_login_logged_out');
                localStorage.removeItem('stirling_sso_auto_login_logged_out');
                localStorage.removeItem('stirling_jwt');
            } catch (e) {}

            iframe.addEventListener('load', function () {
                try {
                    var href = iframe.contentWindow.location.href || '';
                    if (href.indexOf('/pdf-tools') !== -1 && href.indexOf('/pdf-tools/enter') === -1) {
                        hideBoot();
                    }
                } catch (e) {
                    hideBoot();
                }
                bind();
            });

            iframe.src = enterUrl + (enterUrl.indexOf('?') >= 0 ? '&' : '?') + '_=' + Date.now();
            setTimeout(bind, 800);
            setTimeout(bind, 2500);
            setTimeout(hideBoot, 4000);
        })();
    </script>
@endsection
