<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AllegatoContrattoEnergia;
use App\Models\ContrattoEnergia;
use App\Models\ContrattoEnergiaMagicLink;
use App\Models\Notifica;
use App\Notifications\NotificaAdminDocumentiContrattoEnergiaRicevuti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContrattoEnergiaDocumentiController extends Controller
{
    private const TEMPLATE_FILENAME_PREFIX = 'Dichiarazione relativa al titolo di occupazione';

    private const DESTINATARIO_ADMIN_DOCUMENTI = 'ag.servizi16@gmail.com';

    public function downloadTemplate(): BinaryFileResponse
    {
        $path = $this->resolveTemplatePath();
        abort_if(! $path, 404, 'Documento non disponibile');

        return response()->download($path);
    }

    public function show(string $token)
    {
        $record = ContrattoEnergiaMagicLink::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('purpose', ContrattoEnergiaMagicLink::PURPOSE_RICHIESTA_DOCUMENTI)
            ->first();

        if (! $record) {
            abort(404);
        }

        $contratto = ContrattoEnergia::query()->findOrFail($record->contratto_energia_id);
        $templateUrl = route('frontend.contratto-energia.magic.template');
        [$activationLabel, $activationPlaceholder] = $this->activationLinkUiText($contratto);

        return view('Frontend.ContrattoEnergia.magic-link-upload', [
            'titoloPagina' => 'Caricamento documento firmato',
            'token' => $token,
            'magicLink' => $record,
            'contratto' => $contratto,
            'templateUrl' => $templateUrl,
            'activationLabel' => $activationLabel,
            'activationPlaceholder' => $activationPlaceholder,
            'canUpload' => $record->isUsable(),
            'alreadyUploaded' => (bool) $record->used_at,
            'isExpired' => $record->isExpired(),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $record = ContrattoEnergiaMagicLink::findUsableByPlainToken($token);
        if (! $record) {
            return redirect()
                ->route('frontend.contratto-energia.magic.show', ['token' => $token])
                ->withErrors(['Il link non è più valido. Richiedi un nuovo invio documenti.']);
        }

        $request->validate([
            'documenti_firmati' => ['required', 'array', 'min:1', 'max:10'],
            'documenti_firmati.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'link_attivazione_gestore' => ['required', 'url', 'max:2000'],
            'conferma_firma' => ['accepted'],
        ], [
            'documenti_firmati.required' => 'Carica almeno un documento firmato prima di inviare.',
            'documenti_firmati.min' => 'Carica almeno un documento firmato prima di inviare.',
            'documenti_firmati.max' => 'Puoi caricare massimo 10 allegati alla volta.',
            'link_attivazione_gestore.required' => 'Incolla il link di attivazione del gestore prima di inviare.',
            'link_attivazione_gestore.url' => 'Il link di attivazione non è valido.',
            'conferma_firma.accepted' => 'Devi confermare che il documento è stato firmato in tutte le parti obbligatorie.',
        ]);

        $contratto = ContrattoEnergia::query()->findOrFail($record->contratto_energia_id);
        $uploadedFiles = $request->file('documenti_firmati', []);
        $savedFiles = 0;
        $cartella = config('configurazione.allegati_contratti_energia.cartella');

        foreach ($uploadedFiles as $filePath) {
            $ext = strtolower((string) $filePath->extension());
            $savedName = Str::ulid().'.'.$ext;
            $filePath->storeAs($cartella, $savedName);

            $allegato = new AllegatoContrattoEnergia;
            $allegato->contratto_energia_id = $contratto->id;
            $allegato->uid = null;
            $allegato->path_filename = $cartella.'/'.$savedName;
            $allegato->filename_originale = 'VOLTURA_SUBENTRO_FIRMATO__'.$filePath->getClientOriginalName();
            $allegato->mime_type = $filePath->getMimeType();
            $allegato->dimensione_file = $filePath->getSize();
            $content = file_get_contents($filePath->getRealPath());
            $allegato->file_contenuto_base64 = $content !== false ? base64_encode($content) : null;
            $allegato->save();
            $savedFiles++;
        }

        $contratto->link_attivazione_gestore = trim((string) $request->input('link_attivazione_gestore'));
        $contratto->note = trim((string) $contratto->note."\n[".now()->format('d/m/Y H:i').'] Documenti voltura/subentro firmati caricati dal cliente via magic-link: '.$savedFiles.' allegato/i.');
        $contratto->save();

        $record->markUsed($request->ip());

        Notifica::notificaAdAdmin(
            'Documento cliente ricevuto',
            'Contratto energia #'.$contratto->id.' ('.$contratto->nominativo().') - caricamento documenti voltura/subentro firmati completato da magic-link: '.$savedFiles.' allegato/i.'
        );
        Notification::route('mail', self::DESTINATARIO_ADMIN_DOCUMENTI)
            ->notify(new NotificaAdminDocumentiContrattoEnergiaRicevuti($contratto, $savedFiles));

        return redirect()
            ->route('frontend.contratto-energia.magic.show', ['token' => $token])
            ->with('status', 'Documenti ricevuti correttamente. La pratica verrà completata dal backend.');
    }

    private function resolveTemplatePath(): ?string
    {
        $candidates = glob(base_path('docs/*.pdf')) ?: [];
        foreach ($candidates as $file) {
            if (Str::startsWith(basename($file), self::TEMPLATE_FILENAME_PREFIX)) {
                return $file;
            }
        }

        return null;
    }

    private function activationLinkUiText(ContrattoEnergia $contratto): array
    {
        $gestoreNome = strtolower((string) optional($contratto->gestore)->nome);

        if (Str::contains($gestoreNome, 'enel')) {
            return [
                'Link attivazione ENEL',
                'Incolla il link attivazione ENEL arrivato via email',
            ];
        }

        if (Str::contains($gestoreNome, 'a2a')) {
            return [
                'Link attivazione A2A',
                'Incolla il link di attivazione A2A arrivato via email/SMS',
            ];
        }

        return [
            'Link attivazione del gestore',
            'Incolla il link di attivazione ricevuto dal gestore (email/SMS)',
        ];
    }
}
