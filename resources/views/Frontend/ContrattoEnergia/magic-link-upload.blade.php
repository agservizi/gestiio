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
        .ce-dropzone {
            border: 3px dashed #3b82f6;
            border-radius: .625rem;
            background: #f5f9ff;
            padding: 2.5rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all .2s ease;
            box-shadow: inset 0 0 0 2px rgba(59, 130, 246, .08);
        }

        .ce-dropzone.drag {
            border-color: #2563eb;
            background: #eaf2ff;
            box-shadow: inset 0 0 0 2px rgba(37, 99, 235, .16);
        }

        .ce-dropzone:hover {
            border-color: #2563eb;
            background: #eef4ff;
        }

        .ce-files {
            margin-top: 1rem;
            border: 1px solid var(--bs-gray-300);
            border-radius: .625rem;
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
            gap: .75rem;
            padding: .75rem 1rem;
            background: var(--bs-white);
            border-bottom: 1px solid var(--bs-gray-200);
        }

        .ce-file:last-child {
            border-bottom: 0;
        }

        .ce-submit[disabled] {
            opacity: .55;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="app-default">
<div class="container-fluid py-8 px-6 px-lg-10">
    <div class="card mb-6 bg-primary">
        <div class="card-body py-8">
            <h1 class="text-white fw-bold mb-1 fs-2">Completamento pratica energia</h1>
            <div class="text-white opacity-75 fs-6">Carica i documenti firmati di voltura/subentro tramite link sicuro.</div>
        </div>
    </div>

    <div class="row g-6 mb-6">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex justify-content-between"><span class="text-gray-600">Pratica</span><span class="fw-bold">#{{ $contratto->id }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-gray-600">Cliente</span><span class="fw-bold">{{ $contratto->nominativo() }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-gray-600">Gestore</span><span class="fw-bold">{{ $contratto->gestore?->nome ?? '-' }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-gray-600">Email</span><span class="fw-bold">{{ $contratto->email }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-gray-600">Scadenza link</span><span class="fw-bold">{{ optional($magicLink->expires_at)->format('d/m/Y H:i') }}</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="fw-bold fs-6 mb-2">Documento da compilare e firmare</div>
                    <a class="btn btn-light-primary mb-3" href="{{ $templateUrl }}">Scarica modulo PDF</a>
                    <div class="text-gray-600 fs-7">Compila e firma il modulo, poi caricalo insieme agli altri allegati necessari.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
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

        @if($alreadyUploaded)
            <div class="alert alert-success mb-0">Documenti già ricevuti. Il backend procederà al completamento della pratica.</div>
        @elseif($isExpired)
            <div class="alert alert-warning mb-0">Questo link è scaduto. Richiedi un nuovo invio documenti.</div>
        @elseif($canUpload)
            <form method="POST" enctype="multipart/form-data" action="{{ route('frontend.contratto-energia.magic.store', ['token' => $token]) }}" id="ce-form-upload">
                @csrf

                <input type="file" class="d-none" id="documenti_firmati" name="documenti_firmati[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple required>

                <div class="ce-dropzone" id="ce-dropzone">
                    <div class="fw-bold mb-1 fs-6">Trascina qui i documenti firmati</div>
                    <div class="text-gray-600 fs-7 mb-2">oppure clicca per selezionare più allegati</div>
                    <span class="badge badge-light-success">Formati: PDF, JPG, PNG, WEBP · max 10MB per file</span>
                </div>

                <div class="ce-files" id="ce-files"></div>

                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" value="1" id="conferma_firma" name="conferma_firma" required>
                    <label class="form-check-label" for="conferma_firma">
                        Confermo di aver firmato il documento in tutte le parti obbligatorie.
                    </label>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5">
                    <div class="text-gray-600 fs-7">Il link è monouso: dopo l'invio non potrà essere riutilizzato.</div>
                    <button type="submit" class="btn btn-primary ce-submit" id="ce-submit" disabled>Invia documenti</button>
                </div>
            </form>
        @else
            <div class="alert alert-warning mb-0">Questo link non è più valido.</div>
        @endif
        </div>
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
