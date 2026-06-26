@php
    $gestiioAiUser = Auth::user();
@endphp

@if($gestiioAiUser)
    <div class="gestiio-ai-widget"
         data-chat-url="{{action([\App\Http\Controllers\Backend\AiAutomationController::class, 'chat'])}}"
         data-recharge-intent-url="{{action([\App\Http\Controllers\Backend\PaymentController::class, 'prepareChatRicarica'])}}"
         data-recharge-pay-url="{{action([\App\Http\Controllers\Backend\PaymentController::class, 'storePagamentoChat'])}}">
        <button type="button" class="gestiio-ai-toggle" aria-label="Apri Gestiio AI">
            <span>G</span>
        </button>

        <section class="gestiio-ai-panel" aria-live="polite">
            <header class="gestiio-ai-head">
                <div>
                    <strong>Gestiio AI</strong>
                    <small>Dimmi cosa vuoi fare in questa pagina.</small>
                </div>
                <button type="button" class="gestiio-ai-close" aria-label="Chiudi">x</button>
            </header>

            <div class="gestiio-ai-context">
                <span>Pagina</span>
                <strong data-gestiio-ai-page>{{trim($__env->yieldContent('title')) ?: ($titoloPagina ?? 'Backend')}}</strong>
            </div>

            <div class="gestiio-ai-messages" data-gestiio-ai-messages>
                <div class="gestiio-ai-message gestiio-ai-message-bot">
                    Ciao, sono Gestiio AI. Posso aiutarti a trovare dati, preparare messaggi, capire priorità o avviare un'automazione.
                </div>
            </div>

            <div class="gestiio-ai-suggestions">
                <button type="button" data-ai-prompt="Cosa devo controllare in questa pagina?">Controlla pagina</button>
                <button type="button" data-ai-prompt="Preparami un messaggio per il cliente">Scrivi messaggio</button>
                <button type="button" data-ai-prompt="Voglio ricaricare il plafond con carta">Ricarica plafond</button>
                <button type="button" data-ai-prompt="Qual è la prossima cosa da fare?">Prossima azione</button>
            </div>

            <form class="gestiio-ai-form" data-gestiio-ai-form>
                <input type="text" name="prompt" autocomplete="off" placeholder="Scrivi qui..." required>
                <button type="submit">Invia</button>
            </form>
        </section>
    </div>

    @once
            <style>
                .gestiio-ai-widget {
                    position: fixed;
                    right: 22px;
                    bottom: 22px;
                    z-index: 1080;
                    font-family: Poppins, sans-serif;
                }

                #kt_scrolltop {
                    right: 22px !important;
                    bottom: 92px !important;
                    z-index: 1079;
                }

                .gestiio-ai-toggle {
                    width: 58px;
                    height: 58px;
                    border: 0;
                    border-radius: 50%;
                    color: #fff;
                    background: #009ef7;
                    box-shadow: 0 14px 34px rgba(0, 158, 247, .35);
                    font-weight: 900;
                    font-size: 1.25rem;
                }

                .gestiio-ai-panel {
                    position: absolute;
                    right: 0;
                    bottom: 72px;
                    display: none;
                    width: min(390px, calc(100vw - 28px));
                    overflow: hidden;
                    border: 1px solid #dce8f4;
                    border-radius: 8px;
                    background: #fff;
                    box-shadow: 0 24px 70px rgba(16, 24, 39, .18);
                }

                .gestiio-ai-widget.is-open .gestiio-ai-panel {
                    display: block;
                }

                .gestiio-ai-head {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 1rem;
                    padding: 1rem;
                    border-bottom: 1px solid #e8eef6;
                    background: #f8fbff;
                }

                .gestiio-ai-head strong {
                    display: block;
                    color: #101827;
                    font-size: 1rem;
                }

                .gestiio-ai-head small,
                .gestiio-ai-context span {
                    color: #69758d;
                }

                .gestiio-ai-close {
                    width: 30px;
                    height: 30px;
                    border: 0;
                    border-radius: 50%;
                    color: #69758d;
                    background: #edf4fb;
                    font-weight: 800;
                }

                .gestiio-ai-context {
                    display: flex;
                    justify-content: space-between;
                    gap: .75rem;
                    padding: .75rem 1rem;
                    border-bottom: 1px solid #eef3f8;
                    font-size: .82rem;
                }

                .gestiio-ai-context strong {
                    max-width: 240px;
                    overflow: hidden;
                    color: #101827;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .gestiio-ai-messages {
                    display: grid;
                    gap: .65rem;
                    max-height: 320px;
                    overflow-y: auto;
                    padding: 1rem;
                    background: #fbfdff;
                }

                .gestiio-ai-message {
                    width: fit-content;
                    max-width: 92%;
                    padding: .7rem .8rem;
                    border-radius: 8px;
                    line-height: 1.45;
                    font-size: .9rem;
                }

                .gestiio-ai-message-bot {
                    color: #263247;
                    background: #eef7ff;
                }

                .gestiio-ai-message-user {
                    justify-self: end;
                    color: #fff;
                    background: #009ef7;
                }

                .gestiio-ai-message a {
                    color: inherit;
                    font-weight: 800;
                    text-decoration: underline;
                }

                .gestiio-ai-message-action {
                    display: block;
                    width: 100%;
                    margin-top: .5rem;
                    border: 0;
                    border-radius: 8px;
                    padding: .5rem .65rem;
                    color: #fff;
                    background: #009ef7;
                    font-weight: 800;
                    text-align: center;
                }

                .gestiio-ai-recharge {
                    display: grid;
                    gap: .65rem;
                    width: min(320px, 100%);
                }

                .gestiio-ai-field {
                    display: grid;
                    gap: .35rem;
                }

                .gestiio-ai-field label {
                    margin: 0;
                    color: #263247;
                    font-size: .78rem;
                    font-weight: 800;
                }

                .gestiio-ai-amounts {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: .4rem;
                }

                .gestiio-ai-amounts input {
                    position: absolute;
                    opacity: 0;
                    pointer-events: none;
                }

                .gestiio-ai-amounts span,
                .gestiio-ai-recharge select,
                .gestiio-ai-recharge input {
                    min-height: 38px;
                    border: 1px solid #dfe8f3;
                    border-radius: 8px;
                    background: #fff;
                }

                .gestiio-ai-amounts span {
                    display: grid;
                    place-items: center;
                    color: #263247;
                    font-weight: 800;
                    cursor: pointer;
                }

                .gestiio-ai-amounts input:checked + span {
                    border-color: #009ef7;
                    color: #0077c2;
                    background: #eef8ff;
                }

                .gestiio-ai-recharge select,
                .gestiio-ai-recharge input {
                    width: 100%;
                    padding: 0 .65rem;
                    color: #263247;
                }

                .gestiio-ai-card-element {
                    min-height: 42px;
                    padding: 11px 12px;
                    border: 1px solid #dfe8f3;
                    border-radius: 8px;
                    background: #fff;
                }

                .gestiio-ai-recharge small {
                    color: #69758d;
                }

                .gestiio-ai-error {
                    color: #f1416c;
                    font-size: .8rem;
                }

                .gestiio-ai-suggestions {
                    display: flex;
                    gap: .45rem;
                    overflow-x: auto;
                    padding: .75rem 1rem;
                    border-top: 1px solid #eef3f8;
                }

                .gestiio-ai-suggestions button {
                    flex: 0 0 auto;
                    border: 0;
                    border-radius: 8px;
                    padding: .45rem .65rem;
                    color: #0077c2;
                    background: #eef8ff;
                    font-size: .8rem;
                    font-weight: 700;
                }

                .gestiio-ai-form {
                    display: grid;
                    grid-template-columns: minmax(0, 1fr) auto;
                    gap: .55rem;
                    padding: .9rem 1rem 1rem;
                    border-top: 1px solid #eef3f8;
                    background: #fff;
                }

                .gestiio-ai-form input {
                    min-height: 38px;
                    border: 1px solid #dfe8f3;
                    border-radius: 8px;
                    padding: 0 .75rem;
                    outline: none;
                }

                .gestiio-ai-form button {
                    min-height: 38px;
                    border: 0;
                    border-radius: 8px;
                    padding: 0 .9rem;
                    color: #fff;
                    background: #009ef7;
                    font-weight: 800;
                }

                @media (max-width: 575.98px) {
                    .gestiio-ai-widget {
                        right: 14px;
                        bottom: 14px;
                    }

                    #kt_scrolltop {
                        right: 14px !important;
                        bottom: 84px !important;
                    }

                    .gestiio-ai-panel {
                        bottom: 68px;
                    }
                }
            </style>

        @push('customScript')
            <script>
                (function () {
                    const widget = document.querySelector('.gestiio-ai-widget');
                    if (!widget) return;

                    const toggle = widget.querySelector('.gestiio-ai-toggle');
                    const close = widget.querySelector('.gestiio-ai-close');
                    const form = widget.querySelector('[data-gestiio-ai-form]');
                    const input = form.querySelector('input[name="prompt"]');
                    const messages = widget.querySelector('[data-gestiio-ai-messages]');
                    const chatUrl = widget.dataset.chatUrl;
                    const rechargeIntentUrl = widget.dataset.rechargeIntentUrl;
                    const rechargePayUrl = widget.dataset.rechargePayUrl;
                    const token = document.querySelector('meta[name="_token"]')?.getAttribute('content') || '';
                    let stripeLoader = null;
                    let conversationId = sessionStorage.getItem('gestiio_ai_conversation_id') || null;
                    let activeWorkflow = null;
                    let chatHistory = [];

                    function addMessage(text, type, actions) {
                        const node = document.createElement('div');
                        node.className = 'gestiio-ai-message gestiio-ai-message-' + type;
                        node.textContent = text;
                        rememberMessage(type === 'user' ? 'user' : 'assistant', text);

                        if (actions && actions.length) {
                            actions.forEach(function (action) {
                                if (action.type === 'recharge_plafond') {
                                    const button = document.createElement('button');
                                    button.type = 'button';
                                    button.textContent = action.label || 'Avvia ricarica';
                                    button.className = 'gestiio-ai-message-action';
                                    button.addEventListener('click', startRechargeWizard);
                                    node.appendChild(button);
                                    return;
                                }

                                if (!action.url) return;
                                const link = document.createElement('a');
                                link.href = action.url;
                                link.textContent = ' ' + action.label;
                                link.className = 'd-block mt-2';
                                node.appendChild(link);
                            });
                        }

                        messages.appendChild(node);
                        messages.scrollTop = messages.scrollHeight;
                    }

                    function addNode(node) {
                        messages.appendChild(node);
                        messages.scrollTop = messages.scrollHeight;
                    }

                    function rememberMessage(role, text) {
                        if (!text) return;
                        chatHistory.push({
                            role: role,
                            text: String(text).slice(0, 500)
                        });

                        if (chatHistory.length > 10) {
                            chatHistory = chatHistory.slice(-10);
                        }
                    }

                    function loadStripeScript() {
                        if (window.Stripe) {
                            return Promise.resolve();
                        }

                        if (stripeLoader) {
                            return stripeLoader;
                        }

                        stripeLoader = new Promise(function (resolve, reject) {
                            const script = document.createElement('script');
                            script.src = 'https://js.stripe.com/v3/';
                            script.onload = resolve;
                            script.onerror = reject;
                            document.head.appendChild(script);
                        });

                        return stripeLoader;
                    }

                    function euro(value) {
                        return new Intl.NumberFormat('it-IT', {
                            style: 'currency',
                            currency: 'EUR'
                        }).format(Number(value || 0));
                    }

                    function escapeHtml(value) {
                        return String(value || '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    }

                    async function fetchJson(url, body) {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify(body || {})
                        });
                        const data = await response.json().catch(function () {
                            return {};
                        });

                        if (!response.ok) {
                            throw new Error(data.message || 'Operazione non riuscita.');
                        }

                        return data;
                    }

                    async function startRechargeWizard() {
                        widget.classList.add('is-open');
                        activeWorkflow = 'recharge_plafond';
                        addMessage('Preparo la ricarica con carta. Resti qui, ti guido io.', 'bot');

                        try {
                            const config = await fetchJson(rechargeIntentUrl);
                            await loadStripeScript();
                            renderRechargeForm(config);
                        } catch (error) {
                            addMessage(error.message || 'Non riesco a preparare la ricarica adesso. Riprova tra poco.', 'bot');
                        }
                    }

                    function renderRechargeForm(config) {
                        const stripe = Stripe(config.stripe_public_key);
                        const elements = stripe.elements();
                        const cardId = 'gestiio-ai-card-' + Date.now();
                        const node = document.createElement('div');
                        node.className = 'gestiio-ai-message gestiio-ai-message-bot';

                        const amounts = (config.amounts || []).map(function (amount, index) {
                            return '<label><input type="radio" name="ai_recharge_amount" value="' + escapeHtml(amount.value) + '"' + (index === 0 ? ' checked' : '') + '><span>' + euro(amount.value) + '</span></label>';
                        }).join('');

                        const wallets = (config.wallets || []).map(function (wallet) {
                            return '<option value="' + escapeHtml(wallet.value) + '">' + escapeHtml(wallet.label) + '</option>';
                        }).join('');

                        node.innerHTML =
                            '<form class="gestiio-ai-recharge">' +
                                '<div class="gestiio-ai-field">' +
                                    '<label>Importo</label>' +
                                    '<div class="gestiio-ai-amounts">' + amounts + '</div>' +
                                    '<small>' + escapeHtml(config.fee_label || 'Commissione Stripe: 1 euro.') + '</small>' +
                                '</div>' +
                                '<div class="gestiio-ai-field">' +
                                    '<label>Portafoglio</label>' +
                                    '<select name="portafoglio" required>' + wallets + '</select>' +
                                '</div>' +
                                '<div class="gestiio-ai-field">' +
                                    '<label>Titolare carta</label>' +
                                    '<input type="text" name="holder" value="' + escapeHtml(config.holder_name || '') + '" required>' +
                                '</div>' +
                                '<div class="gestiio-ai-field">' +
                                    '<label>Dati carta</label>' +
                                    '<div id="' + cardId + '" class="gestiio-ai-card-element"></div>' +
                                    '<div class="gestiio-ai-error" data-recharge-error></div>' +
                                '</div>' +
                                '<button type="submit" class="gestiio-ai-message-action">Conferma ricarica</button>' +
                            '</form>';

                        addNode(node);

                        const card = elements.create('card', {
                            style: {
                                base: {
                                    color: '#263247',
                                    fontFamily: 'Poppins, sans-serif',
                                    fontSize: '14px',
                                    '::placeholder': {color: '#99a6bd'}
                                },
                                invalid: {color: '#f1416c'}
                            }
                        });
                        card.mount('#' + cardId);

                        const rechargeForm = node.querySelector('form');
                        const errorNode = node.querySelector('[data-recharge-error]');
                        const submitButton = rechargeForm.querySelector('button[type="submit"]');

                        rechargeForm.addEventListener('submit', async function (event) {
                            event.preventDefault();
                            errorNode.textContent = '';
                            submitButton.disabled = true;
                            submitButton.textContent = 'Pagamento in corso...';

                            const result = await stripe.confirmCardSetup(config.client_secret, {
                                payment_method: {
                                    card: card,
                                    billing_details: {
                                        name: rechargeForm.elements.holder.value
                                    }
                                }
                            });

                            if (result.error) {
                                errorNode.textContent = result.error.message;
                                submitButton.disabled = false;
                                submitButton.textContent = 'Conferma ricarica';
                                return;
                            }

                            try {
                                const data = await fetchJson(rechargePayUrl, {
                                    importo: rechargeForm.querySelector('input[name="ai_recharge_amount"]:checked').value,
                                    portafoglio: rechargeForm.elements.portafoglio.value,
                                    payment_method: result.setupIntent.payment_method
                                });

                                addMessage(data.message || 'Ricarica completata. Il plafond è stato aggiornato.', 'bot', [
                                    {label: 'Vedi movimenti', url: data.redirect_url || '/backend/portafoglio'}
                                ]);
                                activeWorkflow = null;
                                submitButton.textContent = 'Ricarica completata';
                            } catch (error) {
                                errorNode.textContent = error.message || 'Pagamento non riuscito. Controlla i dati e riprova.';
                                submitButton.disabled = false;
                                submitButton.textContent = 'Conferma ricarica';
                            }
                        });
                    }

                    function pageContext() {
                        const title = document.title || '';
                        const heading = document.querySelector('h1,h2,.card-title')?.textContent?.trim() || '';
                        return {
                            url: window.location.pathname,
                            full_url: window.location.href,
                            title: title,
                            heading: heading,
                            menu: document.querySelector('.menu-link.active .menu-title, .menu-item.show .menu-title')?.textContent?.trim() || ''
                        };
                    }

                    async function send(prompt) {
                        if (!prompt) return;
                        addMessage(prompt, 'user');
                        input.value = '';
                        input.disabled = true;

                        try {
                            const response = await fetch(chatUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token
                                },
                            body: JSON.stringify({
                                prompt: prompt,
                                context: Object.assign(pageContext(), {
                                    workflow: activeWorkflow
                                }),
                                conversation_id: conversationId,
                                history: chatHistory.slice(-8)
                            })
                        });

                            const data = await response.json();
                            if (data.conversation_id) {
                                conversationId = data.conversation_id;
                                sessionStorage.setItem('gestiio_ai_conversation_id', conversationId);
                            }

                            addMessage(data.answer || 'Ho preso in carico la richiesta.', 'bot', data.actions || []);
                            if (data.workflow === 'recharge_plafond') {
                                activeWorkflow = 'recharge_plafond';
                                startRechargeWizard();
                            }
                        } catch (error) {
                            addMessage('Non riesco a rispondere adesso. Riprova tra poco.', 'bot');
                        } finally {
                            input.disabled = false;
                            input.focus();
                        }
                    }

                    toggle.addEventListener('click', function () {
                        widget.classList.toggle('is-open');
                        if (widget.classList.contains('is-open')) {
                            input.focus();
                        }
                    });

                    close.addEventListener('click', function () {
                        widget.classList.remove('is-open');
                    });

                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        send(input.value.trim());
                    });

                    widget.querySelectorAll('[data-ai-prompt]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            send(button.dataset.aiPrompt);
                        });
                    });
                })();
            </script>
        @endpush
    @endonce
@endif
