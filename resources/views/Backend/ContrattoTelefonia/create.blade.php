@extends('Backend._layout._main')

@section('toolbar')
    <a href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}" class="btn btn-sm btn-light-primary fw-bold">Elenco contratti</a>
@endsection

@section('content')
    <div class="contract-create-page">
        <section class="contract-create-hero">
            <div class="contract-create-copy">
                <span>Nuovo ordine telefonia</span>
                <h1>Prepara il contratto giusto, senza inciampi.</h1>
                <p>Seleziona agente e tipo contratto: appena il profilo commerciale è scelto, apri il form corretto con campi e prodotto già allineati.</p>
            </div>
            <form id="contract-create-form" method="GET" action="{{action([$controller,'create'])}}" class="contract-create-panel">
                <input type="hidden" name="new" value="1">

                @if(Auth::user()->hasPermissionTo('admin'))
                    <label for="agente_id">Agente</label>
                    @include('Backend._components.inputSelect2',[
                        'campo'=>'agente_id',
                        'testo'=>'Agente',
                        'required'=>true,
                        'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))
                    ])
                @else
                    <input type="hidden" id="agente_id" name="agente_id" value="{{old('agente_id',$record->agente_id)}}">
                @endif

                <label for="tipo_contratto_id">Tipo contratto</label>
                @include('Backend._components.inputSelect2',[
                    'campo'=>'tipo_contratto_id',
                    'testo'=>'Tipo contratto',
                    'required'=>true,
                    'selected'=>null
                ])

                <button type="submit" id="contract-create-submit" class="btn btn-primary">Crea contratto</button>
            </form>
        </section>

        <section class="contract-create-strip">
            <div><strong>1</strong><span>Agente</span></div>
            <div><strong>2</strong><span>Tipo contratto</span></div>
            <div><strong>3</strong><span>Form prodotto</span></div>
        </section>
    </div>
@endsection

@push('customCss')
    <style>
        .contract-create-page {
            --cc-text: #172033;
            --cc-muted: #6d7890;
            --cc-line: #dce6f2;
            --cc-blue: #0b8fe8;
            --cc-ink: #101828;
        }

        .contract-create-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(360px, .95fr);
            gap: 1.25rem;
            align-items: stretch;
            border: 1px solid var(--cc-line);
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }

        .contract-create-copy {
            padding: 2rem;
            background:
                linear-gradient(135deg, rgba(11, 143, 232, .12), rgba(80, 205, 137, .08)),
                repeating-linear-gradient(90deg, rgba(23, 32, 51, .06) 0 1px, transparent 1px 28px);
        }

        .contract-create-copy span {
            color: var(--cc-blue);
            font-size: .75rem;
            font-weight: 850;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .contract-create-copy h1 {
            max-width: 620px;
            margin: .7rem 0 .8rem;
            color: var(--cc-text);
            font-size: clamp(2rem, 3.3vw, 3.5rem);
            line-height: 1;
            font-weight: 900;
            letter-spacing: 0;
        }

        .contract-create-copy p {
            max-width: 560px;
            margin: 0;
            color: var(--cc-muted);
            font-size: 1.02rem;
            line-height: 1.55;
            font-weight: 600;
        }

        .contract-create-panel {
            display: grid;
            align-content: center;
            gap: .85rem;
            padding: 2rem;
            background: #fbfdff;
        }

        .contract-create-panel label {
            color: var(--cc-ink);
            font-weight: 800;
        }

        .contract-create-panel .row {
            margin-bottom: .5rem !important;
        }

        .contract-create-panel .btn {
            min-height: 44px;
            margin-top: .25rem;
            font-weight: 800;
        }

        .contract-create-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .85rem;
            margin-top: 1rem;
        }

        .contract-create-strip div {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-height: 58px;
            padding: .9rem 1rem;
            border: 1px solid var(--cc-line);
            border-radius: 8px;
            background: #fff;
        }

        .contract-create-strip strong {
            display: inline-grid;
            place-items: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            color: #fff;
            background: var(--cc-blue);
            font-weight: 900;
        }

        .contract-create-strip span {
            color: var(--cc-text);
            font-weight: 800;
        }

        @media (max-width: 991.98px) {
            .contract-create-hero,
            .contract-create-strip {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {
            if ($('#agente_id').is('select')) {
                select2UniversaleBackend('agente_id', 'un agente', 1, 'agente_id');
            }

            $('#tipo_contratto_id').select2({
                placeholder: 'Seleziona un tipo contratto',
                minimumInputLength: -1,
                allowClear: true,
                width: '100%',
                ajax: {
                    quietMillis: 150,
                    url: function () {
                        return "/backend/select2?tipo_contratto_id&agente_id=" + ($('#agente_id').val() || '');
                    },
                    dataType: 'json',
                    data: function (term) {
                        return {term: term.term};
                    },
                    processResults: function (data) {
                        return {results: data};
                    }
                }
            }).on('select2:select', function (e) {
                if ($('#agente_id').is('select') && !$('#agente_id').val()) {
                    return;
                }

                var agente = $('#agente_id').val() ? '&agente_id=' + $('#agente_id').val() : '';
                location.href = '{{action([$controller,'create'])}}?new=1&tipo_contratto_id=' + e.params.data.id + agente;
            });
        });
    </script>
@endpush
