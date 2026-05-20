@extends('Backend._layout._main')
@section('toolbar')
    <div class="inpost-toolbar">
        <div class="inpost-toolbar-search" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{$testoCerca}}">
            <span class="inpost-toolbar-search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="M14.2929 16.7071C13.9024 16.3166 13.9024 15.6834 14.2929 15.2929C14.6834 14.9024 15.3166 14.9024 15.7071 15.2929L19.7071 19.2929C20.0976 19.6834 20.0976 20.3166 19.7071 20.7071C19.3166 21.0976 18.6834 21.0976 18.2929 20.7071L14.2929 16.7071Z" fill="currentColor" opacity="0.35"/>
                    <path d="M11 16C13.7614 16 16 13.7614 16 11C16 8.23858 13.7614 6 11 6C8.23858 6 6 8.23858 6 11C6 13.7614 8.23858 16 11 16ZM11 18C7.13401 18 4 14.866 4 11C4 7.13401 7.13401 4 11 4C14.866 4 18 7.13401 18 11C18 14.866 14.866 18 11 18Z" fill="currentColor"/>
                </svg>
            </span>
            <input type="text" id="filter_search" class="form-control form-control-solid inpost-toolbar-input" placeholder="Ricerca per città o contatto"/>
        </div>
        <div class="inpost-toolbar-actions">
            <a class="btn btn-sm inpost-btn-primary" href="{{action([$controller,'create'])}}">{{$testoNuovo}}</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $collection = collect($records->items());
        $pendingCount = $collection->filter(fn($r) => in_array(strtoupper((string)$r->status), ['CREATED','PENDING','RESCHEDULED']))->count();
        $collectedCount = $collection->filter(fn($r) => strtoupper((string)$r->status) === 'COLLECTED')->count();
        $cancelledCount = $collection->filter(fn($r) => in_array(strtoupper((string)$r->status), ['CANCELLED','INVALIDATED','INFEASIBLE']))->count();
    @endphp
    <div class="inpost-index-page">
        <section class="inpost-hero-card mb-8">
            <div class="inpost-hero-copy">
                <div class="inpost-hero-kicker">Ritiri InPost</div>
                <h1 class="inpost-hero-title">Prenota il ritiro a domicilio.</h1>
                <p class="inpost-hero-text">Pianifica il ritiro di uno o più colli direttamente dall'indirizzo del mittente. Il corriere InPost si presenta nella finestra oraria concordata.</p>
                <div class="inpost-hero-badges">
                    <span class="inpost-doc-badge">Ritiro a domicilio</span>
                    <span class="inpost-doc-badge">Finestra oraria</span>
                    <span class="inpost-doc-badge">Tracking</span>
                </div>
            </div>
            <div class="inpost-kpi-grid">
                <div class="inpost-kpi-card">
                    <span class="inpost-kpi-label">Totale ritiri</span>
                    <span class="inpost-kpi-value">{{$records->total()}}</span>
                </div>
                <div class="inpost-kpi-card">
                    <span class="inpost-kpi-label">In attesa</span>
                    <span class="inpost-kpi-value">{{$pendingCount}}</span>
                </div>
                <div class="inpost-kpi-card">
                    <span class="inpost-kpi-label">Ritirati</span>
                    <span class="inpost-kpi-value">{{$collectedCount}}</span>
                </div>
                <div class="inpost-kpi-card">
                    <span class="inpost-kpi-label">Annullati</span>
                    <span class="inpost-kpi-value">{{$cancelledCount}}</span>
                </div>
            </div>
        </section>

        <div class="inpost-table-shell">
            <div class="inpost-table-shell-head">
                <div>
                    <h2 class="inpost-table-title">Elenco ritiri</h2>
                    <p class="inpost-table-text">Controlla data, indirizzo e stato di ogni ritiro programmato.</p>
                </div>
            </div>
            <div class="card-body pt-0 pb-5 fs-6" id="tabella">
                @include('Backend.InpostPickup.tabella')
            </div>
        </div>
    </div>
@endsection

