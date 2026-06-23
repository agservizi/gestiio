<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\DatiRitorno;
use App\Models\CartellaFiles;
use App\Models\File;
use App\Models\FileAuditLog;
use App\Models\FileShareLink;
use App\Models\FileVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class CartellaFilesController extends Controller
{
    protected $conFiltro = false;

    public function index(Request $request, $cartellaId = 0)
    {
        abort_unless($this->canViewDocumenti(), 403);

        $nomeClasse = get_class($this);
        $canManageFolders = $this->canManageFolders();
        $canUploadFiles = $this->canUploadFiles();
        $canDeleteFiles = $this->canDeleteFiles();
        $cartella = $cartellaId ? CartellaFiles::find($cartellaId) : null;
        abort_if($cartellaId && ! $cartella, 404, 'Questa cartella non esiste');
        abort_if($cartella && ! $this->folderVisibleToCurrentUser($cartella), 403, 'Cartella non disponibile');

        $categorieDocumentali = File::query()
            ->whereNotNull('categoria_documentale')
            ->where('categoria_documentale', '<>', '')
            ->distinct()
            ->orderBy('categoria_documentale')
            ->pluck('categoria_documentale');

        $auditRecenti = FileAuditLog::with('utente')
            ->latest()
            ->limit(12)
            ->get();

        [$cartelleQb, $filesQb, $cartellePrev] = $this->buildDocumentQueries($request, (int) $cartellaId, $cartella);
        $cartelle = $cartelleQb->withCount('files')->get();
        $files = $filesQb->get();
        $stats = $this->buildStats((int) $cartellaId, $cartelle, $files, $cartella);

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.CartellaFiles.elenchi', [
                    'cartelle' => $cartelle,
                    'files' => $files,
                    'controller' => $nomeClasse,
                    'cartellaId' => (int) $cartellaId,
                    'cartellePrev' => $cartellePrev,
                    'canManageFolders' => $canManageFolders,
                    'canDeleteFiles' => $canDeleteFiles,
                    'canUploadFiles' => $canUploadFiles,
                    'stats' => $stats,
                ])->render()),
            ];
        }

        return view('Backend.CartellaFiles.index_new', [
            'cartellaId' => (int) $cartellaId,
            'files' => $files,
            'cartelle' => $cartelle,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco '.CartellaFiles::NOME_PLURALE,
            'ordinamenti' => null,
            'filtro' => 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova cartella',
            'testoCerca' => 'Cerca nel nome',
            'cartellePrev' => $cartellePrev,
            'canManageFolders' => $canManageFolders,
            'canUploadFiles' => $canUploadFiles,
            'canDeleteFiles' => $canDeleteFiles,
            'stats' => $stats,
            'categorieDocumentali' => $categorieDocumentali,
            'auditRecenti' => $auditRecenti,
            'folderOptions' => $this->folderOptionsForCurrentUser(),
            'shareBaseUrl' => url('/documenti/condivisi'),
        ]);
    }

    protected function applicaFiltriCartelle($request)
    {
        $queryBuilder = CartellaFiles::query();
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \' ,nome)'), 'like', "%$t%");
            }
        }

        return $queryBuilder->orderBy('nome');
    }

    protected function applicaFiltriCartelleNellaCartella(Request $request, int $cartellaId = 0, ?CartellaFiles $cartella = null)
    {
        if (! $cartellaId) {
            $queryBuilder = CartellaFiles::whereIsRoot();
        } else {
            $cartella = $cartella ?: CartellaFiles::find($cartellaId);
            abort_if(! $cartella, 404, 'Questa cartella non esiste');
            abort_if(! $this->folderVisibleToCurrentUser($cartella), 403, 'Cartella non disponibile');
            $queryBuilder = CartellaFiles::whereDescendantOf($cartella)->where('parent_id', $cartella->id);
        }

        $visibleIds = $this->visibleFolderIdsForCurrentUser();
        if (count($visibleIds)) {
            $queryBuilder->whereIn('id', $visibleIds);
        } else {
            $queryBuilder->whereRaw('1=0');
        }

        return $this->applicaFiltriTermineCartelle($queryBuilder, $request)->orderBy('nome');
    }

    protected function applicaFiltriTermineCartelle($queryBuilder, Request $request)
    {
        $term = trim((string) $request->input('cerca'));
        if ($term !== '') {
            foreach (preg_split('/\s+/', $term) as $t) {
                if ($t === '') {
                    continue;
                }
                $queryBuilder->where('nome', 'like', '%'.$t.'%');
            }
        }

        return $queryBuilder;
    }

    protected function applicaFiltriFiles($request, int $cartellaId = 0, ?CartellaFiles $cartella = null)
    {
        $queryBuilder = File::query();
        $scope = $request->input('scope', 'current');
        $isGlobalSearch = $scope === 'all';

        $visibleFolderIds = $this->visibleFolderIdsForCurrentUser();

        if (! $isGlobalSearch) {
            $cartella = $cartellaId ? ($cartella ?: CartellaFiles::find($cartellaId)) : null;
            if ($cartellaId && ! $cartella) {
                abort(404, 'Questa cartella non esiste');
            }
            if ($cartella && ! $this->folderVisibleToCurrentUser($cartella)) {
                abort(403, 'Cartella non disponibile');
            }
            $queryBuilder->where('cartella_id', $cartellaId ?: null);
        } else {
            $queryBuilder->where(function ($q) use ($visibleFolderIds) {
                $q->whereNull('cartella_id');
                if (count($visibleFolderIds)) {
                    $q->orWhereIn('cartella_id', $visibleFolderIds);
                }
            });
        }

        $term = trim((string) $request->input('cerca'));
        if ($term !== '') {
            foreach (preg_split('/\s+/', $term) as $t) {
                if ($t === '') {
                    continue;
                }
                $queryBuilder->where(function ($q) use ($t) {
                    $q->where('filename_originale', 'like', '%'.$t.'%')
                        ->orWhere('ocr_testo', 'like', '%'.$t.'%');
                });
            }
        }

        $tipoFile = trim((string) $request->input('tipo_file'));
        if ($tipoFile !== '') {
            $queryBuilder->where('tipo_file', $tipoFile);
        }

        $categoriaDocumentale = trim((string) $request->input('categoria_documentale'));
        if ($categoriaDocumentale !== '') {
            $queryBuilder->where('categoria_documentale', $categoriaDocumentale);
        }

        $tagDocumentale = trim((string) $request->input('tag_documentale'));
        if ($tagDocumentale !== '') {
            $queryBuilder->whereJsonContains('tags_documentali', mb_strtolower($tagDocumentale));
        }

        if ($request->filled('data_da')) {
            $queryBuilder->whereDate('created_at', '>=', $request->input('data_da'));
        }
        if ($request->filled('data_a')) {
            $queryBuilder->whereDate('created_at', '<=', $request->input('data_a'));
        }

        $scadenza = (string) $request->input('scadenza', '');
        if ($scadenza === 'scaduti') {
            $queryBuilder->whereNotNull('expires_at')->where('expires_at', '<', now());
        } elseif ($scadenza === 'prossimi') {
            $queryBuilder->whereBetween('expires_at', [now(), now()->copy()->addDays(30)]);
        }

        $orderBy = $request->input('order_by', 'recenti');
        if ($orderBy === 'nome') {
            $queryBuilder->orderBy('filename_originale');
        } elseif ($orderBy === 'dimensione') {
            $queryBuilder->orderByDesc('dimensione_file');
        } else {
            $queryBuilder->orderByDesc('created_at');
        }

        return $queryBuilder;
    }

    public function create($cartellaId)
    {
        abort_unless($this->canManageFolders(), 403);
        $record = new CartellaFiles;

        return view('Backend.CartellaFiles.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuova cartella',
            'controller' => get_class($this),
            'cartellaId' => $cartellaId,
            'action' => 'store',
        ]);
    }

    public function store(Request $request, $cartellaId)
    {
        abort_unless($this->canManageFolders(), 403);
        $cartellaId = (int) $cartellaId === 0 ? null : (int) $cartellaId;
        $request->validate($this->rules(null));
        $record = new CartellaFiles;
        $record->parent_id = $cartellaId;
        $this->salvaDati($record, $request);
        CartellaFiles::fixTree();

        $datiRitorno = new DatiRitorno;
        $datiRitorno->redirect(action([CartellaFilesController::class, 'index'], $cartellaId));

        return $datiRitorno->getArray();
    }

    public function show(Request $request, $id)
    {
        abort_unless($this->canViewDocumenti(), 403);
        if (request()->ajax()) {
            $cartella = $id > 0 ? CartellaFiles::find($id) : null;
            abort_if($id > 0 && ! $cartella, 404, 'Questa cartella non esiste');
            abort_if($cartella && ! $this->folderVisibleToCurrentUser($cartella), 403, 'Cartella non disponibile');

            [$cartelleQb, $filesQb, $cartellePrev] = $this->buildDocumentQueries($request, (int) $id, $cartella);
            $datiRitorno = new DatiRitorno;
            $cartelle = $cartelleQb->withCount('files')->get();
            $files = $filesQb->get();
            $html = view('Backend.CartellaFiles.elenchi', [
                'cartelle' => $cartelle,
                'files' => $files,
                'cartellaId' => $id ?: 0,
                'controller' => get_class($this),
                'cartellePrev' => $cartellePrev,
                'canManageFolders' => $this->canManageFolders(),
                'canDeleteFiles' => $this->canDeleteFiles(),
                'canUploadFiles' => $this->canUploadFiles(),
                'stats' => $this->buildStats((int) ($id ?: 0), $cartelle, $files, $cartella),
            ]);

            return $datiRitorno->oggettoReload('aa', $html)->id($id)->getArray();
        }

        $record = CartellaFiles::find($id);
        abort_if(! $record, 404, 'Questa cartella non esiste');
        abort_if(! $this->folderVisibleToCurrentUser($record), 403, 'Cartella non disponibile');

        return view('Backend.CartellaFiles.show', [
            'record' => $record,
            'controller' => CartellaFilesController::class,
            'titoloPagina' => CartellaFiles::NOME_SINGOLARE,
            'breadcrumbs' => [action([CartellaFilesController::class, 'index']) => 'Torna a elenco '.CartellaFiles::NOME_PLURALE],
        ]);
    }

    public function edit($cartellaId, $id)
    {
        abort_unless($this->canManageFolders(), 403);
        $record = CartellaFiles::withCount('files')->withCount('descendants')->find($id);
        abort_if(! $record, 404, 'Questa cartella non esiste');

        if ($record->files_count || $record->descendants_count) {
            $eliminabile = 'Non eliminabile perchè non vuota';
        } else {
            $eliminabile = true;
        }

        return view('Backend.CartellaFiles.edit', [
            'record' => $record,
            'controller' => CartellaFilesController::class,
            'titoloPagina' => 'Modifica '.CartellaFiles::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'action' => 'edit',
            'cartellaId' => $cartellaId,
        ]);
    }

    public function update(Request $request, $cartellaId, $id)
    {
        abort_unless($this->canManageFolders(), 403);

        $record = CartellaFiles::find($id);
        abort_if(! $record, 404, 'Questa '.CartellaFiles::NOME_SINGOLARE.' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);

        $datiRitorno = new DatiRitorno;
        $datiRitorno->redirect(action([CartellaFilesController::class, 'index'], $cartellaId));

        return $datiRitorno->getArray();
    }

    public function destroy($cartellaId, $id)
    {
        abort_unless($this->canManageFolders(), 403);
        $record = CartellaFiles::find($id);
        abort_if(! $record, 404, 'Questa cartella non esiste');

        $record->delete();

        return [
            'success' => true,
            'redirect' => action([CartellaFilesController::class, 'index']),
        ];
    }

    public function upload(Request $request, $cartellaId)
    {
        abort_unless($this->canUploadFiles(), 403);

        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'categoria_documentale' => ['nullable', 'string', 'max:80'],
            'tags_documentali' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
        ]);

        if ($request->file('file')) {
            if ((int) $cartellaId > 0) {
                $cartella = CartellaFiles::find((int) $cartellaId);
                abort_if(! $cartella, 404, 'Questa cartella non esiste');
                abort_if(! $this->folderVisibleToCurrentUser($cartella), 403, 'Cartella non disponibile');
            }

            $file = new File;
            $filePath = $request->file('file');
            $estensione = $filePath->extension();
            $fileName = Str::ulid().'.'.$estensione;
            $storageFolder = trim((string) config('configurazione.file_manager.cartella'), '/');
            $filePath->storeAs($storageFolder, $fileName);

            $storedPath = $storageFolder.'/'.$fileName;
            $file->cartella_id = (int) $cartellaId ?: null;
            $file->path_filename = $storedPath;
            $file->filename_originale = $filePath->getClientOriginalName();
            $file->dimensione_file = $filePath->getSize();
            $file->categoria_documentale = $request->input('categoria_documentale');
            $file->tags_documentali = $this->parseTags($request->input('tags_documentali'));
            $file->ocr_testo = $this->extractSearchableText($storedPath, $file->filename_originale);
            $file->expires_at = $request->filled('expires_at') ? $request->input('expires_at') : null;
            $file->versione = 1;
            $file->save();

            $this->registraAudit('upload', $file, [
                'categoria_documentale' => $file->categoria_documentale,
                'tags_documentali' => $file->tags_documentali,
                'ocr_len' => mb_strlen((string) $file->ocr_testo),
            ]);

            return response()->json([
                'success' => true,
                'id' => $file->id,
                'filename' => $fileName,
                'versione' => $file->versione,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Nessun file inviato'], 422);
    }

    public function uploadVersion(Request $request, $id)
    {
        abort_unless($this->canUploadFiles(), 403);

        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'categoria_documentale' => ['nullable', 'string', 'max:80'],
            'tags_documentali' => ['nullable', 'string', 'max:255'],
        ]);

        $record = File::find($id);
        abort_if(! $record, 404, 'Questo file non esiste');
        abort_if(! $this->canAccessFile($record), 403, 'File non disponibile');

        $this->storeCurrentVersionSnapshot($record);

        $filePath = $request->file('file');
        $estensione = $filePath->extension();
        $fileName = Str::ulid().'.'.$estensione;
        $storageFolder = trim((string) config('configurazione.file_manager.cartella'), '/');
        $filePath->storeAs($storageFolder, $fileName);

        $record->path_filename = $storageFolder.'/'.$fileName;
        $record->filename_originale = $filePath->getClientOriginalName();
        $record->dimensione_file = $filePath->getSize();
        $record->categoria_documentale = $request->input('categoria_documentale', $record->categoria_documentale);
        $record->tags_documentali = $request->filled('tags_documentali')
            ? $this->parseTags($request->input('tags_documentali'))
            : $record->tags_documentali;
        $record->ocr_testo = $this->extractSearchableText($record->path_filename, $record->filename_originale);
        $record->versione = ((int) $record->versione) + 1;
        $record->save();

        $this->registraAudit('upload_version', $record, [
            'versione' => $record->versione,
        ]);

        return response()->json(['success' => true, 'versione' => $record->versione]);
    }

    public function rollbackVersion(Request $request, $id, $versionId)
    {
        abort_unless($this->canUploadFiles(), 403);

        $record = File::find($id);
        abort_if(! $record, 404, 'Questo file non esiste');
        abort_if(! $this->canAccessFile($record), 403, 'File non disponibile');

        $version = FileVersion::where('file_id', $record->id)
            ->where(function ($q) use ($versionId) {
                $q->where('id', $versionId)->orWhere('versione', $versionId);
            })
            ->orderByDesc('id')
            ->first();
        abort_if(! $version, 404, 'Versione non trovata');

        $this->storeCurrentVersionSnapshot($record);

        $record->filename_originale = $version->filename_originale;
        $record->path_filename = $version->path_filename;
        $record->dimensione_file = $version->dimensione_file;
        $record->tipo_file = $version->tipo_file;
        $record->categoria_documentale = $version->categoria_documentale;
        $record->tags_documentali = $version->tags_documentali;
        $record->ocr_testo = $version->ocr_testo;
        $record->versione = ((int) $record->versione) + 1;
        $record->save();

        $this->registraAudit('rollback_version', $record, [
            'from_version_id' => $version->id,
            'versione_corrente' => $record->versione,
        ]);

        return response()->json(['success' => true, 'versione' => $record->versione]);
    }

    public function moveFile(Request $request)
    {
        abort_unless($this->canManageFolders(), 403);

        $request->validate([
            'id' => ['required', 'integer', 'exists:files,id'],
            'target_cartella_id' => ['nullable', 'integer', 'exists:files_cartelle,id'],
        ]);

        $record = File::find($request->input('id'));
        abort_if(! $record, 404, 'File non trovato');

        $targetId = $request->filled('target_cartella_id') ? (int) $request->input('target_cartella_id') : null;
        if ($targetId) {
            $targetFolder = CartellaFiles::find($targetId);
            abort_if(! $targetFolder, 404, 'Cartella destinazione non trovata');
        }

        $oldCartella = $record->cartella_id;
        $record->cartella_id = $targetId;
        $record->save();

        $this->registraAudit('move_file', $record, [
            'from_cartella_id' => $oldCartella,
            'to_cartella_id' => $targetId,
        ]);

        return response()->json(['success' => true]);
    }

    public function moveFolder(Request $request)
    {
        abort_unless($this->canManageFolders(), 403);

        $request->validate([
            'id' => ['required', 'integer', 'exists:files_cartelle,id'],
            'target_parent_id' => ['nullable', 'integer', 'exists:files_cartelle,id'],
        ]);

        $folder = CartellaFiles::find((int) $request->input('id'));
        abort_if(! $folder, 404, 'Cartella non trovata');

        $targetId = $request->filled('target_parent_id') ? (int) $request->input('target_parent_id') : null;
        if ($targetId === (int) $folder->id) {
            return response()->json(['success' => false, 'message' => 'La cartella non può essere spostata su se stessa'], 422);
        }

        if ($targetId) {
            $target = CartellaFiles::find($targetId);
            abort_if(! $target, 404, 'Cartella destinazione non trovata');
            abort_if($target->isDescendantOf($folder), 422, 'Destinazione non valida: cartella figlia');
        }

        $oldParent = $folder->parent_id;
        $folder->parent_id = $targetId;
        $folder->save();
        CartellaFiles::fixTree();

        $this->registraAudit('move_folder', null, [
            'cartella_id' => $folder->id,
            'filename_originale' => '[cartella] '.$folder->nome,
            'from_parent_id' => $oldParent,
            'to_parent_id' => $targetId,
        ]);

        return response()->json(['success' => true]);
    }

    public function renameFile(Request $request)
    {
        abort_unless($this->canManageFolders(), 403);

        $request->validate([
            'id' => ['required', 'integer', 'exists:files,id'],
            'filename_originale' => ['required', 'string', 'max:255'],
        ]);

        $record = File::find((int) $request->input('id'));
        abort_if(! $record, 404, 'File non trovato');

        $oldName = $record->filename_originale;
        $record->filename_originale = trim((string) $request->input('filename_originale'));
        $record->save();

        $this->registraAudit('rename_file', $record, [
            'old_filename' => $oldName,
            'new_filename' => $record->filename_originale,
        ]);

        return response()->json(['success' => true]);
    }

    public function setExpiry(Request $request)
    {
        abort_unless($this->canManageFolders(), 403);

        $request->validate([
            'id' => ['required', 'integer', 'exists:files,id'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $record = File::find((int) $request->input('id'));
        abort_if(! $record, 404, 'File non trovato');

        $record->expires_at = $request->filled('expires_at') ? $request->input('expires_at') : null;
        $record->save();

        $this->registraAudit('set_expiry', $record, [
            'expires_at' => $record->expires_at?->toDateTimeString(),
        ]);

        return response()->json(['success' => true]);
    }

    public function setFolderVisibility(Request $request, $id)
    {
        abort_unless($this->canManageFolders(), 403);

        $request->validate([
            'ruoli' => ['nullable', 'array'],
            'ruoli.*' => ['string', 'in:admin,agente,operatore,supervisore'],
        ]);

        $folder = CartellaFiles::find($id);
        abort_if(! $folder, 404, 'Cartella non trovata');

        $ruoli = collect($request->input('ruoli', []))
            ->map(fn ($r) => trim((string) $r))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $folder->visibilita_ruoli = count($ruoli) ? $ruoli : null;
        $folder->save();

        $this->registraAudit('set_folder_visibility', null, [
            'cartella_id' => $folder->id,
            'filename_originale' => '[cartella] '.$folder->nome,
            'visibilita_ruoli' => $folder->visibilita_ruoli,
        ]);

        return response()->json(['success' => true]);
    }

    public function preview($id)
    {
        abort_unless($this->canViewDocumenti(), 403);

        $record = File::find($id);
        abort_if(! $record, 404, 'File non trovato');
        abort_if(! $this->canAccessFile($record), 403, 'File non disponibile');
        abort_if(! Storage::exists($record->path_filename), 404, 'File non disponibile sul disco');

        $path = Storage::path($record->path_filename);
        $mimeType = Storage::mimeType($record->path_filename) ?: 'application/octet-stream';

        $previewable = Str::startsWith($mimeType, 'image/')
            || Str::contains($mimeType, ['pdf', 'text/plain', 'text/csv', 'application/json']);

        if (! $previewable) {
            return response()->download($path, $record->filename_originale);
        }

        $this->registraAudit('preview', $record);

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addslashes($record->filename_originale).'"',
        ]);
    }

    public function cancellaFile(Request $request)
    {
        abort_unless($this->canDeleteFiles(), 403);

        $id = $request->input('id');
        $record = File::find($id);
        if (! $record) {
            abort(404, 'Questo file non esiste.');
        }

        $auditPayload = [
            'filename_originale' => $record->filename_originale,
            'path_filename' => $record->path_filename,
            'cartella_id' => $record->cartella_id,
            'categoria_documentale' => $record->categoria_documentale,
            'tags_documentali' => $record->tags_documentali,
        ];

        $record->delete();
        $this->registraAudit('delete', null, $auditPayload);

        $datiRitorno = new DatiRitorno;

        return $datiRitorno->rimuoviOggetto('#file_'.$id)->getArray();
    }

    public function download($id)
    {
        abort_unless($this->canViewDocumenti(), 403);

        $record = File::find($id);
        abort_if(! $record, 404, 'File non trovato');
        abort_if(! $this->canAccessFile($record), 403, 'File non disponibile');

        $this->registraAudit('download', $record);

        return response()->download(Storage::path($record->path_filename), $record->filename_originale);
    }

    public function downloadMultiplo(Request $request)
    {
        abort_unless($this->canViewDocumenti(), 403);

        $request->validate([
            'file_ids' => ['required', 'array', 'min:1', 'max:200'],
            'file_ids.*' => ['integer', 'exists:files,id'],
        ]);

        $fileIds = collect($request->input('file_ids', []))->map(fn ($id) => (int) $id)->unique()->values();
        $files = File::whereIn('id', $fileIds)->orderBy('filename_originale')->get()->filter(fn ($f) => $this->canAccessFile($f));
        abort_if($files->isEmpty(), 404, 'Nessun file valido selezionato');

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $downloadName = 'documenti-'.now()->format('Ymd-His').'.zip';
        $zipPath = $tmpDir.'/'.Str::ulid().'.zip';

        $zip = new ZipArchive;
        abort_if($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true, 500, 'Impossibile creare ZIP');

        $usedNames = [];
        $aggiunti = 0;
        foreach ($files as $file) {
            if (! Storage::exists($file->path_filename)) {
                continue;
            }

            $nomeBase = $file->filename_originale;
            $nomeFinale = $nomeBase;
            $i = 1;
            while (isset($usedNames[$nomeFinale])) {
                $filename = pathinfo($nomeBase, PATHINFO_FILENAME);
                $extension = pathinfo($nomeBase, PATHINFO_EXTENSION);
                $nomeFinale = $extension ? ($filename.'-'.$i.'.'.$extension) : ($filename.'-'.$i);
                $i++;
            }

            $usedNames[$nomeFinale] = true;
            $zip->addFile(Storage::path($file->path_filename), $nomeFinale);
            $aggiunti++;

            $this->registraAudit('download_multiplo', $file, [
                'batch_download_name' => $downloadName,
            ]);
        }

        $zip->close();
        abort_if($aggiunti === 0, 404, 'I file selezionati non sono disponibili sul disco');

        return response()
            ->download($zipPath, $downloadName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function createShareLink(Request $request)
    {
        abort_unless($this->canManageFolders(), 403);

        $request->validate([
            'file_id' => ['nullable', 'integer', 'exists:files,id'],
            'cartella_id' => ['nullable', 'integer', 'exists:files_cartelle,id'],
            'expires_at' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'max:120'],
            'max_downloads' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $fileId = $request->filled('file_id') ? (int) $request->input('file_id') : null;
        $cartellaId = $request->filled('cartella_id') ? (int) $request->input('cartella_id') : null;
        abort_if(! $fileId && ! $cartellaId, 422, 'Devi indicare file_id o cartella_id');

        $share = new FileShareLink;
        $share->token = Str::random(64);
        $share->file_id = $fileId;
        $share->cartella_id = $cartellaId;
        $share->created_by = Auth::id();
        $share->expires_at = $request->filled('expires_at') ? $request->input('expires_at') : now()->addDays(7);
        $share->max_downloads = $request->filled('max_downloads') ? (int) $request->input('max_downloads') : null;
        $share->password_hash = $request->filled('password') ? Hash::make((string) $request->input('password')) : null;
        $share->is_active = true;
        $share->save();

        $this->registraAudit('create_share_link', null, [
            'file_id' => $fileId,
            'cartella_id' => $cartellaId,
            'filename_originale' => '[share] '.($fileId ? ('file#'.$fileId) : ('cartella#'.$cartellaId)),
            'share_link_id' => $share->id,
        ]);

        return response()->json([
            'success' => true,
            'token' => $share->token,
            'url' => url('/documenti/condivisi/'.$share->token),
        ]);
    }

    public function sharedDownload(Request $request, $token)
    {
        $share = FileShareLink::where('token', $token)->first();
        abort_if(! $share, 404, 'Link non valido');
        abort_if(! $share->is_active, 410, 'Link non più attivo');
        abort_if($share->isExpired(), 410, 'Link scaduto');
        abort_if($share->isLimitReached(), 429, 'Limite download raggiunto');

        if ($share->password_hash) {
            $password = (string) $request->input('password', '');
            if ($password === '' || ! Hash::check($password, $share->password_hash)) {
                return response('<html><body><form method="GET"><input type="password" name="password" placeholder="Password"/><button type="submit">Apri</button></form></body></html>', 401);
            }
        }

        $share->download_count = ((int) $share->download_count) + 1;
        $share->last_access_at = now();
        $share->save();

        if ($share->file_id) {
            $record = File::find($share->file_id);
            abort_if(! $record, 404, 'File non più disponibile');
            $path = Storage::path($record->path_filename);

            return response()->download($path, $record->filename_originale);
        }

        $folder = CartellaFiles::find($share->cartella_id);
        abort_if(! $folder, 404, 'Cartella non più disponibile');

        [$zipPath, $downloadName] = $this->buildFolderZip($folder, 'cartella-condivisa-'.$folder->id);

        return response()->download($zipPath, $downloadName, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }

    public function exportAuditCsv(Request $request): StreamedResponse
    {
        abort_unless($this->canManageFolders(), 403);

        $query = FileAuditLog::query()->with(['utente', 'cartella']);

        if ($request->filled('azione')) {
            $query->where('azione', (string) $request->input('azione'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }
        if ($request->filled('cartella_id')) {
            $query->where('cartella_id', (int) $request->input('cartella_id'));
        }
        if ($request->filled('data_da')) {
            $query->whereDate('created_at', '>=', $request->input('data_da'));
        }
        if ($request->filled('data_a')) {
            $query->whereDate('created_at', '<=', $request->input('data_a'));
        }

        $rows = $query->orderByDesc('created_at')->limit(10000)->get();

        $filename = 'audit-documenti-'.now()->format('Ymd-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['quando', 'utente', 'azione', 'file', 'cartella_id', 'meta']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->created_at?->format('Y-m-d H:i:s'),
                    $row->utente?->name ?? ('user#'.($row->user_id ?? '-')),
                    $row->azione,
                    $row->filename_originale,
                    $row->cartella_id,
                    json_encode($row->meta, JSON_UNESCAPED_UNICODE),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    protected function salvaDati($model, $request)
    {
        $campi = [
            'nome' => 'app\\getInputUcwords',
        ];

        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();

        return $model;
    }

    protected function backToIndex()
    {
        return redirect()->action([get_class($this), 'index']);
    }

    protected function queryBuilderIndexSemplice()
    {
        return CartellaFiles::get();
    }

    protected function rules($id = null)
    {
        return [
            'nome' => ['required', 'max:255'],
        ];
    }

    protected function buildDocumentQueries(Request $request, int $cartellaId = 0, ?CartellaFiles $cartella = null): array
    {
        $cartelleQb = $this->applicaFiltriCartelleNellaCartella($request, $cartellaId, $cartella);
        $filesQb = $this->applicaFiltriFiles($request, $cartellaId, $cartella);
        $cartellePrev = $cartellaId ? CartellaFiles::ancestorsAndSelf($cartellaId)->filter(fn ($c) => $this->folderVisibleToCurrentUser($c)) : collect();

        return [$cartelleQb, $filesQb, $cartellePrev];
    }

    protected function buildStats(int $cartellaId, $cartelle, $files, ?CartellaFiles $cartella = null): array
    {
        return [
            'cartella' => $cartellaId > 0 ? ($cartella?->nome ?? 'Root') : 'Root',
            'conteggio_cartelle' => $cartelle->count(),
            'conteggio_file' => $files->count(),
            'dimensione_totale' => \App\humanFileSize((int) $files->sum('dimensione_file')),
        ];
    }

    protected function canViewDocumenti(): bool
    {
        return $this->currentUserHasAnyPermission(['admin', 'agente', 'operatore', 'supervisore']);
    }

    protected function canManageFolders(): bool
    {
        return $this->currentUserHasAnyPermission(['admin']);
    }

    protected function canUploadFiles(): bool
    {
        return $this->currentUserHasAnyPermission(['admin']);
    }

    protected function canDeleteFiles(): bool
    {
        return $this->currentUserHasAnyPermission(['admin']);
    }

    protected function parseTags(?string $rawTags): array
    {
        if (! $rawTags) {
            return [];
        }

        return collect(explode(',', $rawTags))
            ->map(fn ($tag) => mb_strtolower(trim((string) $tag)))
            ->filter(fn ($tag) => $tag !== '')
            ->unique()
            ->take(15)
            ->values()
            ->all();
    }

    protected function extractSearchableText(string $storedPath, string $originalFilename): ?string
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        if (! in_array($extension, ['txt', 'csv', 'json', 'xml', 'html', 'htm', 'md'], true)) {
            return null;
        }

        if (! Storage::exists($storedPath)) {
            return null;
        }

        $content = (string) Storage::get($storedPath);
        $content = strip_tags($content);
        $content = preg_replace('/\s+/', ' ', $content ?? '');

        return Str::limit(trim((string) $content), 20000, '');
    }

    protected function storeCurrentVersionSnapshot(File $file): void
    {
        FileVersion::create([
            'file_id' => $file->id,
            'versione' => (int) ($file->versione ?: 1),
            'filename_originale' => $file->filename_originale,
            'path_filename' => $file->path_filename,
            'dimensione_file' => $file->dimensione_file,
            'tipo_file' => $file->tipo_file,
            'categoria_documentale' => $file->categoria_documentale,
            'tags_documentali' => $file->tags_documentali,
            'ocr_testo' => $file->ocr_testo,
            'created_by' => Auth::id(),
        ]);
    }

    protected function registraAudit(string $azione, ?File $file = null, array $meta = []): void
    {
        $payload = [
            'user_id' => Auth::id(),
            'file_id' => $file?->id,
            'cartella_id' => $file?->cartella_id ?? ($meta['cartella_id'] ?? null),
            'azione' => $azione,
            'filename_originale' => $file?->filename_originale ?? ($meta['filename_originale'] ?? 'n/d'),
            'path_filename' => $file?->path_filename ?? ($meta['path_filename'] ?? null),
            'meta' => $meta ?: null,
        ];

        FileAuditLog::create($payload);
        Log::info('documenti_audit', $payload);
    }

    protected function canAccessFile(File $file): bool
    {
        if (! $this->canViewDocumenti()) {
            return false;
        }

        if (! $file->cartella_id) {
            return true;
        }

        $folder = CartellaFiles::find($file->cartella_id);
        if (! $folder) {
            return false;
        }

        return $this->folderVisibleToCurrentUser($folder);
    }

    protected function currentUserRoles(): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $roles = [];
        foreach (['admin', 'agente', 'operatore', 'supervisore'] as $role) {
            if (method_exists($user, 'hasRole') && (bool) call_user_func([$user, 'hasRole'], $role)) {
                $roles[] = $role;

                continue;
            }
            if (method_exists($user, 'hasPermissionTo')) {
                try {
                    if ((bool) call_user_func([$user, 'hasPermissionTo'], $role)) {
                        $roles[] = $role;
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        return array_values(array_unique($roles));
    }

    protected function folderVisibleToCurrentUser(?CartellaFiles $folder): bool
    {
        if (! $folder) {
            return true;
        }

        if ($this->canManageFolders()) {
            return true;
        }

        $allowedRoles = $folder->visibilita_ruoli;
        if (! is_array($allowedRoles) || ! count($allowedRoles)) {
            return true;
        }

        return count(array_intersect($this->currentUserRoles(), $allowedRoles)) > 0;
    }

    protected function visibleFolderIdsForCurrentUser(): array
    {
        $folders = CartellaFiles::query()->select(['id', 'visibilita_ruoli'])->get();

        return $folders
            ->filter(fn ($f) => $this->folderVisibleToCurrentUser($f))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function folderOptionsForCurrentUser(): array
    {
        $options = [0 => 'Root'];
        $folders = CartellaFiles::query()->orderBy('nome')->get();
        foreach ($folders as $folder) {
            if (! $this->folderVisibleToCurrentUser($folder)) {
                continue;
            }
            $options[(int) $folder->id] = '#'.$folder->id.' - '.$folder->nome;
        }

        return $options;
    }

    protected function buildFolderZip(CartellaFiles $folder, string $prefix = 'documenti-folder'): array
    {
        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $downloadName = $prefix.'-'.now()->format('Ymd-His').'.zip';
        $zipPath = $tmpDir.'/'.Str::ulid().'.zip';

        $folderIds = CartellaFiles::descendantsAndSelf($folder->id)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $files = File::whereIn('cartella_id', $folderIds)->orderBy('filename_originale')->get();

        $zip = new ZipArchive;
        abort_if($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true, 500, 'Impossibile creare ZIP');

        $usedNames = [];
        foreach ($files as $file) {
            if (! Storage::exists($file->path_filename)) {
                continue;
            }

            $relativeFolder = '';
            if ($file->cartella_id) {
                $f = CartellaFiles::find($file->cartella_id);
                $relativeFolder = $f ? ($f->nome.'/') : '';
            }

            $entry = $relativeFolder.$file->filename_originale;
            $i = 1;
            $safeEntry = $entry;
            while (isset($usedNames[$safeEntry])) {
                $filename = pathinfo($entry, PATHINFO_FILENAME);
                $extension = pathinfo($entry, PATHINFO_EXTENSION);
                $safeEntry = $extension ? ($filename.'-'.$i.'.'.$extension) : ($filename.'-'.$i);
                $i++;
            }

            $usedNames[$safeEntry] = true;
            $zip->addFile(Storage::path($file->path_filename), $safeEntry);
        }

        $zip->close();

        return [$zipPath, $downloadName];
    }

    protected function currentUserHasAnyPermission(array $permissions): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyPermission')) {
            try {
                if ((bool) call_user_func([$user, 'hasAnyPermission'], $permissions)) {
                    return true;
                }
            } catch (\Throwable $e) {
            }
        }

        if (method_exists($user, 'hasAnyRole')) {
            try {
                if ((bool) call_user_func([$user, 'hasAnyRole'], $permissions)) {
                    return true;
                }
            } catch (\Throwable $e) {
            }
        }

        if (method_exists($user, 'hasPermissionTo')) {
            foreach ($permissions as $permission) {
                try {
                    if ((bool) call_user_func([$user, 'hasPermissionTo'], $permission)) {
                        return true;
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        if (method_exists($user, 'hasRole')) {
            foreach ($permissions as $permission) {
                if ((bool) call_user_func([$user, 'hasRole'], $permission)) {
                    return true;
                }
            }
        }

        if (method_exists($user, 'can')) {
            foreach ($permissions as $permission) {
                if ((bool) call_user_func([$user, 'can'], $permission)) {
                    return true;
                }
            }
        }

        return false;
    }
}
