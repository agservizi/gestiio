<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\SensitiveFileService;
use App\Models\AllegatoServizio;
use App\Models\Visura;
use App\Support\VisuraAttachmentMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AllegatoServizioController extends Controller
{
    public function downloadAllegato($contrattoId, $allegatoId)
    {

        $record = AllegatoServizio::find($allegatoId);
        abort_if(! $record, 404, 'Questo allegato non esiste');
        abort_if($record->allegato_id != $contrattoId, 404, 'Questo allegato non esiste');

        if ($record->file_contenuto_base64) {
            $contenuto = base64_decode($record->file_contenuto_base64, true);
            if ($contenuto !== false) {
                return response($contenuto, 200, [
                    'Content-Type' => $record->mime_type ?: 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename="'.addslashes($record->filename_originale).'"',
                ]);
            }
        }

        return app(SensitiveFileService::class)->download(
            (string) $record->path_filename,
            (string) $record->filename_originale,
            ['model' => AllegatoServizio::class, 'id' => $record->id]
        );

    }

    public function downloadAllegatoCliente($contrattoId) {}

    public function uploadAllegato(Request $request)
    {
        $file = new AllegatoServizio;

        if ($request->file('file')) {
            $cartella = config('configurazione.allegati_tutti.cartella');
            $stored = app(SensitiveFileService::class)->store($request->file('file'), $cartella, [
                'area' => 'allegati_servizi',
                'allegato_id' => $request->input('allegato_id', 0),
                'allegato_type' => $request->input('allegato_type'),
            ]);
            $file->path_filename = $stored['path'];
            $file->filename_originale = $stored['original_name'];
            $file->mime_type = $stored['mime_type'];
            $file->file_contenuto_base64 = $stored['base64'];
            $file->dimensione_file = $stored['size'];
            $file->allegato_id = $request->input('allegato_id', 0);
            if (! $file->allegato_id) {
                $file->uid = $request->input('uid');
            }
            $file->allegato_type = str_replace('_', '\\', $request->input('allegato_type'));
            $file->per_cliente = $request->input('per_cliente', 0);
            $file->save();
            if (
                (string) $file->allegato_type === Visura::class
                && (int) $file->allegato_id > 0
                && (int) $file->per_cliente === 1
            ) {
                $visura = Visura::find((int) $file->allegato_id);
                if ($visura) {
                    VisuraAttachmentMailer::notifyCliente($visura, $file);
                }
            }

            return response()->json(['success' => true, 'id' => $file->id, 'filename' => $stored['filename'], 'thumbnail' => $file->urlThumbnail()]);

        }
        abort(404, 'File non presente');

    }

    public function deleteAllegato(Request $request)
    {
        $record = AllegatoServizio::find($request->input('id'));
        abort_if(! $record, 404, 'File non trovato');
        Log::debug(__FUNCTION__, $record->toArray());

        Log::debug('elimino allegato cliente'.$record->path_filename);
        $record->delete();

        return $record->path_filename;
    }
}
