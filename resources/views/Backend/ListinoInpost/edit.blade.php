@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="inpost-listino-edit-page">
        <section class="inpost-listino-edit-hero mb-8">
            <div>
                <div class="inpost-listino-edit-kicker">Listino InPost</div>
                <h1 class="inpost-listino-edit-title">{{$vecchio ? 'Modifica tariffa package' : 'Nuova tariffa package'}}</h1>
                <p class="inpost-listino-edit-text">Configura i prezzi del package selezionato per punto di ritiro e consegna a indirizzo.</p>
            </div>
        </section>
        <div class="card inpost-listino-edit-shell">
        <div class="card-body p-8 p-lg-10">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}">
                @csrf
                @method($record->id?'PATCH':'POST')
                <div class="inpost-listino-form-card mb-8">
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputRadioH',['campo'=>'package_type','testo'=>'Package','required'=>true,'array'=>['small'=>'Piccolo (S)','medium'=>'Medio (M)','large'=>'Grande (L)']])
                    </div>
                </div>
                </div>
                <div class="inpost-listino-form-card mb-8">
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'locker_point','testo'=>'Locker o punto di ritiro','classe'=>'autonumericImporto'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'home_delivery','testo'=>'Indirizzo del destinatario','classe'=>'autonumericImporto'])
                    </div>
                </div>
                </div>

                <div class="inpost-listino-submit-bar">
                    <div class="inpost-listino-submit-copy">
                        <div class="inpost-listino-submit-title">Tariffa pronta per essere salvata.</div>
                        <div class="inpost-listino-submit-text">Controlla i prezzi inseriti prima di confermare.</div>
                    </div>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <button class="btn inpost-listino-submit-button" type="submit" id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\ListinoInpost::NOME_SINGOLARE}}</button>
                    @if($vecchio)
                        @if($eliminabile===true)
                            <a class="btn btn-danger" id="elimina" href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
                        @elseif(is_string($eliminabile))
                            <span data-bs-toggle="tooltip" title="{{$eliminabile}}">
                                <a class="btn btn-danger disabled" href="javascript:void(0)">Elimina</a>
                            </span>
                        @endif
                    @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
    </div>
@endsection
@push('customCss')
    <style>
        .inpost-listino-edit-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .inpost-listino-edit-hero {
            padding: 2rem;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(255, 199, 0, 0.24), transparent 28%),
                linear-gradient(135deg, #191d24 0%, #232933 58%, #2d3541 100%);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .inpost-listino-edit-kicker {
            display: inline-flex;
            margin-bottom: .85rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            color: #ffd84d;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .inpost-listino-edit-title {
            margin-bottom: .65rem;
            font-size: clamp(2rem, 3vw, 3rem);
            line-height: 1.05;
            font-weight: 800;
            color: #fff;
        }

        .inpost-listino-edit-text {
            margin: 0;
            color: rgba(255,255,255,0.78);
            font-size: 1rem;
        }

        .inpost-listino-edit-shell {
            background: linear-gradient(180deg, #ffffff 0%, #fbfbfd 100%);
            border-radius: 22px;
            border: 1px solid #e3e6ec;
            box-shadow: 0 20px 60px rgba(22, 28, 45, 0.06);
        }

        .inpost-listino-form-card {
            background: #fff;
            border: 1px solid #e1e3ea;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 24px rgba(18, 24, 39, 0.04);
            overflow: hidden;
        }

        .inpost-listino-form-card .row {
            --bs-gutter-x: 1.25rem;
            --bs-gutter-y: 1rem;
            margin-right: 0;
            margin-left: 0;
        }

        .inpost-listino-form-card .row > [class*="col-"],
        .inpost-listino-form-card .row > [class^="col-"] {
            padding-right: calc(var(--bs-gutter-x) * .5);
            padding-left: calc(var(--bs-gutter-x) * .5);
        }

        .inpost-listino-submit-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem 1.25rem;
            border-radius: 18px;
            border: 1px solid #ece2ad;
            background: linear-gradient(135deg, #fffdf1 0%, #fff8d8 100%);
        }

        .inpost-listino-submit-title {
            font-size: 1.02rem;
            font-weight: 800;
            color: #28230d;
        }

        .inpost-listino-submit-text {
            color: #685f35;
            font-size: .92rem;
        }

        .inpost-listino-submit-button {
            min-height: 48px;
            padding: .8rem 1.25rem;
            border-radius: 999px;
            background: #11151c;
            color: #fff;
            border: 1px solid #11151c;
            font-weight: 800;
        }

        .inpost-listino-submit-button:hover {
            color: #fff;
            background: #1d232c;
            border-color: #1d232c;
        }

        @media (max-width: 991px) {
            .inpost-listino-submit-bar {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
@endpush
@push('customScript')
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
            autonumericImporto('autonumericImporto');
        });
    </script>
@endpush
