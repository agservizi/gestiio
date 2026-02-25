<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AllegatoContrattoEnergia;
use App\Models\ContrattoEnergia;
use App\Models\ContrattoEnergiaMagicLink;
use App\Models\Notifica;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContrattoEnergiaDocumentiController extends Controller
{
    public function show(string $token)
    {
        $record = ContrattoEnergiaMagicLink::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('purpose', ContrattoEnergiaMagicLink::PURPOSE_RICHIESTA_DOCUMENTI)
            ->first();

        if (!$record) {
            abort(404);
        }

        $contratto = ContrattoEnergia::query()->findOrFail($record->contratto_energia_id);

        return view('Frontend.ContrattoEnergia.magic-link-upload', [
            'titoloPagina' => 'Caricamento documento firmato',
            'token' => $token,
            'magicLink' => $record,
            'contratto' => $contratto,
            'canUpload' => $record->isUsable(),
            'alreadyUploaded' => (bool) $record->used_at,
            'isExpired' => $record->isExpired(),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $record = ContrattoEnergiaMagicLink::findUsableByPlainToken($token);
        if (!$record) {
            return redirect()
                ->route('frontend.contratto-energia.magic.show', ['token' => $token])
                ->withErrors(['Il link non è più valido. Richiedi un nuovo invio documenti.']);
        }

        $request->validate([
            'documento_firmato' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'conferma_firma' => ['accepted'],
        ], [
            'documento_firmato.required' => 'Carica il documento firmato prima di inviare.',
            'conferma_firma.accepted' => 'Devi confermare che il documento è stato firmato in tutte le parti obbligatorie.',
        ]);

        $contratto = ContrattoEnergia::query()->findOrFail($record->contratto_energia_id);
        $filePath = $request->file('documento_firmato');

        $ext = strtolower((string) $filePath->extension());
        $savedName = Str::ulid() . '.' . $ext;
        $cartella = config('configurazione.allegati_contratti_energia.cartella');
        $filePath->storeAs($cartella, $savedName);

        $allegato = new AllegatoContrattoEnergia();
        $allegato->contratto_energia_id = $contratto->id;
        $allegato->uid = null;
        $allegato->path_filename = $cartella . '/' . $savedName;
        $allegato->filename_originale = 'VOLTURA_SUBENTRO_FIRMATO__' . $filePath->getClientOriginalName();
        $allegato->mime_type = $filePath->getMimeType();
        $allegato->dimensione_file = $filePath->getSize();
        $content = file_get_contents($filePath->getRealPath());
        $allegato->file_contenuto_base64 = $content !== false ? base64_encode($content) : null;
        $allegato->save();

        $contratto->note = trim((string) $contratto->note . "\n[" . now()->format('d/m/Y H:i') . "] Documento voltura/subentro firmato caricato dal cliente via magic-link.");
        $contratto->save();

        $record->markUsed($request->ip());

        Notifica::notificaAdAdmin(
            'Documento cliente ricevuto',
            'Contratto energia #' . $contratto->id . ' (' . $contratto->nominativo() . ') - caricamento documento voltura/subentro firmato completato da magic-link.'
        );

        return redirect()
            ->route('frontend.contratto-energia.magic.show', ['token' => $token])
            ->with('status', 'Documento ricevuto correttamente. La pratica verrà completata dal backend.');
    }
}
