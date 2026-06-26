@php
    $aiSuggestions = collect($aiSuggestions ?? []);
    $audience = $audience ?? 'agente';
    $usageProfile = Auth::check() ? app(\App\Services\AiAutomationService::class)->usageProfile(Auth::user()) : null;
@endphp

<section class="ai-control-card mb-7">
    <div class="ai-control-head">
        <div>
            <span>Gestiio AI</span>
            <h3>{{$audience === 'admin' ? 'Consigli per oggi' : 'Assistente personale'}}</h3>
            <p>{{$audience === 'admin' ? 'Priorità e anomalie da controllare nella giornata.' : 'Suggerimenti basati sulle pratiche che stai lavorando.'}}</p>
        </div>
        <div class="ai-control-actions">
            <form method="POST" action="{{action([\App\Http\Controllers\Backend\AiAutomationController::class, 'triggerDashboard'])}}">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">Aggiorna consigli</button>
            </form>
            <a href="{{action([\App\Http\Controllers\Backend\AiAutomationController::class, 'index'])}}" class="btn btn-sm btn-light-primary">Vedi storico</a>
        </div>
    </div>

    <div class="ai-command-strip">
        <form method="POST" action="{{action([\App\Http\Controllers\Backend\AiAutomationController::class, 'ask'])}}">
            @csrf
            <input type="hidden" name="scope" value="dashboard">
            <input type="text" name="prompt" class="form-control form-control-sm form-control-solid"
                   placeholder="{{$audience === 'admin' ? 'Chiedi quali agenti o pratiche controllare ora' : 'Chiedi cosa lavorare ora o fatti preparare un messaggio'}}">
            <button type="submit" class="btn btn-sm btn-primary">Chiedi</button>
        </form>
        @if($usageProfile)
            <div class="ai-learning-meter">
                <span>Apprendimento</span>
                <strong>{{$usageProfile['acceptance_rate']}}%</strong>
                <em>{{$usageProfile['preference'] === 'azioni_dirette' ? 'Preferisce azioni dirette' : 'Richiede consigli più selettivi'}}</em>
            </div>
        @endif
    </div>

    @if($aiSuggestions->isEmpty())
        <div class="ai-control-empty">
            <strong>Nessun consiglio da leggere</strong>
            <span>Aggiorna i consigli oppure torna più tardi.</span>
        </div>
    @else
        <div class="ai-suggestion-grid">
            @foreach($aiSuggestions as $suggestion)
                <article class="ai-suggestion ai-priority-{{$suggestion->priority}}">
                    <div class="ai-suggestion-meta">
                        <span>{{ucfirst($suggestion->priority)}}</span>
                        <em>{{$suggestion->created_at?->diffForHumans()}}</em>
                    </div>
                    <h4>{{$suggestion->title}}</h4>
                    @if($suggestion->summary)
                        <p>{{$suggestion->summary}}</p>
                    @endif
                    @if($suggestion->next_action)
                        <div class="ai-next-action">{{$suggestion->next_action}}</div>
                    @endif
                    <div class="ai-suggestion-actions">
                        <form method="POST" action="{{action([\App\Http\Controllers\Backend\AiAutomationController::class, 'feedback'], $suggestion->id)}}" class="js-ai-feedback-form">
                            @csrf
                            <input type="hidden" name="azione" value="accepted">
                            <button type="submit" class="btn btn-sm btn-light-success">Usa</button>
                        </form>
                        <form method="POST" action="{{action([\App\Http\Controllers\Backend\AiAutomationController::class, 'feedback'], $suggestion->id)}}" class="js-ai-feedback-form">
                            @csrf
                            <input type="hidden" name="azione" value="dismissed">
                            <button type="submit" class="btn btn-sm btn-light">Scarta</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

