<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="_token" content="{{ csrf_token() }}">
    <title>{{ $titoloPagina ?? 'Caricamento documento firmato' }}</title>
    <link href="/assets_backend/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="/assets_backend/css/style10.bundle.css" rel="stylesheet" type="text/css"/>
</head>
<body class="bg-light">
<div class="container py-8" style="max-width: 860px;">
    <div class="card shadow-sm">
        <div class="card-body p-8">
            <h1 class="fs-2 fw-bold mb-2">Caricamento documento firmato</h1>
            <p class="text-muted mb-6">Carica il documento <strong>voltura/subentro</strong> dopo averlo firmato in tutte le parti obbligatorie.</p>

            <div class="mb-5 p-4 rounded border bg-light-primary">
                <div class="fw-semibold">Pratica energia #{{ $contratto->id }}</div>
                <div class="text-muted">Cliente: {{ $contratto->nominativo() }}</div>
                <div class="text-muted">Gestore: {{ $contratto->gestore?->nome ?? '-' }}</div>
                <div class="text-muted">Email: {{ $contratto->email }}</div>
                <div class="text-muted">Scadenza link: {{ optional($magicLink->expires_at)->format('d/m/Y H:i') }}</div>
            </div>

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
                <div class="alert alert-success mb-0">
                    Documento già ricevuto. Il backend procederà al completamento della pratica.
                </div>
            @elseif($isExpired)
                <div class="alert alert-warning mb-0">
                    Questo link è scaduto. Richiedi un nuovo invio documenti.
                </div>
            @elseif($canUpload)
                <form method="POST" enctype="multipart/form-data" action="{{ route('frontend.contratto-energia.magic.store', ['token' => $token]) }}">
                    @csrf

                    <div class="mb-5">
                        <label for="documento_firmato" class="form-label fw-semibold">Documento voltura/subentro firmato</label>
                        <input type="file" class="form-control" id="documento_firmato" name="documento_firmato" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                        <div class="form-text">Formati ammessi: PDF, JPG, PNG, WEBP. Max 10MB.</div>
                    </div>

                    <div class="form-check mb-6">
                        <input class="form-check-input" type="checkbox" value="1" id="conferma_firma" name="conferma_firma" required>
                        <label class="form-check-label" for="conferma_firma">
                            Confermo di aver firmato il documento in tutte le parti obbligatorie.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">Invia documento</button>
                </form>
            @else
                <div class="alert alert-warning mb-0">Questo link non è più valido.</div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
