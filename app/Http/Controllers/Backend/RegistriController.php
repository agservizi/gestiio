<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AllegatoCafPatronato;
use App\Models\AllegatoContratto;
use App\Models\AllegatoContrattoEnergia;
use App\Models\AllegatoServizio;
use App\Models\FileAuditLog;
use App\Models\Licenza;
use App\Models\RegistroAttivita;
use App\Models\RegistroEmail;
use App\Models\RegistroLogin;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\Helpers\Format;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatus;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistriController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Factory|View|BinaryFileResponse|StreamedResponse
     */
    public function index(Request $request, $cosa)
    {

        switch ($cosa) {
            case 'login':
                $this->authorizeRegistro($request, 'registro.login.view');

                return $this->registroLogin($request);

            case 'modifiche':
                $this->authorizeRegistro($request, 'registro.modifiche.view');

                return $this->registroModifiche($request);

            case 'backup-db':
                $this->authorizeRegistro($request, 'registro.backup.view');

                return $this->backupDatabase();

            case 'elenco_licenze':
                $this->authorizeRegistro($request, 'registro.licenze.view');

                return view('Backend.Registri.indexLicenze')->with(['records' => Licenza::orderBy('nome')->get()]);

            case 'email':
                $this->authorizeRegistro($request, 'registro.email.view');

                if ($request->has('email_id')) {
                    return $this->showEmail($request);
                }

                return $this->registroEmail($request);

            case 'info-sito':
                $this->authorizeRegistro($request, 'registro.info-sito.view');

                return $this->infoSito($request);

            case 'errori':
                $this->authorizeRegistro($request, 'registro.errori.view');

                return $this->registroErrori($request);

            case 'upload':
                $this->authorizeRegistro($request, 'registro.upload.view');

                return $this->registroUpload($request);

        }

        abort(404);

    }

    public function eseguiBackup(Request $request)
    {
        $this->authorizeRegistro($request, 'registro.backup.run');

        Artisan::call('backup:run --only-db --disable-notifications');

        return redirect()
            ->route('registro.index', ['cosa' => 'backup-db'])
            ->with('success', 'Backup database avviato.');
    }

    public function scaricaBackup(Request $request)
    {
        $this->authorizeRegistro($request, 'registro.backup.download');

        $backupDisks = (array) config('backup.backup.destination.disks', ['local']);
        $disk = (string) $request->input('disk', $backupDisks[0] ?? 'local');
        abort_unless(in_array($disk, $backupDisks, true), 404);

        $filePath = ltrim((string) $request->input('path'), '/');
        abort_unless(str_starts_with($filePath, 'backup-database/'), 404);
        abort_unless(Storage::disk($disk)->exists($filePath), 404);

        $stream = Storage::disk($disk)->readStream($filePath);
        abort_unless($stream !== false, 404);

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, basename($filePath));
    }

    protected function authorizeRegistro(Request $request, string $permission): void
    {
        $user = $request->user();

        abort_unless($user && ($user->hasRole('admin') || $user->can('admin') || $user->can($permission)), 403);
    }

    protected function showEmail($request)
    {
        $record = RegistroEmail::find($request->input('email_id'));
        abort_if(! $record, 404, 'Questa email non esiste');

        return view('Backend.Registri.showEmail', [
            'record' => $record,
            'minW' => 'mw-850px',
            'titoloPagina' => 'Dettaglio email',
            'breadcrumbs' => [
                action([RegistriController::class, 'index'], ['cosa' => 'email']) => 'Ritorna a email inviate', ],
        ]);

    }

    protected function registroLogin($request)
    {
        $filtro = false;
        $recordsQB = RegistroLogin::with('utente')->with('impersonatoDa')->orderBy('id', 'desc');
        if ($request->input('giorno')) {
            $recordsQB->whereDate('created_at', Carbon::createFromFormat('d/m/Y', $request->input('giorno')));
            $filtro = true;
        }

        if ($request->input('riuscito')) {
            $recordsQB->where('riuscito', $request->input('riuscito') - 10);
            $filtro = true;
        }
        if ($request->input('user_id')) {
            $recordsQB->where('user_id', $request->input('user_id'));
            $filtro = true;
        }

        $records = $recordsQB->paginate(100);
        if ($filtro) {
            $records->appends($_GET);

        }

        return view('Backend.Registri.indexLogin')->with([
            'records' => $records,
            'filtro' => $filtro,
            'controller' => OperatoreController::class,
            'titoloPagina' => 'Elenco login',
        ]);

    }

    protected function registroEmail($request)
    {

        $filtro = false;
        $recordsQB = RegistroEmail::orderBy('id', 'desc');
        if ($request->input('giorno')) {
            try {
                $recordsQB->whereDate('data', Carbon::createFromFormat('d/m/Y', $request->input('giorno')));
                $filtro = true;
            } catch (\Throwable $e) {
            }
        }

        $modulo = (string) $request->input('modulo', '');
        if ($modulo !== '') {
            if ($modulo === 'telefonia') {
                $recordsQB->where(function ($q) {
                    $q->where('subject', 'like', '%contratto%')
                        ->orWhere('subject', 'like', '%promemoria di scadenza offerta%')
                        ->orWhere('subject', 'like', '%richiesta informazioni sullo stato di attivazione%');
                })->where('subject', 'not like', '%energia%');
                $filtro = true;
            } elseif ($modulo === 'energia') {
                $recordsQB->where('subject', 'like', '%energia%');
                $filtro = true;
            } elseif ($modulo === 'caf-patronato') {
                $recordsQB->where(function ($q) {
                    $q->where('subject', 'like', 'Richiesta % per %')
                        ->orWhere('subject', 'like', 'Invio pratica %');
                });
                $filtro = true;
            }
        }

        $records = $recordsQB->paginate(100);
        if ($filtro) {
            $records->appends($_GET);
        }

        return view('Backend.Registri.indexEmail')->with([
            'records' => $records,
            'filtro' => $filtro,
            'controller' => RegistriController::class,
            'titoloPagina' => 'Registro email inviate',
            'moduli' => [
                'telefonia' => 'Contratti Telefonia',
                'energia' => 'Contratti Energia',
                'caf-patronato' => 'Caf / Patronato Agenti',
            ],

        ]);

    }

    protected function registroErrori(Request $request)
    {
        $records = collect($this->readLaravelLogLines((int) $request->input('righe', 2500)))
            ->reverse()
            ->filter(function (string $line) {
                return Str::contains($line, [
                    '.ERROR:',
                    'production.ERROR',
                    'local.ERROR',
                    'slow_request',
                    'slow_query',
                    'failed_upload',
                    'Exception',
                ]);
            })
            ->take(250)
            ->map(fn (string $line) => $this->parseLogLine($line))
            ->values();

        return view('Backend.Registri.indexErrori', [
            'records' => $records,
            'titoloPagina' => 'Registro errori server',
        ]);
    }

    protected function registroUpload(Request $request)
    {
        $auditLogs = collect();
        if (Schema::hasTable('files_audit_logs')) {
            $auditLogs = FileAuditLog::with('utente')
                ->orderByDesc('id')
                ->limit(100)
                ->get();
        }

        $allegati = collect([
            'Telefonia' => AllegatoContratto::class,
            'Energia' => AllegatoContrattoEnergia::class,
            'CAF / Patronato' => AllegatoCafPatronato::class,
            'Servizi / Visure' => AllegatoServizio::class,
        ])->flatMap(function (string $modelClass, string $modulo) {
            $model = new $modelClass;
            if (! Schema::hasTable($model->getTable())) {
                return collect();
            }

            return $modelClass::query()
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(function ($record) use ($modulo) {
                    return [
                        'modulo' => $modulo,
                        'id' => $record->id,
                        'filename_originale' => $record->filename_originale ?? basename((string) $record->path_filename),
                        'path_filename' => $record->path_filename ?? null,
                        'dimensione_file' => $record->dimensione_file ?? null,
                        'tipo_file' => $record->tipo_file ?? null,
                        'created_at' => $record->created_at ?? null,
                    ];
                });
        })->sortByDesc('id')->take(150)->values();

        return view('Backend.Registri.indexUpload', [
            'auditLogs' => $auditLogs,
            'allegati' => $allegati,
            'titoloPagina' => 'Registro upload e allegati',
        ]);
    }

    protected function registroModifiche(Request $request)
    {
        $records = Schema::hasTable('registro_attivita')
            ? RegistroAttivita::with('utente')->orderByDesc('id')->paginate(100)
            : collect();

        return view('Backend.Registri.indexModifiche', [
            'records' => $records,
            'titoloPagina' => 'Registro modifiche',
        ]);
    }

    protected function readLaravelLogLines(int $lines): array
    {
        $path = storage_path('logs/laravel.log');
        if (! is_file($path)) {
            return [];
        }

        $lines = max(200, min($lines, 10000));
        $content = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return array_slice($content ?: [], -$lines);
    }

    protected function parseLogLine(string $line): array
    {
        preg_match('/^\[([^\]]+)\]\s+([^:]+):\s*(.*)$/', $line, $matches);

        $message = $matches[3] ?? $line;
        $type = 'Errore';
        if (Str::contains($message, 'slow_request')) {
            $type = 'Richiesta lenta';
        } elseif (Str::contains($message, 'slow_query')) {
            $type = 'Query lenta';
        } elseif (Str::contains($message, 'failed_upload')) {
            $type = 'Upload fallito';
        }

        return [
            'data' => $matches[1] ?? null,
            'livello' => isset($matches[2]) ? trim($matches[2]) : null,
            'tipo' => $type,
            'messaggio' => Str::limit($message, 1200),
        ];
    }

    protected function infoSito($request)
    {

        $stat['allegati_telefonia'] = AllegatoContratto::sum('dimensione_file');
        $stat['allegati_energia'] = AllegatoContrattoEnergia::sum('dimensione_file');
        $stat['allegati_servizi_finanziari'] = 0;
        $stat['allegati_caf_patronato'] = AllegatoCafPatronato::sum('dimensione_file');
        $stat['allegati_attivazioni_sim'] = 0;
        $stat['allegati_visure'] = AllegatoServizio::where('allegato_type', 'App\Models\Visura')->sum('dimensione_file');

        return view('Backend.Registri.infoSito', [
            'titoloPagina' => 'Info varie',
            'stat' => $stat,
        ]);

    }

    protected function backupDatabase()
    {
        $statuses = BackupDestinationStatusFactory::createForMonitorConfig(config('backup.monitor_backups'));
        [$headers, $rows] = $this->displayOverview($statuses);

        $backupName = (string) config('backup.backup.name', 'backup-database');
        $backupDisks = (array) config('backup.backup.destination.disks', ['local']);

        $files = collect($backupDisks)
            ->flatMap(function (string $disk) use ($backupName) {
                return collect(Storage::disk($disk)->allFiles($backupName))
                    ->filter(function (string $path) {
                        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip';
                    })
                    ->map(function (string $path) use ($disk) {
                        return [
                            'disk' => $disk,
                            'path' => $path,
                            'fileSize' => Storage::disk($disk)->size($path),
                            'lastModified' => Storage::disk($disk)->lastModified($path),
                        ];
                    });
            })
            ->sortByDesc('lastModified')
            ->values();

        return view('Backend.Registri.showBackup', [
            'headers' => $headers,
            'rows' => $rows,
            'titoloPagina' => 'Registro backup database',
            'files' => $files,
        ]);

    }

    protected function displayOverview(Collection $backupDestinationStatuses)
    {
        $headers = ['Nome', 'Disco', 'Raggiungibile', 'Integro', 'numero di backups', 'Ultimo backup', 'Spazio utilizzato'];

        $rows = $backupDestinationStatuses->map(function (BackupDestinationStatus $backupDestinationStatus) {
            return $this->convertToRow($backupDestinationStatus);
        });

        return [$headers, $rows];
    }

    public function convertToRow(BackupDestinationStatus $backupDestinationStatus): array
    {
        $destination = $backupDestinationStatus->backupDestination();

        $row = [
            $destination->backupName(),
            'disk' => $destination->diskName(),
            Format::emoji($destination->isReachable()),
            Format::emoji($backupDestinationStatus->isHealthy()),
            'amount' => $destination->backups()->count(),
            'newest' => $this->getFormattedBackupDate($destination->newestBackup()),
            'usedStorage' => Format::humanReadableSize($destination->usedStorage()),
        ];

        if (! $destination->isReachable()) {
            foreach (['amount', 'newest', 'usedStorage'] as $propertyName) {
                $row[$propertyName] = '/';
            }
        }

        if ($backupDestinationStatus->getHealthCheckFailure() !== null) {
            $row['disk'] = '<error>'.$row['disk'].'</error>';
        }

        return $row;
    }

    protected function getFormattedBackupDate(?Backup $backup = null)
    {
        return is_null($backup)
            ? 'Nessun backup'
            : $this::ageInDays($backup->date());
    }

    public static function ageInDays(Carbon $date): string
    {
        return $date->diffForHumans();
    }
}