@once
    @push('customCss')
        <style>
            .ai-control-card {
                overflow: hidden;
                border: 1px solid rgba(0, 158, 247, .18);
                border-radius: 8px;
                background: linear-gradient(135deg, #ffffff 0%, #f6fbff 55%, #f7fff9 100%);
                box-shadow: 0 18px 45px rgba(16, 24, 39, .055);
            }

            .ai-control-head {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                padding: 1.35rem 1.5rem;
                border-bottom: 1px solid #e7eef7;
            }

            .ai-control-head span {
                display: inline-flex;
                min-height: 24px;
                align-items: center;
                padding: .2rem .6rem;
                border-radius: 8px;
                color: #009ef7;
                background: rgba(0, 158, 247, .08);
                font-size: .72rem;
                font-weight: 850;
                text-transform: uppercase;
            }

            .ai-control-head h3 {
                margin: .65rem 0 .25rem;
                color: #101827;
                font-size: 1.2rem;
                font-weight: 900;
            }

            .ai-control-head p {
                margin: 0;
                color: #69758d;
            }

            .ai-control-actions,
            .ai-suggestion-actions {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-start;
                gap: .55rem;
            }

            .ai-command-strip {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 250px;
                gap: .85rem;
                padding: 1rem 1.5rem;
                border-bottom: 1px solid #e7eef7;
                background: rgba(255, 255, 255, .56);
            }

            .ai-command-strip form {
                display: flex;
                gap: .55rem;
                align-items: center;
            }

            .ai-command-strip input {
                min-height: 40px;
            }

            .ai-learning-meter {
                display: grid;
                gap: .1rem;
                padding: .7rem .85rem;
                border: 1px solid #e9f1f8;
                border-radius: 8px;
                background: #fff;
            }

            .ai-learning-meter span {
                color: #69758d;
                font-size: .72rem;
                font-weight: 850;
                text-transform: uppercase;
            }

            .ai-learning-meter strong {
                color: #101827;
                font-size: 1.1rem;
                font-weight: 900;
            }

            .ai-learning-meter em {
                color: #69758d;
                font-style: normal;
            }

            .ai-control-empty {
                display: grid;
                gap: .25rem;
                margin: 1.25rem 1.5rem;
                padding: 1rem;
                border: 1px dashed #dfe8f3;
                border-radius: 8px;
                background: rgba(255, 255, 255, .75);
            }

            .ai-control-empty strong,
            .ai-suggestion h4 {
                color: #101827;
            }

            .ai-control-empty span,
            .ai-suggestion p,
            .ai-suggestion-meta em {
                color: #69758d;
            }

            .ai-suggestion-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: .85rem;
                padding: 1.25rem 1.5rem;
            }

            .ai-suggestion {
                display: grid;
                align-content: start;
                gap: .7rem;
                transition: opacity .18s ease, transform .18s ease;
                padding: 1rem;
                border: 1px solid #e9f1f8;
                border-left: 4px solid #009ef7;
                border-radius: 8px;
                background: #ffffff;
                box-shadow: 0 10px 24px rgba(16, 24, 39, .04);
            }

            .ai-suggestion.is-resolving {
                pointer-events: none;
                opacity: 0;
                transform: translateY(-6px);
            }

            .ai-priority-alta,
            .ai-priority-critica {
                border-left-color: #f1416c;
            }

            .ai-priority-bassa {
                border-left-color: #50cd89;
            }

            .ai-suggestion-meta {
                display: flex;
                justify-content: space-between;
                gap: .75rem;
                align-items: center;
            }

            .ai-suggestion-meta span {
                color: #009ef7;
                font-size: .75rem;
                font-weight: 850;
                text-transform: uppercase;
            }

            .ai-suggestion h4 {
                margin: 0;
                font-size: 1rem;
                font-weight: 850;
            }

            .ai-suggestion p {
                margin: 0;
            }

            .ai-next-action {
                padding: .75rem;
                border-radius: 8px;
                color: #101827;
                background: #f6f9fc;
                font-weight: 750;
            }

            @media (max-width: 991.98px) {
                .ai-control-head,
                .ai-command-strip {
                    flex-direction: column;
                    grid-template-columns: 1fr;
                }

                .ai-command-strip form {
                    flex-direction: column;
                    align-items: stretch;
                }
            }
        </style>
    @endpush

    @push('customScript')
        <script>
            document.addEventListener('submit', function (event) {
                const form = event.target.closest('.js-ai-feedback-form');
                if (!form) {
                    return;
                }

                const card = form.closest('.ai-suggestion');
                if (card) {
                    card.classList.add('is-resolving');
                }
            });
        </script>
    @endpush
@endonce
