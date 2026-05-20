@extends('Backend._layout._main')
@section('toolbar') @endsection
@section('content')
    <style>
        .brt-zone-picker {
            padding: 1rem;
        }

        .brt-zone-kicker {
            display: inline-flex;
            margin-bottom: .85rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            background: #eef2ff;
            color: #28408b;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .brt-zone-title {
            margin-bottom: .5rem;
            font-size: 2rem;
            font-weight: 800;
            color: #172033;
        }

        .brt-zone-text {
            margin-bottom: 0;
            color: #6b7383;
        }

        .brt-zone-card {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            height: 100%;
            padding: 1.4rem;
            border-radius: 18px;
            border: 1px solid #dfe5f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8faff 100%);
            color: #172033 !important;
            text-decoration: none !important;
            box-shadow: 0 18px 45px rgba(22, 28, 45, 0.06);
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }

        .brt-zone-card:hover {
            transform: translateY(-2px);
            color: #172033 !important;
            border-color: #b8c8f7;
            box-shadow: 0 24px 55px rgba(22, 28, 45, 0.1);
        }

        .brt-zone-card-kicker {
            color: #5f6d8f;
            font-size: .82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .brt-zone-card-title {
            font-size: 1.7rem;
            font-weight: 800;
        }

        .brt-zone-card-text {
            color: #5d677b;
            line-height: 1.55;
        }
    </style>
    <div class="brt-zone-picker">
        <div class="brt-zone-picker-head">
            <div class="brt-zone-kicker">Nuova spedizione BRT</div>
            <h3 class="brt-zone-title">Scegli l'area di spedizione</h3>
            <p class="brt-zone-text">Seleziona il flusso corretto per aprire il form gia collegato alla logica BRT del progetto.</p>
        </div>
        <div class="row g-4 pt-4">
        <div class="col-md-6">
            <a href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'create'],'ITALIA')}}" class="brt-zone-card">
                <span class="brt-zone-card-kicker">Nazionale</span>
                <span class="brt-zone-card-title">Italia</span>
                <span class="brt-zone-card-text">Per spedizioni sul territorio italiano con form dedicato e provincia selezionabile.</span>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'create'],'EUROPA')}}" class="brt-zone-card">
                <span class="brt-zone-card-kicker">Internazionale</span>
                <span class="brt-zone-card-title">Europa</span>
                <span class="brt-zone-card-text">Per spedizioni europee con nazione di destinazione selezionabile.</span>
            </a>
        </div>
        </div>
    </div>
@endsection
