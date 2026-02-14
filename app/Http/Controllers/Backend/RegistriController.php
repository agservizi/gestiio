<?php

namespace App\Http\Controllers\Backend;

use App\Models\Licenza;
use App\Models\RegistroEmail;
use App\Models\RegistroLogin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\Helpers\Format;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatus;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;

class RegistriController extends Controller
{


    /**
     * Display a listing of the resource.
     *
    * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function index(Request $request, $cosa)
    {

        switch ($cosa) {
            case 'login':
                return $this->registroLogin($request);

            case 'modifiche':
                abort(404);

            case 'backup-db':
                if ($request->input('scarica')) {
                    $backupDisks = (array) config('backup.backup.destination.disks', ['local']);
                    $disk = (string) $request->input('disk', $backupDisks[0] ?? 'local');
                    abort_unless(in_array($disk, $backupDisks, true), 404);

                    $filePath = ltrim((string)$request->input('scarica'), '/');
                    abort_unless(str_starts_with($filePath, 'backup-database/'), 404);
                    abort_unless(Storage::disk($disk)->exists($filePath), 404);

                    $stream = Storage::disk($disk)->readStream($filePath);
                    abort_unless($stream !== false, 404);

                    return response()->streamDownload(function () use ($stream) {
                        fpassthru($stream);
                        fclose($stream);
                    }, basename($filePath));
                }
                if ($request->has('esegui')) {
                    Artisan::call('backup:run --only-db --disable-notifications');
                }
                return $this->backupDatabase();

            case 'elenco_licenze':
                return view('Backend.Registri.indexLicenze')->with(['records' => Licenza::orderBy('nome')->get()]);
                
            case 'email':
                if ($request->has('email_id')) {
                    return $this->showEmail($request);
                }
                return $this->registroEmail($request);

            case 'info-sito':
                return $this->infoSito($request);

        }

        abort(404);

    }


    protected function showEmail($request)
    {
        $record = RegistroEmail::find($request->input('email_id'));
        abort_if(!$record, 404, 'Questa email non esiste');

        return view('Backend.Registri.showEmail', [
            'record' => $record,
            'minW' => 'mw-850px',
            'titoloPagina' => 'Dettaglio email',
            'breadcrumbs' => [
                action([\App\Http\Controllers\Backend\RegistriController::class, 'index'], ['cosa' => 'email']) => 'Ritorna a email inviate',]
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
            'titoloPagina' => 'Elenco login'
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

        $modulo = (string)$request->input('modulo', '');
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

    protected function infoSito($request)
    {

        $stat['allegati_telefonia'] = \App\Models\AllegatoContratto::sum('dimensione_file');
        $stat['allegati_energia'] = \App\Models\AllegatoContrattoEnergia::sum('dimensione_file');
        $stat['allegati_servizi_finanziari'] = 0;
        $stat['allegati_caf_patronato'] = \App\Models\AllegatoCafPatronato::sum('dimensione_file');
        $stat['allegati_attivazioni_sim'] = 0;
        $stat['allegati_visure'] =\App\Models\AllegatoServizio::where('allegato_type', 'App\Models\Visura')->sum('dimensione_file');

        return view('Backend.Registri.infoSito', [
            'titoloPagina' => 'Info varie',
            'stat' => $stat
        ]);


    }

    protected function backupDatabase()
    {
        $statuses = BackupDestinationStatusFactory::createForMonitorConfig(config('backup.monitor_backups'));
        list($headers, $rows) = $this->displayOverview($statuses);

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
            'files' => $files
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

        if (!$destination->isReachable()) {
            foreach (['amount', 'newest', 'usedStorage'] as $propertyName) {
                $row[$propertyName] = '/';
            }
        }

        if ($backupDestinationStatus->getHealthCheckFailure() !== null) {
            $row['disk'] = '<error>' . $row['disk'] . '</error>';
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
