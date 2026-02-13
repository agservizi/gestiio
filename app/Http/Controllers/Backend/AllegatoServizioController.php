<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AllegatoServizio;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AllegatoServizioController extends Controller
{
    public function downloadAllegato($contrattoId, $allegatoId)
    {

        $record = AllegatoServizio::find($allegatoId);
        abort_if(!$record, 404, 'Questo allegato non esiste');
        abort_if($record->allegato_id != $contrattoId, 404, 'Questo allegato non esiste');

        return response()->download(\Storage::path($record->path_filename), $record->filename_originale);

    }

    public function downloadAllegatoCliente($contrattoId)
    {


    }


    public function uploadAllegato(Request $request)
    {
        $file = new AllegatoServizio();

        if ($request->file('file')) {
            $filePath = $request->file('file');
            $estensione = $filePath->extension();
            $fileName = Str::ulid() . '.' . $estensione;
            $cartella = config('configurazione.allegati_tutti.cartella');
            $request->file('file')->storeAs($cartella, $fileName);
            $file->path_filename = $cartella . '/' . $fileName;
            $file->filename_originale = $filePath->getClientOriginalName();
            $file->dimensione_file = $filePath->getSize();
            $file->allegato_id = $request->input('allegato_id', 0);
            if (!$file->allegato_id) {
                $file->uid = $request->input('uid');
            }
            $file->allegato_type = str_replace('_', '\\', $request->input('allegato_type'));
            $file->per_cliente = $request->input('per_cliente', 0);
            $file->save();

            return response()->json(['success' => true, 'id' => $file->id, 'filename' => $fileName, 'thumbnail' => $file->urlThumbnail()]);

        }
        abort(404, 'File non presente');

    }

    public function deleteAllegato(Request $request)
    {
        $record = AllegatoServizio::find($request->input('id'));
        abort_if(!$record, 404, 'File non trovato');
        \Log::debug(__FUNCTION__, $record->toArray());

        \Log::debug('elimino allegato cliente' . $record->path_filename);
        $record->delete();
        return $record->path_filename;
    }

}
