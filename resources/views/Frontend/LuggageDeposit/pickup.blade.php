<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Ritiro {{ $deposit->code }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:400,600,700">
    <style>
        :root {
            --ok: #16a36f;
            --ink: #07111f;
            --muted: #5d6b82;
            --line: #dce7f3;
            --brand: #0795e8;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0;
            height: 100%;
            overflow: hidden;
            font-family: Inter, Arial, sans-serif;
            background: #0b1220;
            color: var(--ink);
            touch-action: manipulation;
        }
        .app {
            height: 100dvh;
            max-height: 100dvh;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto;
            background: #f4f8fc;
        }
        .top {
            padding: max(10px, env(safe-area-inset-top)) 14px 10px;
            background: #fff;
            border-bottom: 1px solid var(--line);
        }
        .top-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }
        .eyebrow { font-size: .68rem; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
        h1 { margin: 2px 0 0; font-size: 1.05rem; line-height: 1.2; }
        .meta { font-size: .78rem; color: var(--muted); margin-top: 2px; }
        .badge-count {
            flex-shrink: 0;
            min-width: 54px;
            text-align: center;
            background: #eef6ff;
            color: #045f9b;
            border-radius: 12px;
            padding: 8px 10px;
            font-weight: 700;
            font-size: .95rem;
        }
        .badge-count span { display: block; font-size: .62rem; font-weight: 600; color: var(--muted); }
        .scan-pane {
            position: relative;
            min-height: 0;
            background: #000;
            overflow: hidden;
        }
        #scanner {
            width: 100%;
            height: 100%;
        }
        #scanner video, #scanner canvas {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }
        .scan-overlay {
            pointer-events: none;
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .scan-frame {
            width: 78%;
            max-width: 320px;
            aspect-ratio: 1.6 / 1;
            border: 2px solid rgba(255,255,255,.85);
            border-radius: 12px;
            box-shadow: 0 0 0 9999px rgba(0,0,0,.35);
        }
        .scan-hint {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 10px;
            text-align: center;
            color: #fff;
            font-size: .72rem;
            font-weight: 600;
            text-shadow: 0 1px 4px rgba(0,0,0,.6);
            padding: 0 12px;
        }
        .bottom {
            background: #fff;
            border-top: 1px solid var(--line);
            padding: 10px 12px max(12px, env(safe-area-inset-bottom));
        }
        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            max-height: 56px;
            overflow-y: auto;
            margin-bottom: 8px;
        }
        .tag-chip {
            font-size: .72rem;
            font-weight: 700;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f1f5f9;
            color: var(--muted);
            border: 1px solid var(--line);
        }
        .tag-chip.done {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }
        .manual {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            margin-bottom: 8px;
        }
        .manual input {
            min-width: 0;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: .92rem;
        }
        .manual button, .btn-action {
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            font-size: .92rem;
            cursor: pointer;
        }
        .manual button { background: #e8eef5; color: var(--ink); }
        .actions {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 8px;
            align-items: stretch;
        }
        select {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 8px;
            font-size: .88rem;
            background: #fff;
        }
        .btn-primary {
            background: var(--brand);
            color: #fff;
        }
        .btn-primary:disabled { opacity: .45; cursor: not-allowed; }
        .feedback {
            display: none;
            margin-top: 8px;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: .78rem;
            font-weight: 600;
        }
        .feedback.info { display: block; background: #eef6ff; color: #045f9b; }
        .feedback.error { display: block; background: #fef2f2; color: #b91c1c; }
        .feedback.success { display: block; background: #ecfdf5; color: #047857; }
    </style>
</head>
<body>
<div class="app">
    <header class="top">
        <div class="top-row">
            <div>
                <div class="eyebrow">Ritiro bagagli</div>
                <h1>{{ $deposit->customer_name }}</h1>
                <div class="meta">{{ $deposit->code }} · € {{ number_format($pricing['total'], 2, ',', '.') }} · {{ $pricing['days'] }} gg</div>
            </div>
            <div class="badge-count" id="count-scanned">{{ count($scannedTags) }}/{{ count($expectedTags) }}<span>tag</span></div>
        </div>
    </header>

    <section class="scan-pane">
        <div id="scanner"></div>
        <div class="scan-overlay">
            <div class="scan-frame"></div>
        </div>
        <div class="scan-hint">Inquadra il barcode CODE 128 sul tag</div>
    </section>

    <footer class="bottom">
        <div class="tags" id="tag-list">
            @foreach($expectedTags as $tag)
                <span class="tag-chip {{ in_array($tag, $scannedTags, true) ? 'done' : '' }}" data-tag="{{ $tag }}">{{ $tag }}</span>
            @endforeach
        </div>
        <div class="manual">
            <input type="text" id="manual-tag" placeholder="Tag manuale LB-XXXX-A" autocomplete="off" autocapitalize="characters" enterkeyhint="go">
            <button type="button" id="manual-btn">OK</button>
        </div>
        <div class="actions">
            <select id="payment-method" aria-label="Metodo pagamento">
                <option>Contanti</option>
                <option>Carta</option>
                <option>Bonifico</option>
            </select>
            <button type="button" class="btn-action btn-primary" id="complete-btn" disabled>Completa ritiro</button>
        </div>
        <div id="scan-feedback" class="feedback"></div>
        <div id="complete-feedback" class="feedback"></div>
    </footer>
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

    let audioCtx = null;

    function getAudioCtx() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        return audioCtx;
    }

    function unlockAudio() {
        const ctx = getAudioCtx();
        if (ctx.state === 'suspended') {
            ctx.resume();
        }
    }

    document.addEventListener('touchstart', unlockAudio, { once: true, passive: true });
    document.addEventListener('click', unlockAudio, { once: true });

    function playScanBeep() {
        try {
            const ctx = getAudioCtx();
            if (ctx.state === 'suspended') {
                ctx.resume();
            }
            const t = ctx.currentTime;
            const tones = [
                { freq: 1400, start: 0, dur: 0.18 },
                { freq: 1800, start: 0.14, dur: 0.22 },
            ];
            tones.forEach(({ freq, start, dur }) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'square';
                osc.frequency.setValueAtTime(freq, t + start);
                gain.gain.setValueAtTime(0.0001, t + start);
                gain.gain.exponentialRampToValueAtTime(0.92, t + start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, t + start + dur);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(t + start);
                osc.stop(t + start + dur);
            });
        } catch (e) {
            /* audio non disponibile */
        }
    }

    function showFeedback(el, text, type) {
        el.className = 'feedback ' + type;
        el.textContent = text;
    }

    function hideFeedback(el) {
        el.className = 'feedback';
        el.textContent = '';
    }

    function syncUi() {
        const upper = scanned.map(t => t.toUpperCase());
        tagList.querySelectorAll('.tag-chip').forEach(chip => {
            const tag = chip.dataset.tag;
            chip.classList.toggle('done', upper.includes(tag.toUpperCase()));
        });
        countEl.innerHTML = scanned.length + '/' + expected.length + '<span>tag</span>';
        completeBtn.disabled = scanned.length < expected.length;
    }

    async function registerTag(tag) {
        hideFeedback(completeFeedback);
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
        playScanBeep();
        if (window.navigator.vibrate) navigator.vibrate([120, 60, 120]);
    }

    document.getElementById('manual-btn').addEventListener('click', () => {
        const tag = document.getElementById('manual-tag').value.trim();
        if (tag) {
            registerTag(tag);
            document.getElementById('manual-tag').value = '';
        }
    });

    document.getElementById('manual-tag').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('manual-btn').click();
        }
    });

    completeBtn.addEventListener('click', async () => {
        completeBtn.disabled = true;
        hideFeedback(scanFeedback);
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
                constraints: {
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
            },
            locator: { patchSize: 'medium', halfSample: true },
            decoder: { readers: ['code_128_reader'] },
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
            if (!code || (code === lastCode && Date.now() - lastAt < 2000)) return;
            lastCode = code;
            lastAt = Date.now();
            registerTag(code);
        });
    }
})();
</script>
</body>
</html>