@push('customCss')
    <style>
        .inpost-index-page { display:flex; flex-direction:column; gap:1.5rem; }
        .inpost-hero-card { display:grid; grid-template-columns:minmax(0,1.4fr) minmax(320px,1fr); gap:1.5rem; padding:2rem; border-radius:24px; background: radial-gradient(circle at top right,rgba(255,199,0,.24),transparent 28%), linear-gradient(135deg,#191d24 0%,#232933 58%,#2d3541 100%); color:#fff; border:1px solid rgba(255,255,255,.08); overflow:hidden; }
        .inpost-hero-kicker { display:inline-flex; margin-bottom:.85rem; padding:.35rem .65rem; border-radius:999px; background:rgba(255,255,255,.08); color:#ffd84d; font-size:.82rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
        .inpost-hero-title { max-width:16ch; margin-bottom:.85rem; font-size:clamp(2rem,3vw,3.2rem); line-height:1.05; font-weight:800; color:#fff; }
        .inpost-hero-text { max-width:60ch; margin-bottom:1.15rem; color:rgba(255,255,255,.76); font-size:1.02rem; }
        .inpost-hero-badges { display:flex; flex-wrap:wrap; gap:.65rem; }
        .inpost-doc-badge { padding:.55rem .8rem; border-radius:999px; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); color:#fff; font-size:.9rem; font-weight:600; }
        .inpost-kpi-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.9rem; align-content:start; }
        .inpost-kpi-card { display:flex; flex-direction:column; justify-content:space-between; min-height:100px; padding:1rem 1.1rem; border-radius:18px; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08); }
        .inpost-kpi-label { color:rgba(255,255,255,.68); font-size:.88rem; font-weight:600; }
        .inpost-kpi-value { font-size:2rem; font-weight:800; line-height:1; color:#fff; }
        .inpost-table-shell { background:linear-gradient(180deg,#fff 0%,#fbfbfd 100%); border-radius:22px; border:1px solid #e3e6ec; box-shadow:0 20px 60px rgba(22,28,45,.06); overflow:hidden; }
        .inpost-table-shell-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1.5rem 1.75rem .75rem; }
        .inpost-table-title { margin:0 0 .25rem; font-size:1.6rem; font-weight:800; color:#1f2430; }
        .inpost-table-text { margin:0; color:#697181; }
        .inpost-table-responsive { padding:0 1.75rem 1.5rem; }
        #tabella-elenco thead th { padding:.95rem .85rem 1rem; border-bottom:1px solid #e9edf3; letter-spacing:.04em; white-space:nowrap; }
        #tabella-elenco tbody td { padding:1rem .85rem; border-bottom:1px solid #edf1f6; vertical-align:middle; }
        #tabella-elenco tbody tr:hover { background:#fffdf2; }
        .inpost-action-group { display:inline-flex; align-items:center; gap:.35rem; padding:.2rem; border-radius:999px; background:#f6f8fb; border:1px solid #e7ebf1; }
        .inpost-agent-pill { display:inline-flex; align-items:center; justify-content:center; min-height:32px; padding:.35rem .75rem; border-radius:999px; font-size:.84rem; font-weight:700; background:#f2f4f8; color:#475164; }
        .inpost-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; width:100%; }
        .inpost-toolbar-search { position:relative; flex:1 1 auto; max-width:460px; }
        .inpost-toolbar-search-icon { position:absolute; top:50%; left:1rem; transform:translateY(-50%); color:#596171; z-index:2; }
        .inpost-toolbar-input { min-height:46px; padding-left:3rem !important; border-radius:999px; border:1px solid #d9dde6; background:#fff !important; font-weight:600; }
        .inpost-toolbar-actions { display:flex; align-items:center; gap:.75rem; }
        .inpost-btn-primary { min-height:44px; padding:.7rem 1rem; border-radius:999px; font-weight:700; background:#11151c; color:#fff; border:1px solid #11151c; }
        .inpost-btn-primary:hover { color:#fff; background:#1d232c; border-color:#1d232c; }
        @media(max-width:991px) { .inpost-hero-card { grid-template-columns:1fr; } .inpost-toolbar { flex-direction:column; align-items:stretch; } .inpost-toolbar-search { max-width:none; } }
    </style>
@endpush

@push('customScript')
    <script>
        var indexUrl = '{{action([$controller,'index'])}}';
        $(function () { searchHandler(); });
    </script>
@endpush
