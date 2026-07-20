<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Ritiro {{ $package->code }}</title>
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
        .badge-status {
            flex-shrink: 0;
            text-align: center;
            background: #eef6ff;
            color: #045f9b;
            border-radius: 12px;
            padding: 8px 10px;
            font-weight: 700;
            font-size: .78rem;
            max-width: 110px;
        }
        .badge-status.done {
            background: #ecfdf5;
            color: #047857;
        }
        .main-pane {
            position: relative;
            min-height: 0;
            overflow: hidden;
        }
        .scan-pane {
            position: absolute;
            inset: 0;
            background: #000;
        }
        .scan-pane.hidden { display: none; }
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
        .signature-pane {
            position: absolute;
            inset: 0;
            display: none;
            flex-direction: column;
            background: #fff;
            padding: 14px;
            overflow-y: auto;
        }
        .signature-pane.active { display: flex; }
        .signature-pane h2 {
            margin: 0 0 4px;
            font-size: 1rem;
        }
        .signature-pane p {
            margin: 0 0 12px;
            font-size: .78rem;
            color: var(--muted);
        }
        .signer-field {
            margin-bottom: 10px;
        }
        .signer-field label {
            display: block;
            font-size: .72rem;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .signer-field input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: .92rem;
        }
        .canvas-wrap {
            flex: 1;
            min-height: 160px;
            border: 2px dashed var(--line);
            border-radius: 12px;
            background: #fafcff;
            position: relative;
            touch-action: none;
        }
        #signature-canvas {
            display: block;
            width: 100%;
            height: 100%;
            border-radius: 10px;
            cursor: crosshair;
        }
        .canvas-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
        }
        .canvas-actions button {
            border: 0;
            background: #e8eef5;
            color: var(--ink);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: .82rem;
            font-weight: 700;
            cursor: pointer;
        }
        .bottom {
            background: #fff;
            border-top: 1px solid var(--line);
            padding: 10px 12px max(12px, env(safe-area-inset-bottom));
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
        .bottom-scan.hidden,
        .bottom-signature { display: none; }
        .bottom-signature.active { display: block; }
    </style>
</head>
<body>
<div class="app">
    <header class="top">
        <div class="top-row">
            <div>
                <div class="eyebrow">Ritiro pacco</div>
                <h1>{{ $package->recipient_name }}</h1>
                <div class="meta">{{ $package->code }} · € {{ number_format($pricing['total'], 2, ',', '.') }} · {{ $pricing['days'] }} gg</div>
            </div>
            <div class="badge-status" id="scan-status">Scansiona</div>
        </div>
    </header>

    <section class="main-pane">
        <div class="scan-pane" id="scan-pane">
            <div id="scanner"></div>
            <div class="scan-overlay">
                <div class="scan-frame"></div>
            </div>
            <div class="scan-hint">Inquadra il barcode CODE 128 sul codice pacco</div>
        </div>

        <div class="signature-pane" id="signature-pane">
            <h2>Firma per ricevuta</h2>
            <p>Il destinatario firma qui sotto per confermare la consegna del pacco.</p>
            <div class="signer-field">
                <label for="signer-name">Nome e cognome firmatario *</label>
                <input type="text" id="signer-name" placeholder="Es. Mario Rossi" autocomplete="name" value="{{ $package->recipient_name }}">
            </div>
            <div class="canvas-wrap">
                <canvas id="signature-canvas"></canvas>
            </div>
            <div class="canvas-actions">
                <button type="button" id="clear-signature">Cancella firma</button>
            </div>
        </div>
    </section>

    <footer class="bottom">
        <div class="bottom-scan" id="bottom-scan">
            <div class="manual">
                <input type="text" id="manual-code" placeholder="Codice pacco {{ $package->code }}" autocomplete="off" autocapitalize="characters" enterkeyhint="go">
                <button type="button" id="manual-btn">OK</button>
            </div>
            <div id="scan-feedback" class="feedback"></div>
        </div>

        <div class="bottom-signature" id="bottom-signature">
            <div class="actions">
                <select id="payment-method" aria-label="Metodo pagamento">
                    <option>Contanti</option>
                    <option>Carta</option>
                    <option>Bonifico</option>
                </select>
                <button type="button" class="btn-action btn-primary" id="complete-btn" disabled>Completa consegna</button>
            </div>
            <div id="complete-feedback" class="feedback"></div>
        </div>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2@1.8.4/dist/quagga.min.js"></script>
<script>
(function () {
    const packageId = @json($package->id);
    const token = @json($token);
    const expectedCode = @json(strtoupper($package->code));
    const apiScan = @json(url("/locker-point/ritiro/{$package->id}/scan")) + '?t=' + encodeURIComponent(token);
    const apiComplete = @json(url("/locker-point/ritiro/{$package->id}/complete")) + '?t=' + encodeURIComponent(token);

    let scanned = @json(array_map('strtoupper', $scannedTags));
    let scanComplete = scanned.includes(expectedCode);
    let quaggaStarted = false;
    let signatureDirty = false;

    const scanPane = document.getElementById('scan-pane');
    const signaturePane = document.getElementById('signature-pane');
    const bottomScan = document.getElementById('bottom-scan');
    const bottomSignature = document.getElementById('bottom-signature');
    const scanStatus = document.getElementById('scan-status');
    const completeBtn = document.getElementById('complete-btn');
    const scanFeedback = document.getElementById('scan-feedback');
    const completeFeedback = document.getElementById('complete-feedback');
    const signerNameInput = document.getElementById('signer-name');
    const canvas = document.getElementById('signature-canvas');
    const ctx = canvas.getContext('2d');
    const csrf = @json(csrf_token());

    let audioCtx = null;
    let drawing = false;
    let lastPoint = null;

    function getAudioCtx() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        return audioCtx;
    }

    function unlockAudio() {
        const ctxAudio = getAudioCtx();
        if (ctxAudio.state === 'suspended') {
            ctxAudio.resume();
        }
    }

    document.addEventListener('touchstart', unlockAudio, { once: true, passive: true });
    document.addEventListener('click', unlockAudio, { once: true });

    function playScanBeep() {
        try {
            const ctxAudio = getAudioCtx();
            if (ctxAudio.state === 'suspended') {
                ctxAudio.resume();
            }
            const t = ctxAudio.currentTime;
            const tones = [
                { freq: 1400, start: 0, dur: 0.18 },
                { freq: 1800, start: 0.14, dur: 0.22 },
            ];
            tones.forEach(({ freq, start, dur }) => {
                const osc = ctxAudio.createOscillator();
                const gain = ctxAudio.createGain();
                osc.type = 'square';
                osc.frequency.setValueAtTime(freq, t + start);
                gain.gain.setValueAtTime(0.0001, t + start);
                gain.gain.exponentialRampToValueAtTime(0.92, t + start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, t + start + dur);
                osc.connect(gain);
                gain.connect(ctxAudio.destination);
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

    function stopScanner() {
        if (typeof Quagga !== 'undefined' && quaggaStarted) {
            try {
                Quagga.stop();
            } catch (e) {
                /* già fermato */
            }
            quaggaStarted = false;
        }
    }

    function resizeCanvas() {
        const wrap = canvas.parentElement;
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const width = wrap.clientWidth;
        const height = Math.max(wrap.clientHeight, 160);
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.lineWidth = 2.5;
        ctx.strokeStyle = '#07111f';
    }

    function canvasPoint(event) {
        const rect = canvas.getBoundingClientRect();
        const clientX = event.touches ? event.touches[0].clientX : event.clientX;
        const clientY = event.touches ? event.touches[0].clientY : event.clientY;
        return {
            x: clientX - rect.left,
            y: clientY - rect.top,
        };
    }

    function startDraw(event) {
        event.preventDefault();
        drawing = true;
        lastPoint = canvasPoint(event);
    }

    function draw(event) {
        if (!drawing) return;
        event.preventDefault();
        const point = canvasPoint(event);
        ctx.beginPath();
        ctx.moveTo(lastPoint.x, lastPoint.y);
        ctx.lineTo(point.x, point.y);
        ctx.stroke();
        lastPoint = point;
        signatureDirty = true;
        syncCompleteButton();
    }

    function endDraw(event) {
        if (!drawing) return;
        event.preventDefault();
        drawing = false;
        lastPoint = null;
    }

    function clearSignature() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        signatureDirty = false;
        syncCompleteButton();
    }

    function syncCompleteButton() {
        const nameOk = signerNameInput.value.trim().length > 0;
        completeBtn.disabled = !(scanComplete && signatureDirty && nameOk);
    }

    function showSignatureStep() {
        scanComplete = true;
        stopScanner();
        scanPane.classList.add('hidden');
        signaturePane.classList.add('active');
        bottomScan.classList.add('hidden');
        bottomSignature.classList.add('active');
        scanStatus.textContent = 'Verificato';
        scanStatus.classList.add('done');
        resizeCanvas();
        syncCompleteButton();
    }

    async function registerCode(code) {
        if (scanComplete) return;

        hideFeedback(completeFeedback);
        const res = await fetch(apiScan, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ tag: code }),
        });
        const json = await res.json();
        if (!json.success) {
            showFeedback(scanFeedback, json.error?.message || 'Codice non valido', 'error');
            return;
        }
        scanned = json.data.scanned.map(t => t.toUpperCase());
        showFeedback(scanFeedback, json.data.message, json.data.complete ? 'success' : 'info');
        playScanBeep();
        if (window.navigator.vibrate) navigator.vibrate([120, 60, 120]);

        if (json.data.complete) {
            showSignatureStep();
        }
    }

    document.getElementById('manual-btn').addEventListener('click', () => {
        const code = document.getElementById('manual-code').value.trim();
        if (code) {
            registerCode(code);
            document.getElementById('manual-code').value = '';
        }
    });

    document.getElementById('manual-code').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('manual-btn').click();
        }
    });

    document.getElementById('clear-signature').addEventListener('click', clearSignature);
    signerNameInput.addEventListener('input', syncCompleteButton);

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', endDraw);
    canvas.addEventListener('mouseleave', endDraw);
    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', endDraw, { passive: false });
    canvas.addEventListener('touchcancel', endDraw, { passive: false });

    window.addEventListener('resize', () => {
        if (signaturePane.classList.contains('active')) {
            const snapshot = canvas.toDataURL('image/png');
            resizeCanvas();
            if (signatureDirty) {
                const img = new Image();
                img.onload = () => ctx.drawImage(img, 0, 0, canvas.width / (window.devicePixelRatio || 1), canvas.height / (window.devicePixelRatio || 1));
                img.src = snapshot;
            }
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
                signature: canvas.toDataURL('image/png'),
                signerName: signerNameInput.value.trim(),
            }),
        });
        const json = await res.json();
        if (!json.success) {
            showFeedback(completeFeedback, json.error?.message || 'Errore', 'error');
            syncCompleteButton();
            return;
        }
        window.location.reload();
    });

    if (scanComplete) {
        showSignatureStep();
    } else if (typeof Quagga !== 'undefined') {
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
                showFeedback(scanFeedback, 'Fotocamera non disponibile. Inserisci il codice manualmente.', 'error');
                return;
            }
            Quagga.start();
            quaggaStarted = true;
        });

        let lastCode = '';
        let lastAt = 0;
        Quagga.onDetected(result => {
            const code = (result.codeResult && result.codeResult.code || '').trim();
            if (!code || (code === lastCode && Date.now() - lastAt < 2000)) return;
            lastCode = code;
            lastAt = Date.now();
            registerCode(code);
        });
    }
})();
</script>
</body>
</html>
