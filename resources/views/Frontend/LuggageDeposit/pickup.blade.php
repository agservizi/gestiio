<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <title>Ritiro {{ $deposit->code }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:400,600,700">
    <style>
        :root { --ok:#16a36f; --warn:#f59e0b; --ink:#07111f; --muted:#5d6b82; --line:#dce7f3; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:Inter,Arial,sans-serif; background:#f4f8fc; color:var(--ink); }
        .wrap { max-width:480px; margin:0 auto; padding:16px 16px 120px; }
        .card { background:#fff; border-radius:16px; padding:18px; margin-bottom:14px; box-shadow:0 8px 24px rgba(7,17,31,.06); }
        h1 { font-size:1.35rem; margin:0 0 4px; }
        .muted { color:var(--muted); font-size:.92rem; }
        .kpi { display:flex; gap:12px; margin-top:14px; }
        .kpi div { flex:1; background:#f8fafc; border-radius:12px; padding:12px; text-align:center; }
        .kpi strong { display:block; font-size:1.2rem; }
        .tag-list { list-style:none; padding:0; margin:12px 0 0; }
        .tag-list li { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--line); font-weight:600; }
        .tag-list li.done { color:var(--ok); }
        .tag-list li .dot { width:28px; height:28px; border-radius:50%; border:2px solid var(--line); display:flex; align-items:center; justify-content:center; font-size:.8rem; }
        .tag-list li.done .dot { background:var(--ok); border-color:var(--ok); color:#fff; }
        #scanner-wrap { position:relative; overflow:hidden; border-radius:14px; background:#000; min-height:220px; }
        #scanner { width:100%; min-height:220px; }
        .scanner-line { position:absolute; left:8%; right:8%; top:50%; height:2px; background:rgba(7,149,232,.9); box-shadow:0 0 8px rgba(7,149,232,.8); }
        .manual { display:flex; gap:8px; margin-top:12px; }
        .manual input { flex:1; border:1px solid var(--line); border-radius:10px; padding:12px; font-size:1rem; }
        .manual button, .btn { border:0; border-radius:12px; padding:14px 16px; font-weight:700; font-size:1rem; cursor:pointer; }
        .btn-primary { background:#0795e8; color:#fff; width:100%; }
        .btn-primary:disabled { opacity:.45; cursor:not-allowed; }
        .btn-secondary { background:#e8eef5; color:var(--ink); }
        .sticky { position:fixed; left:0; right:0; bottom:0; background:#fff; border-top:1px solid var(--line); padding:12px 16px; }
        .sticky .wrap { padding:0; max-width:480px; }
        .alert { border-radius:12px; padding:12px 14px; margin-top:12px; font-size:.92rem; }
        .alert-info { background:#eef6ff; color:#045f9b; }
        .alert-error { background:#fef2f2; color:#b91c1c; }
        .alert-success { background:#ecfdf5; color:#047857; }
        select { width:100%; border:1px solid var(--line); border-radius:10px; padding:12px; font-size:1rem; margin-bottom:10px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="muted">Ritiro bagagli · sportello</div>
        <h1>{{ $deposit->customer_name }}</h1>
        <div class="muted">{{ $deposit->code }} · {{ $deposit->bag_count }} borse</div>
        <div class="kpi">
            <div><span class="muted">Importo</span><strong>€ {{ number_format($pricing['total'], 2, ',', '.') }}</strong></div>
            <div><span class="muted">Giorni</span><strong>{{ $pricing['days'] }}</strong></div>
            <div><span class="muted">Scansionati</span><strong id="count-scanned">{{ count($scannedTags) }}/{{ count($expectedTags) }}</strong></div>
        </div>
    </div>

    <div class="card">
        <strong>1. Scansiona i tag sui bagagli</strong>
        <p class="muted" style="margin:8px 0 0">Inquadra il barcode CODE 128 stampato su ogni tag.</p>
        <div id="scanner-wrap" class="mt-3" style="margin-top:14px">
            <div id="scanner"></div>
            <div class="scanner-line"></div>
        </div>
        <div class="manual">
            <input type="text" id="manual-tag" placeholder="Oppure inserisci tag es. LB-XXXX-A" autocomplete="off" autocapitalize="characters">
            <button type="button" class="btn-secondary" id="manual-btn">OK</button>
        </div>
        <div id="scan-feedback" class="alert alert-info" style="display:none"></div>
    </div>

    <div class="card">
        <strong>2. Verifica tag</strong>
        <ul class="tag-list" id="tag-list">
            @foreach($expectedTags as $tag)
                <li data-tag="{{ $tag }}" class="{{ in_array($tag, $scannedTags, true) ? 'done' : '' }}">
                    <span class="dot">{{ in_array($tag, $scannedTags, true) ? '✓' : '' }}</span>
                    <span>{{ $tag }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<div class="sticky">
    <div class="wrap">
        <select id="payment-method">
            <option>Contanti</option>
            <option>Carta</option>
            <option>Bonifico</option>
        </select>
        <button type="button" class="btn-primary" id="complete-btn" disabled>Completa ritiro</button>
        <div id="complete-feedback" class="alert" style="display:none;margin-top:10px"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2@1.8.4/dist/quagga.min.js"></script>
<script>
(function () {
    const depositId = @json($deposit->id);
    const token = @json($token);
    const apiScan = @json(url("/deposito-bagagli/ritiro/{$deposit->id}/scan")) + '?t=' + encodeURIComponent(token);
    const apiComplete = @json(url("/deposito-bagagli/ritiro/{$deposit->id}/complete")) + '?t=' + encodeURIComponent(token);

    let scanned = @json($scannedTags);
    const expected = @json($expectedTags);

    const tagList = document.getElementById('tag-list');
    const countEl = document.getElementById('count-scanned');
    const completeBtn = document.getElementById('complete-btn');
    const scanFeedback = document.getElementById('scan-feedback');
    const completeFeedback = document.getElementById('complete-feedback');
    const csrf = @json(csrf_token());

    function showFeedback(el, text, type) {
        el.className = 'alert alert-' + type;
        el.textContent = text;
        el.style.display = 'block';
    }

    function syncUi() {
        const upper = scanned.map(t => t.toUpperCase());
        tagList.querySelectorAll('li').forEach(li => {
            const tag = li.dataset.tag;
            const done = upper.includes(tag.toUpperCase());
            li.classList.toggle('done', done);
            li.querySelector('.dot').textContent = done ? '✓' : '';
        });
        countEl.textContent = scanned.length + '/' + expected.length;
        completeBtn.disabled = scanned.length < expected.length;
    }

    async function registerTag(tag) {
        const res = await fetch(apiScan, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ tag }),
        });
        const json = await res.json();
        if (!json.success) {
            showFeedback(scanFeedback, json.error?.message || 'Tag non valido', 'error');
            return;
        }
        scanned = json.data.scanned;
        syncUi();
        showFeedback(scanFeedback, json.data.message, json.data.complete ? 'success' : 'info');
        if (window.navigator.vibrate) navigator.vibrate(80);
    }

    document.getElementById('manual-btn').addEventListener('click', () => {
        const tag = document.getElementById('manual-tag').value.trim();
        if (tag) registerTag(tag);
    });

    document.getElementById('manual-tag').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('manual-btn').click();
        }
    });

    completeBtn.addEventListener('click', async () => {
        completeBtn.disabled = true;
        const res = await fetch(apiComplete, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                paymentMethod: document.getElementById('payment-method').value,
                scannedTags: scanned,
            }),
        });
        const json = await res.json();
        if (!json.success) {
            showFeedback(completeFeedback, json.error?.message || 'Errore', 'error');
            completeBtn.disabled = false;
            return;
        }
        window.location.reload();
    });

    syncUi();

    if (typeof Quagga !== 'undefined') {
        Quagga.init({
            inputStream: {
                name: 'Live',
                type: 'LiveStream',
                target: document.querySelector('#scanner'),
                constraints: { facingMode: 'environment' },
            },
            decoder: { readers: ['code_128_reader', 'code_39_reader'] },
            locate: true,
        }, err => {
            if (err) {
                showFeedback(scanFeedback, 'Fotocamera non disponibile. Inserisci il tag manualmente.', 'error');
                return;
            }
            Quagga.start();
        });

        let lastCode = '';
        let lastAt = 0;
        Quagga.onDetected(result => {
            const code = (result.codeResult && result.codeResult.code || '').trim();
            if (!code || code === lastCode && Date.now() - lastAt < 2000) return;
            lastCode = code;
            lastAt = Date.now();
            registerTag(code);
        });
    }
})();
</script>
</body>
</html>
