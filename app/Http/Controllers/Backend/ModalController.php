<?php

namespace App\Http\Controllers\Backend;

use App\Http\Services\BrtService;
use App\Models\AllegatoAttivazioneSim;
use App\Models\AllegatoCafPatronato;
use App\Models\AllegatoContratto;
use App\Models\AllegatoServizio;
use App\Models\AttivazioneSim;
use App\Models\CafPatronato;
use App\Models\CartellaFiles;
use App\Models\Comparasemplice;
use App\Models\ContrattoTelefonia;
use App\Models\ContrattoEnergia;
use App\Models\EsitoComparasemplice;
use App\Models\EsitoSegnalazione;
use App\Models\EsitoTelefonia;
use App\Models\EsitoAttivazioneSim;
use App\Models\EsitoCafPatronato;
use App\Models\EsitoContrattoEnergia;
use App\Models\EsitoServizioFinanziario;
use App\Models\EsitoVisura;
use App\Models\Nazione;
use App\Models\Notifica;
use App\Models\NotificaLettura;
use App\Models\PianoRetribuzione;
use App\Models\Ordine;
use App\Models\ProduzioneOperatore;
use App\Models\Segnalazione;
use App\Models\ServizioFinanziario;
use App\Models\SostituzioneSim;
use App\Models\Visura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class ModalController extends Controller
{
    public function show(Request $request, $modal, $id = null)
    {
        switch ($modal) {

            case 'pudo_lista':
                $nazione = Nazione::find($request->input('nazione'));
                $service = new BrtService();
                $pudos = $service->pudo($nazione->alpha3, $request->input('citta'), $request->input('cap'));
                return view('Backend.SpedizioneBrt.modalPudo', [
                    'pudos' => $pudos['pudo'],
                    'titoloPagina' => 'Seleziona pudo ']);
            case 'calcola_volumi':
                return view('Backend.SpedizioneBrt.modalVolumi', [
                    'colli' => $request->input('colli', 1),
                    'titoloPagina' => 'Calcolo volumi '
                ]);

            case 'sostituzione-sim':
                $attivazione = AttivazioneSim::find($id);
                abort_if(!$attivazione, 404);
                $record = new SostituzioneSim();
                $record->attivazione_sim_id = $id;
                return view('Backend.AttivazioneSim.modalSostituzione', [
                    'attivazione' => $attivazione,
                    'titoloPagina' => 'Sostituzione sim per ' . $attivazione->nominativo(),
                    'record' => $record,
                    'controller' => SostituzioneSimController::class
                ]);

            case 'richiedi_visura':
                $record = ContrattoTelefonia::find($id);
                abort_if(!$record, 404);
                $prezzo = config('configurazione.prezzo_visura.ordinaria-' . $record->natura_giuridica);
                if (Auth::user()->portafoglio < $prezzo) {
                    $testo = 'Nel tuo portafoglio non hai credito sufficiente per sostenere il costo della visura camerale per <span class="fw-bolder">'
                        . strtoupper(str_replace('-', ' ', $record->natura_giuridica)) . '</span> al prezzo di ' . \App\importo($prezzo, true) .
                        '<br>Ricarica il tuo portafoglio';
                    $tipo = 'warning';
                } else {
                    $testo = 'Richiedi la visura camerale per <span class="fw-bolder">' . strtoupper(str_replace('-', ' ', $record->natura_giuridica)) . '</span> al prezzo di ' . \App\importo($prezzo, true) . ' che ti verrà scalato dal tuo portafoglio';
                    $tipo = 'primary';
                }
                return view('Backend.VisuraCamerale.modalVisura', [
                    'id' => $id,
                    'titoloPagina' => 'Richiedi visura camerale ' . $record->ragione_sociale,
                    'record' => $record,
                    'prezzo' => $prezzo,
                    'testo' => $testo,
                    'tipo' => $tipo
                ]);

            case 'upload-documento':
                $record = CartellaFiles::find($id);
                abort_if(!$record, 404);
                return view('Backend.CartellaFiles.modalDropzone', [
                    'id' => $id,
                    'titoloPagina' => 'Carica file in ' . $record->nome
                ]);

            case 'dettaglio_produzione':
                $record = ProduzioneOperatore::find($id);
                abort_if(!$record, 404);
                return view('Backend.Agente.show.modalDettaglioProduzione', [
                    'id' => $id,
                    'titoloPagina' => 'Dettaglio calcolo produzione ' . $record->mese . '/' . $record->anno,
                    'record' => $record
                ]);

            case 'modal-allegati-telefonia':
                $record = ContrattoTelefonia::with('allegati')->with('tipoContratto')->find($id);
                return view('Backend.ContrattoTelefonia.modalDownload', [
                    'record' => $record,
                    'id' => $id,
                    'titoloPagina' => 'Allegati contratto',
                    'pda' => $record->tipoContratto->pda,
                ]);

            case 'modal-allegati-energia':
                $record = ContrattoEnergia::with('allegati')->find($id);
                return view('Backend.ContrattoEnergia.modalDownload', [
                    'record' => $record,
                    'id' => $id,
                    'titoloPagina' => 'Allegati contratto Energia',
                ]);

            case 'modal-allegati-attivazione':
                $record = AttivazioneSim::with('allegati')->find($id);
                return view('Backend.AttivazioneSim.modalDownload', [
                    'record' => $record,
                    'id' => $id,
                    'titoloPagina' => 'Allegati attivazione',
                ]);

            case 'modal-allegati-tutti':
                $records = AllegatoServizio::where('allegato_id', $id)->where('allegato_type', $request->input('classe'))
                    ->where('per_cliente', $request->input('per_cliente', 0))
                    ->get();
                return view('Backend._components.modalDownloadTutti', [
                    'records' => $records,
                    'id' => $id,
                    'titoloPagina' => 'Download allegati',
                ]);

            case 'modal-allegati-caf':
                $recordsCliente = AllegatoCafPatronato::where('caf_patronato_id', $id)->where('per_cliente', 1)->get();
                $records = AllegatoCafPatronato::where('caf_patronato_id', $id)->where('per_cliente', 0)->get();
                return view('Backend.CafPatronato.modalDownload', [
                    'recordsCliente' => $recordsCliente,
                    'records' => $records,
                    'id' => $id,
                    'titoloPagina' => 'Allegati pratica',
                ]);

            case 'vedi-notifica':
                $record = Notifica::find($id);
                $visto = NotificaLettura::firstOrNew(['notifica_id' => $id, 'user_id' => \Auth::id()]);
                $visto->save();

                return view('Backend.Notifica.modalShow', [
                    'record' => $record,
                    'id' => $id,
                    'titoloPagina' => $record->titolo ?? 'Notifica',
                ]);

            case 'modifica-stato':
                $record = ContrattoTelefonia::find($id);
                abort_if(!$record, 404, 'Questo contratto non esiste');
                $statiQb = EsitoTelefonia::query();
                if ($record->esito !== 'bozza') {
                    $statiQb->where('id', '<>', 'bozza');
                }

                if (Auth::user()->hasPermissionTo('supervisore')) {
                    return view('Backend.ContrattoTelefonia.modalModificaStatoSupervisore', [
                        'record' => $record,
                        'titoloPagina' => 'Modifica stato contratto',
                        'stati' => $statiQb->get(),
                        'controller' => ContrattoTelefoniaController::class,
                        'uid' => $record->uid,
                    ]);
                }
                return view('Backend.ContrattoTelefonia.modalModificaStato', [
                    'record' => $record,
                    'titoloPagina' => 'Modifica stato contratto',
                    'stati' => $statiQb->get(),

                ]);

            case 'modifica-stato-contratto-energia':
                $record = ContrattoEnergia::find($id);
                abort_if(!$record, 404, 'Questo contratto non esiste');
                $statiQb = EsitoContrattoEnergia::query();
                if ($record->esito !== 'bozza') {
                    $statiQb->where('id', '<>', 'bozza');
                }

                if (Auth::user()->hasPermissionTo('supervisore')) {
                    return view('Backend.ContrattoEnergia.modalModificaStatoSupervisore', [
                        'record' => $record,
                        'titoloPagina' => 'Modifica stato contratto energia',
                        'stati' => $statiQb->get(),
                        'controller' => ContrattoEnergiaController::class,
                        'uid' => $record->uid,
                    ]);
                }

                return view('Backend.ContrattoEnergia.modalModificaStato', [
                    'record' => $record,
                    'titoloPagina' => 'Modifica stato contratto energia',
                    'stati' => $statiQb->get(),
                    'controller' => ContrattoEnergiaController::class,

                ]);
            case 'modifica-stato-segnalazione':
                $record = Segnalazione::find($id);
                abort_if(!$record, 404, 'Questa segnalazione non esiste');
                $statiQb = EsitoSegnalazione::query();
                return view('Backend.Segnalazione.modalModificaStato', [
                    'record' => $record,
                    'titoloPagina' => 'Modifica stato segnalazione',
                    'stati' => $statiQb->get(),
                    'controller' => SegnalazioneController::class,

                ]);

            case 'modifica-stato-servizio':
                $record = ServizioFinanziario::find($id);
                abort_if(!$record, 404, 'Questo ' . ServizioFinanziario::NOME_SINGOLARE . ' non esiste');
                $statiQb = EsitoServizioFinanziario::query();

                return view('Backend.ServizioFinanziario.modalModificaStato', [
                    'record' => $record,
                    'titoloPagina' => 'Modifica stato ' . ServizioFinanziario::NOME_SINGOLARE,
                    'stati' => $statiQb->get()
                ]);

            case 'modifica-esito-compara':
                $record = Comparasemplice::find($id);
                abort_if(!$record, 404, 'Questo ' . Comparasemplice::NOME_SINGOLARE . ' non esiste');
                $statiQb = EsitoComparasemplice::query();

                return view('Backend.Comparasemplice.modalModificaStato', [
                    'record' => $record,
                    'titoloPagina' => 'Modifica stato ' . Comparasemplice::NOME_SINGOLARE,
                    'stati' => $statiQb->get()
                ]);

            case 'modifica-stato-attivazione':
                $record = AttivazioneSim::find($id);
                abort_if(!$record, 404, 'Questa ' . AttivazioneSim::NOME_SINGOLARE . ' non esiste');
                $statiQb = EsitoAttivazioneSim::query();
                return view('Backend.AttivazioneSim.modalModificaStato', [
                    'record' => $record,
                    'titoloPagina' => 'Modifica stato ' . AttivazioneSim::NOME_SINGOLARE,
                    'stati' => $statiQb->get(),
                    'controller' => AttivazioneSimController::class,
                    'uid' => $record->uid,

                ]);

            case 'modifica-stato-caf':
                $record = CafPatronato::find($id);
                abort_if(!$record, 404, 'Questo ' . CafPatronato::NOME_SINGOLARE . ' non esiste');
                $statiQb = EsitoCafPatronato::query();

                return view('Backend.CafPatronato.modalModificaStato', [
                    'record' => $record,
                    'titoloPagina' => 'Modifica stato ' . CafPatronato::NOME_SINGOLARE,
                    'stati' => $statiQb->get(),
                    'controller' => CafPatronatoController::class,
                    'uid' => $record->uid,
                ]);
            case 'modifica-stato-visura':
                $record = Visura::find($id);
                abort_if(!$record, 404, 'Questa ' . Visura::NOME_SINGOLARE . ' non esiste');
                $statiQb = EsitoVisura::query();

                return view('Backend.Visura.modalModificaStato', [
                    'record' => $record,
                    'titoloPagina' => 'Modifica stato ' . Visura::NOME_SINGOLARE,
                    'stati' => $statiQb->get(),
                    'controller' => VisuraController::class,
                    'uid' => $record->uid,
                    'allegatiEsistenti' => \App\Models\AllegatoServizio::perBlade(null, $record->id, get_class($record), 1)
                ]);

            default:
                return 'Voce non gestita:' . $modal;
        }
    }
}
