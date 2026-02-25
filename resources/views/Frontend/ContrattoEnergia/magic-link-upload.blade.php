<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="_token" content="{{ csrf_token() }}">
    <title>{{ $titoloPagina ?? 'Caricamento documento firmato' }}</title>
    <link href="/assets_backend/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="/assets_backend/css/style10.bundle.css" rel="stylesheet" type="text/css"/>
    <style>
        :root {
            --ce-bg: #f5f7fb;
            --ce-card: #ffffff;
            --ce-border: #e3e8f1;
            --ce-title: #10233f;
            --ce-muted: #5f6f88;
            --ce-primary: #0b57d0;
            --ce-primary-soft: #e9f1ff;
            --ce-success-soft: #e8f8ef;
            --ce-danger-soft: #fdecee;
        }

        body {
            background: radial-gradient(circle at 0% 0%, #edf3ff 0%, var(--ce-bg) 45%);
            color: var(--ce-title);
        }

        .ce-wrap {
            width: min(980px, calc(100vw - 24px));
            margin: 32px auto;
        }

        .ce-hero {
            padding: 24px 28px;
            background: linear-gradient(135deg, #0d2f66 0%, #0b57d0 55%, #3b82f6 100%);
            color: #fff;
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(16, 35, 63, .07);
        }

        .ce-title {
            font-size: clamp(1.3rem, 2vw, 1.9rem);
            font-weight: 700;
            margin-bottom: 4px;
        }

        .ce-subtitle {
            opacity: .9;
            font-size: .95rem;
        }

        .ce-content {
            padding: 22px 4px 26px;
        }

        .ce-grid {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 14px;
            margin-bottom: 18px;
        }

        .ce-info,
        .ce-actions {
            border: 1px solid var(--ce-border);
            border-radius: 14px;
            padding: 14px;
            background: #fff;
        }

        .ce-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: .92rem;
            padding: 4px 0;
        }

        .ce-row .k {
            color: var(--ce-muted);
        }

        .ce-dropzone {
            border: 2px dashed #98b3e3;
            border-radius: 14px;
            background: var(--ce-primary-soft);
            padding: 24px;
            text-align: center;
            transition: .2s ease;
            cursor: pointer;
        }

        .ce-dropzone.drag {
            border-color: var(--ce-primary);
            background: #dceaff;
            transform: translateY(-1px);
        }

        .ce-files {
            margin-top: 14px;
            border: 1px solid var(--ce-border);
            border-radius: 12px;
            overflow: hidden;
            display: none;
        }

        .ce-files.show {
            display: block;
        }

        .ce-file {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: #fff;
            border-bottom: 1px solid var(--ce-border);
            font-size: .9rem;
        }

        .ce-file:last-child {
            border-bottom: 0;
        }

        .ce-chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: .78rem;
            font-weight: 600;
        }

        .ce-chip.success {
            background: var(--ce-success-soft);
            color: #0f5132;
        }

        .ce-chip.danger {
            background: var(--ce-danger-soft);
            color: #842029;
        }

        .ce-footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .ce-submit[disabled] {
            opacity: .55;
            cursor: not-allowed;
        }

        @media (max-width: 900px) {
            .ce-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="ce-wrap">
    <div class="ce-hero">
        <div class="ce-title">Completamento pratica energia</div>
        <div class="ce-subtitle">Carica i documenti firmati di voltura/subentro tramite link sicuro.</div>
    </div>

    <div class="ce-content">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="ce-grid">
            <div class="ce-info">
                <div class="ce-row"><span class="k">Pratica</span><strong>#{{ $contratto->id }}</strong></div>
                <div class="ce-row"><span class="k">Cliente</span><strong>{{ $contratto->nominativo() }}</strong></div>
                <div class="ce-row"><span class="k">Gestore</span><strong>{{ $contratto->gestore?->nome ?? '-' }}</strong></div>
                <div class="ce-row"><span class="k">Email</span><strong>{{ $contratto->email }}</strong></div>
                <div class="ce-row"><span class="k">Scadenza link</span><strong>{{ optional($magicLink->expires_at)->format('d/m/Y H:i') }}</strong></div>
            </div>
            <div class="ce-actions">
                <div class="fw-semibold mb-2">Documento da compilare e firmare</div>
                <a class="btn btn-light-primary w-100 mb-2" href="{{ $templateUrl }}">Scarica modulo PDF</a>
                <div class="text-muted fs-8">Compila e firma il modulo, poi caricalo insieme agli altri allegati necessari.</div>
            </div>
        </div>

        @if($alreadyUploaded)
            <div class="alert alert-success mb-0">Documenti già ricevuti. Il backend procederà al completamento della pratica.</div>
        @elseif($isExpired)
            <div class="alert alert-warning mb-0">Questo link è scaduto. Richiedi un nuovo invio documenti.</div>
        @elseif($canUpload)
            <form method="POST" enctype="multipart/form-data" action="{{ route('frontend.contratto-energia.magic.store', ['token' => $token]) }}" id="ce-form-upload">
                @csrf

                <input type="file" class="d-none" id="documenti_firmati" name="documenti_firmati[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple required>

                <div class="ce-dropzone" id="ce-dropzone">
                    <div class="fs-6 fw-bold mb-1">Trascina qui i documenti firmati</div>
                    <div class="text-muted fs-8 mb-3">oppure clicca per selezionare più allegati</div>
                    <span class="ce-chip success">Formati: PDF, JPG, PNG, WEBP · max 10MB per file</span>
                </div>

                <div class="ce-files" id="ce-files"></div>

                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" value="1" id="conferma_firma" name="conferma_firma" required>
                    <label class="form-check-label" for="conferma_firma">
                        Confermo di aver firmato il documento in tutte le parti obbligatorie.
                    </label>
                </div>

                <div class="ce-footer">
                    <div class="text-muted fs-8">Il link è monouso: dopo l'invio non potrà essere riutilizzato.</div>
                    <button type="submit" class="btn btn-primary ce-submit" id="ce-submit" disabled>Invia documenti</button>
                </div>
            </form>
        @else
            <div class="alert alert-warning mb-0">Questo link non è più valido.</div>
        @endif
    </div>
</div>

<script src="/assets_backend/plugins/global/plugins.bundle.js"></script>
<script>
    (function () {
        var form = document.getElementById('ce-form-upload');
        if (!form) return;

        var dropzone = document.getElementById('ce-dropzone');
        var fileInput = document.getElementById('documenti_firmati');
        var filesBox = document.getElementById('ce-files');
        var check = document.getElementById('conferma_firma');
        var submitBtn = document.getElementById('ce-submit');

        function bytesToSize(bytes) {
            if (!bytes) return '0 B';
            var units = ['B', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(1024));
            i = Math.min(i, units.length - 1);
            return (bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
        }

        function renderFiles() {
            var files = Array.from(fileInput.files || []);
            filesBox.innerHTML = '';

            if (files.length === 0) {
                filesBox.classList.remove('show');
            } else {
                filesBox.classList.add('show');
                files.forEach(function (file, index) {
                    var row = document.createElement('div');
                    row.className = 'ce-file';
                    row.innerHTML = '<div><strong>' + file.name + '</strong><div class="text-muted fs-8">' + bytesToSize(file.size) + '</div></div>' +
                        '<button type="button" class="btn btn-sm btn-light-danger" data-remove-index="' + index + '">Rimuovi</button>';
                    filesBox.appendChild(row);
                });
            }

            refreshSubmitState();
        }

        function refreshSubmitState() {
            var hasFiles = (fileInput.files || []).length > 0;
            var accepted = !!check.checked;
            submitBtn.disabled = !(hasFiles && accepted);
        }

        function setFilesFromList(fileList) {
            var dt = new DataTransfer();
            Array.from(fileInput.files || []).forEach(function (f) {
                dt.items.add(f);
            });
            Array.from(fileList || []).forEach(function (f) {
                dt.items.add(f);
            });
            fileInput.files = dt.files;
            renderFiles();
        }

        dropzone.addEventListener('click', function () {
            fileInput.click();
        });

        dropzone.addEventListener('dragover', function (e) {
            e.preventDefault();
            dropzone.classList.add('drag');
        });

        dropzone.addEventListener('dragleave', function () {
            dropzone.classList.remove('drag');
        });

        dropzone.addEventListener('drop', function (e) {
            e.preventDefault();
            dropzone.classList.remove('drag');
            if (e.dataTransfer && e.dataTransfer.files) {
                setFilesFromList(e.dataTransfer.files);
            }
        });

        fileInput.addEventListener('change', function () {
            renderFiles();
        });

        filesBox.addEventListener('click', function (e) {
            var target = e.target.closest('[data-remove-index]');
            if (!target) return;

            var index = parseInt(target.getAttribute('data-remove-index'), 10);
            var dt = new DataTransfer();
            Array.from(fileInput.files || []).forEach(function (f, i) {
                if (i !== index) {
                    dt.items.add(f);
                }
            });
            fileInput.files = dt.files;
            renderFiles();
        });

        check.addEventListener('change', refreshSubmitState);

        form.addEventListener('submit', function (e) {
            refreshSubmitState();
            if (submitBtn.disabled) {
                e.preventDefault();
            }
        });

        renderFiles();
    })();
</script>
</body>
</html>
