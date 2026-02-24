<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\DatiRitorno;
use App\Models\File;
use App\Models\FileAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CartellaFiles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class CartellaFilesController extends Controller
{
    protected $conFiltro = false;


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $cartellaId = 0)
    {
        abort_unless($this->canViewDocumenti(), 403);

        $nomeClasse = get_class($this);
        $canManageFolders = $this->canManageFolders();
        $canUploadFiles = $this->canUploadFiles();
        $canDeleteFiles = $this->canDeleteFiles();
        $cartella = $cartellaId ? CartellaFiles::find($cartellaId) : null;
        abort_if($cartellaId && !$cartella, 404, 'Questa cartella non esiste');
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

        [$cartelleQb, $filesQb, $cartellePrev] = $this->buildDocumentQueries($request, $cartellaId, $cartella);
        $cartelle = $cartelleQb->withCount('files')->get();
        $files = $filesQb->get();
        $stats = $this->buildStats($cartellaId, $cartelle, $files, $cartella);

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.CartellaFiles.elenchi', [
                    'cartelle' => $cartelle,
                    'files' => $files,
                    'controller' => $nomeClasse,
                    'cartellaId' => $cartellaId,
                    'cartellePrev' => $cartellePrev,
                    'canManageFolders' => $canManageFolders,
                    'canDeleteFiles' => $canDeleteFiles,
                    'stats' => $stats,
                ])->render())
            ];

        }

        return view('Backend.CartellaFiles.index_new', [
            'cartellaId' => $cartellaId,
            'files' => $files,
            'cartelle' => $cartelle,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\CartellaFiles::NOME_PLURALE,
            'ordinamenti' => null,// $ordinamenti,
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

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltriCartelle($request)
    {
        $queryBuilder = \App\Models\CartellaFiles::query();
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome)'), 'like', "%$t%");
            }
        }

        return $queryBuilder->orderBy('nome');
    }

    protected function applicaFiltriCartelleNellaCartella(Request $request, int $cartellaId = 0, ?CartellaFiles $cartella = null)
    {
        if (!$cartellaId) {
            $queryBuilder = CartellaFiles::whereIsRoot();
        } else {
            $cartella = $cartella ?: CartellaFiles::find($cartellaId);
            abort_if(!$cartella, 404, 'Questa cartella non esiste');
            $queryBuilder = CartellaFiles::whereDescendantOf($cartella)->where('parent_id', $cartella->id);
        }

        return $this->applicaFiltriTermineCartelle($queryBuilder, $request)->orderBy('nome');
    }

    protected function applicaFiltriTermineCartelle($queryBuilder, Request $request)
    {
        $term = trim((string)$request->input('cerca'));
        if ($term !== '') {
            foreach (preg_split('/\s+/', $term) as $t) {
                if ($t === '') {
                    continue;
                }
                $queryBuilder->where('nome', 'like', '%' . $t . '%');
            }
        }

        return $queryBuilder;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltriFiles($request, int $cartellaId = 0, ?CartellaFiles $cartella = null)
    {
        $queryBuilder = \App\Models\File::query();
        $scope = $request->input('scope', 'current');
        $isGlobalSearch = $scope === 'all';

        if (!$isGlobalSearch) {
            $cartella = $cartellaId ? ($cartella ?: CartellaFiles::find($cartellaId)) : null;
            if ($cartellaId && !$cartella) {
                abort(404, 'Questa cartella non esiste');
            }
            $queryBuilder->where('cartella_id', $cartellaId ?: null);
        }

        $term = trim((string)$request->input('cerca'));
        if ($term !== '') {
            foreach (preg_split('/\s+/', $term) as $t) {
                if ($t === '') {
                    continue;
                }
                $queryBuilder->where('filename_originale', 'like', '%' . $t . '%');
            }
        }

        $tipoFile = trim((string)$request->input('tipo_file'));
        if ($tipoFile !== '') {
            $queryBuilder->where('tipo_file', $tipoFile);
        }

        $categoriaDocumentale = trim((string)$request->input('categoria_documentale'));
        if ($categoriaDocumentale !== '') {
            $queryBuilder->where('categoria_documentale', $categoriaDocumentale);
        }

        $tagDocumentale = trim((string)$request->input('tag_documentale'));
        if ($tagDocumentale !== '') {
            $queryBuilder->whereJsonContains('tags_documentali', mb_strtolower($tagDocumentale));
        }

        if ($request->filled('data_da')) {
            $queryBuilder->whereDate('created_at', '>=', $request->input('data_da'));
        }
        if ($request->filled('data_a')) {
            $queryBuilder->whereDate('created_at', '<=', $request->input('data_a'));
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


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($cartellaId)
    {
        abort_unless($this->canManageFolders(), 403);
        $record = new CartellaFiles();
        return view('Backend.CartellaFiles.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuova cartella',
            'controller' => get_class($this),
            'cartellaId' => $cartellaId,
            'action' => 'store',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $cartellaId)
    {
        abort_unless($this->canManageFolders(), 403);
        $cartellaId = $cartellaId == 0 ? null : $cartellaId;
        $request->validate($this->rules(null));
        $record = new CartellaFiles();
        $record->parent_id = $cartellaId;
        $this->salvaDati($record, $request);
        CartellaFiles::fixTree();
        $datiRitorno = new DatiRitorno();

        $datiRitorno->redirect(action([CartellaFilesController::class, 'index'], $cartellaId));

        return $datiRitorno->getArray();

        $html = view('Backend.CartellaFiles.elencoCartelle', [
            'records' => $this->applicaFiltriCartelle($request)->paginate(),
            'controller' => get_class($this),
            'redirect' => true,
            'cartellaId' => null

        ]);
        return $datiRitorno->chiudiDialog(true)->oggettoReload('cartelle', $html)->getArray();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        abort_unless($this->canViewDocumenti(), 403);
        if (\request()->ajax()) {

            $cartella = $id > 0 ? CartellaFiles::find($id) : null;
            abort_if($id > 0 && !$cartella, 404, 'Questa cartella non esiste');
            [$cartelleQb, $filesQb, $cartellePrev] = $this->buildDocumentQueries($request, $id, $cartella);
            $datiRitorno = new DatiRitorno();
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
                'stats' => $this->buildStats($id ?: 0, $cartelle, $files, $cartella),
            ]);
            return $datiRitorno->oggettoReload('aa', $html)->id($id)->getArray();
        }


        $record = CartellaFiles::find($id);
        abort_if(!$record, 404, 'Questa cartellafiles non esiste');
        return view('Backend.CartellaFiles.show', [
            'record' => $record,
            'controller' => CartellaFilesController::class,
            'titoloPagina' => CartellaFiles::NOME_SINGOLARE,
            'breadcrumbs' => [action([CartellaFilesController::class, 'index']) => 'Torna a elenco ' . CartellaFiles::NOME_PLURALE]

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($cartellaId, $id)
    {
        abort_unless($this->canManageFolders(), 403);
        $record = CartellaFiles::withCount('files')->withCount('descendants')->find($id);
        abort_if(!$record, 404, 'Questa cartellafiles non esiste');
        if ($record->files_count || $record->descendants_count) {
            $eliminabile = 'Non eliminabile perchè non vuota';
        } else {
            $eliminabile = true;
        }
        return view('Backend.CartellaFiles.edit', [
            'record' => $record,
            'controller' => CartellaFilesController::class,
            'titoloPagina' => 'Modifica ' . CartellaFiles::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'action' => 'edit',
            'cartellaId' => $cartellaId


        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $cartellaId, $id)
    {
        abort_unless($this->canManageFolders(), 403);

        $record = CartellaFiles::find($id);
        abort_if(!$record, 404, 'Questa ' . CartellaFiles::NOME_SINGOLARE . ' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);
        $datiRitorno = new DatiRitorno();

        $datiRitorno->redirect(action([CartellaFilesController::class, 'index'], $cartellaId));

        return $datiRitorno->getArray();

        $html = view('Backend.CartellaFiles.elencoCartelle', [
            'records' => $this->applicaFiltriCartelle($request)->paginate(),
            'controller' => get_class($this),
            'reload' => true,
            'cartellaId' => null

        ]);
        return $datiRitorno->chiudiDialog(true)->oggettoReload('cartelle', $html)->getArray();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($cartellaId, $id)
    {
        abort_unless($this->canManageFolders(), 403);
        $record = CartellaFiles::find($id);
        abort_if(!$record, 404, 'Questa cartellafiles non esiste');

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
        ]);

        if ($request->file('file')) {
            if ($cartellaId > 0) {
                abort_if(!CartellaFiles::find($cartellaId), 404, 'Questa cartella non esiste');
            }
            $file = new File();
            $filePath = $request->file('file');
            $estensione = $filePath->extension();
            $fileName = Str::ulid() . '.' . $estensione;
            $cartella = config('configurazione.file_manager.cartella');
            $request->file('file')->storeAs($cartella, $fileName);
            $file->cartella_id = $cartellaId;
            $file->path_filename = $cartella . '/' . $fileName;
            $file->filename_originale = $filePath->getClientOriginalName();
            $file->dimensione_file = $filePath->getSize();
            $file->categoria_documentale = $request->input('categoria_documentale');
            $file->tags_documentali = $this->parseTags($request->input('tags_documentali'));
            $file->save();
            $this->registraAudit(
                'upload',
                $file,
                [
                    'categoria_documentale' => $file->categoria_documentale,
                    'tags_documentali' => $file->tags_documentali,
                ]
            );

            return response()->json(['success' => true, 'id' => $file->id, 'filename' => $fileName]);

        }

    }

    public function cancellaFile(Request $request)
    {
        abort_unless($this->canDeleteFiles(), 403);
        $id = $request->input('id');
        $record = File::find($id);
        if (!$record) {
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
        $datiRitorno = new DatiRitorno();
        return $datiRitorno->rimuoviOggetto('#file_' . $id)->getArray();


    }

    public function download($id)
    {
        abort_unless($this->canViewDocumenti(), 403);
        $record = File::find($id);
        abort_if(!$record, 404);
        return response()->download(Storage::path($record->path_filename), $record->filename_originale);
    }

    public function downloadMultiplo(Request $request)
    {
        abort_unless($this->canViewDocumenti(), 403);

        $request->validate([
            'file_ids' => ['required', 'array', 'min:1', 'max:200'],
            'file_ids.*' => ['integer', 'exists:files,id'],
        ]);

        $fileIds = collect($request->input('file_ids', []))->map(fn($id) => (int)$id)->unique()->values();
        $files = File::whereIn('id', $fileIds)->orderBy('filename_originale')->get();
        abort_if($files->isEmpty(), 404, 'Nessun file valido selezionato');

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
        $downloadName = 'documenti-' . now()->format('Ymd-His') . '.zip';
        $zipPath = $tmpDir . '/' . Str::ulid() . '.zip';

        $zip = new ZipArchive();
        abort_if($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true, 500, 'Impossibile creare ZIP');

        $usedNames = [];
        $aggiunti = 0;
        foreach ($files as $file) {
            if (!Storage::exists($file->path_filename)) {
                continue;
            }
            $nomeBase = $file->filename_originale;
            $nomeFinale = $nomeBase;
            $i = 1;
            while (isset($usedNames[$nomeFinale])) {
                $filename = pathinfo($nomeBase, PATHINFO_FILENAME);
                $extension = pathinfo($nomeBase, PATHINFO_EXTENSION);
                $nomeFinale = $extension ? ($filename . '-' . $i . '.' . $extension) : ($filename . '-' . $i);
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


    /**
     * @param CartellaFiles $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
        }

        //Ciclo su campi
        $campi = [
            'nome' => 'app\getInputUcwords',
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

    /** Query per index
     * @return array
     */
    protected function queryBuilderIndexSemplice()
    {
        return \App\Models\CartellaFiles::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'nome' => ['required', 'max:255'],
        ];

        return $rules;
    }

    protected function buildDocumentQueries(Request $request, int $cartellaId = 0, ?CartellaFiles $cartella = null): array
    {
        $cartelleQb = $this->applicaFiltriCartelleNellaCartella($request, $cartellaId, $cartella);
        $filesQb = $this->applicaFiltriFiles($request, $cartellaId, $cartella);
        $cartellePrev = $cartellaId ? CartellaFiles::ancestorsAndSelf($cartellaId) : collect();

        return [$cartelleQb, $filesQb, $cartellePrev];
    }

    protected function buildStats(int $cartellaId, $cartelle, $files, ?CartellaFiles $cartella = null): array
    {
        return [
            'cartella' => $cartellaId > 0 ? ($cartella?->nome ?? 'Root') : 'Root',
            'conteggio_cartelle' => $cartelle->count(),
            'conteggio_file' => $files->count(),
            'dimensione_totale' => \App\humanFileSize((int)$files->sum('dimensione_file')),
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
        if (!$rawTags) {
            return [];
        }

        return collect(explode(',', $rawTags))
            ->map(fn($tag) => mb_strtolower(trim((string)$tag)))
            ->filter(fn($tag) => $tag !== '')
            ->unique()
            ->take(15)
            ->values()
            ->all();
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

    protected function currentUserHasAnyPermission(array $permissions): bool
    {
        $user = Auth::user();
        if (!$user) {
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
