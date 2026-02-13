<?php

namespace App\Http\Controllers\Backend;

use App\Models\Licenza;
use App\Models\RegistroEmail;
use App\Models\RegistroLogin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, $cosa)
    {

        switch ($cosa) {
            case 'login':
                return $this->registroLogin($request);

            case 'modifiche':
                return $this->registroModifiche($request);

            case 'backup-db':
                if ($request->input('scarica')) {
                    $fileName = $request->input('scarica');
                    $path_to_file = '/backup-database/' . $fileName;
                    return response()->download(Storage::disk('local')->path($path_to_file), $fileName);
                }
                if ($request->has('esegui')) {
                    \Artisan::call('backup:run --only-db --disable-notifications');
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
            $records->appends($_GET)->links();

        }

        return view('Backend.Registri.indexLogin')->with([
            'records' => $records,
            'filtro' => $filtro,
            'controller' => OperatoreController::class,
            'titoloPagina' => 'Elenco login'
        ]);

    }

    public function registroModifiche(Request $request)
    {
        $recordsQB = Audit::with(['user' => function ($q) {
            $q->select('id', 'name', 'cognome', 'ragione_sociale');
        }]);

        $orderBy = false;
        if ($request->has('ordine')) {
            $recordsQB->where('tags', 'ordine_' . $request->input('ordine'));
            $orderBy = true;

        }

        if ($request->input('id')) {
            $recordsQB->where('auditable_id', $request->input('id'));
        }

        if ($request->input('giorno')) {
            $recordsQB->whereDate('created_at', Carbon::createFromFormat('d/m/Y', $request->input('giorno')));
        }
        if ($orderBy == false) {
            $recordsQB->orderBy('id', 'desc');
        }

        return view('Backend.Registri.indexModifiche')->with([
            'records' => $recordsQB->paginate(100)->withQueryString()
        ]);


    }


    protected function registroEmail($request)
    {

        $filtro = false;
        $recordsQB = RegistroEmail::orderBy('id', 'desc');
        if ($request->input('giorno')) {
            $recordsQB->whereDate('data', Carbon::createFromFormat('d/m/Y', $request->input('giorno')));
            $filtro = true;
        }

        return view('Backend.Registri.indexEmail')->with([
            'records' => $recordsQB->paginate(100),
            'filtro' => $filtro,
            'controller' => RegistriController::class,
            'titoloPagina' => 'Registro email inviate',

        ]);

    }

    protected function infoSito($request)
    {

        $stat['allegati_telefonia'] = \App\Models\AllegatoContratto::sum('dimensione_file');
        $stat['allegati_energia'] = \App\Models\AllegatoContrattoEnergia::sum('dimensione_file');
        $stat['allegati_servizi_finanziari'] = \App\Models\AllegatoServizio::where('allegato_type', 'App\Models\ServizioFinanziario')->sum('dimensione_file');
        $stat['allegati_caf_patronato'] = \App\Models\AllegatoCafPatronato::sum('dimensione_file');
        $stat['allegati_attivazioni_sim'] = \App\Models\AllegatoAttivazioneSim::sum('dimensione_file');
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

        $files = collect(\Storage::disk('local')->listContents('/backup-database'))->sortBy('basename');

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

    protected function getFormattedBackupDate(Backup $backup = null)
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
