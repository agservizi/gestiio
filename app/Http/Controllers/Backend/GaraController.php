<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\GaraEvalService;
use App\Models\Gara;
use App\Models\GaraSoglia;
use App\Models\Gestore;
use App\Models\GestoreContrattoEnergia;
use App\Models\Listino;
use App\Models\TelefoniaOfferta;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GaraController extends Controller
{
    protected bool $conFiltro = false;

    public function index(Request $request)
    {
        $nomeClasse = get_class($this);
        $recordsQB = $this->applicaFiltri($request);

        $ordinamenti = [
            'recente' => ['testo' => 'Più recente', 'filtro' => fn ($q) => $q->orderByDesc('id')],
            'periodo' => ['testo' => 'Periodo', 'filtro' => fn ($q) => $q->orderByDesc('anno')->orderByDesc('mese')->orderBy('nome')],
        ];

        $orderByUser = Auth::user()->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');
        $orderBy = $orderByString ?: ($orderByUser ?: 'periodo');
        if ($orderByUser != $orderByString && $orderByString) {
            Auth::user()->setExtra([$nomeClasse => $orderBy]);
        }
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);
        $records = $recordsQB->withCount('soglie')->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.Gara.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])),
            ];
        }

        return view('Backend.Gara.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco '.Gara::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova '.Gara::NOME_SINGOLARE,
            'testoCerca' => 'Cerca gara',
        ]);
    }

    protected function applicaFiltri(Request $request): Builder
    {
        $queryBuilder = Gara::query();
        $term = $request->input('cerca');
        if ($term) {
            foreach (explode(' ', $term) as $t) {
                $queryBuilder->where(DB::raw("concat_ws(' ',nome,canale)"), 'like', "%{$t}%");
            }
            $this->conFiltro = true;
        }
        if ($request->filled('canale')) {
            $queryBuilder->where('canale', $request->input('canale'));
            $this->conFiltro = true;
        }

        return $queryBuilder;
    }

    public function create(): View
    {
        $record = new Gara;
        $record->attivo = true;
        $record->canale = 'telefonia';
        $record->ambito = 'tutti';
        $record->anno = (int) now()->year;
        $record->mese = (int) now()->month;

        return view('Backend.Gara.edit', $this->formData($record, 'Nuova '.Gara::NOME_SINGOLARE));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate($this->rules());
        $record = new Gara;
        $this->salvaDati($record, $request);
        app(GaraEvalService::class)->ricalcolaMese((int) $record->anno, (int) $record->mese);

        return redirect()->action([self::class, 'index'])->with('success', 'Gara creata');
    }

    public function edit($id): View
    {
        $record = Gara::with(['soglie', 'agenti:id,nome,cognome'])->findOrFail($id);

        return view('Backend.Gara.edit', $this->formData($record, 'Modifica '.Gara::NOME_SINGOLARE, true));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $record = Gara::findOrFail($id);
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);
        app(GaraEvalService::class)->ricalcolaMese((int) $record->anno, (int) $record->mese);

        return redirect()->action([self::class, 'index'])->with('success', 'Gara aggiornata');
    }

    public function destroy($id)
    {
        $record = Gara::findOrFail($id);
        $record->delete();

        return [
            'success' => true,
            'redirect' => action([self::class, 'index']),
        ];
    }

    protected function formData(Gara $record, string $titolo, bool $eliminabile = false): array
    {
        return [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => $titolo,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco '.Gara::NOME_PLURALE],
            'listini' => Listino::query()->orderBy('nome')->get(['id', 'nome']),
            'gestoriTel' => Gestore::query()->where('attivo', 1)->orderBy('nome')->get(['id', 'nome']),
            'gestoriEn' => GestoreContrattoEnergia::query()->orderBy('nome')->get(['id', 'nome']),
            'offerteTel' => TelefoniaOfferta::query()->attive()->orderBy('nome')->get(['id', 'nome', 'gestore_id', 'prezzo_mese']),
            'agentiSelect' => User::query()
                ->whereHas('permissions', fn ($q) => $q->where('name', 'agente'))
                ->orderBy('cognome')
                ->orderBy('nome')
                ->get(['id', 'nome', 'cognome']),
            'soglie' => $record->relationLoaded('soglie') ? $record->soglie : $record->soglie()->get(),
        ];
    }

    protected function salvaDati(Gara $model, Request $request): Gara
    {
        $model->nome = trim((string) $request->input('nome'));
        $model->canale = $request->input('canale');
        $model->anno = (int) $request->input('anno');
        $model->mese = (int) $request->input('mese');
        $model->attivo = $request->boolean('attivo');
        $model->ambito = $request->input('ambito', 'tutti');
        $model->listino_id = $model->ambito === 'listino' ? ($request->input('listino_id') ?: null) : null;
        $model->gestore_id = $request->input('gestore_id') ?: null;
        $model->telefonia_offerta_id = $model->canale === 'telefonia' ? ($request->input('telefonia_offerta_id') ?: null) : null;
        $model->tipo_contratto_id = $model->canale === 'telefonia' ? ($request->input('tipo_contratto_id') ?: null) : null;
        $model->tipo_attivazione = $model->canale === 'energia' ? (trim((string) $request->input('tipo_attivazione')) ?: null) : null;
        $isBusiness = $request->input('is_business');
        $model->is_business = ($isBusiness === '' || $isBusiness === null) ? null : (bool) (int) $isBusiness;
        $model->save();

        if ($model->ambito === 'agenti') {
            $ids = collect($request->input('agente_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->all();
            $model->agenti()->sync($ids);
        } else {
            $model->agenti()->sync([]);
        }

        $this->syncSoglie($model, $request->input('soglie', []));

        return $model;
    }

    protected function syncSoglie(Gara $gara, $soglieInput): void
    {
        $keep = [];
        $rows = is_array($soglieInput) ? $soglieInput : [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $da = (int) ($row['da_valore'] ?? 0);
            if ($da <= 0) {
                continue;
            }
            $a = isset($row['a_valore']) && $row['a_valore'] !== '' ? (int) $row['a_valore'] : null;
            $premio = (float) str_replace(',', '.', (string) ($row['premio'] ?? 0));
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $s = GaraSoglia::query()->where('gara_id', $gara->id)->where('id', $id)->first();
                if (! $s) {
                    $s = new GaraSoglia;
                    $s->gara_id = $gara->id;
                }
            } else {
                $s = new GaraSoglia;
                $s->gara_id = $gara->id;
            }
            $s->da_valore = $da;
            $s->a_valore = $a;
            $s->premio = $premio;
            $s->save();
            $keep[] = $s->id;
        }

        GaraSoglia::query()
            ->where('gara_id', $gara->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep), fn ($q) => $q)
            ->delete();
    }

    protected function rules(?int $id = null): array
    {
        return [
            'nome' => 'required|string|max:191',
            'canale' => 'required|in:telefonia,energia',
            'anno' => 'required|integer|min:2020|max:2100',
            'mese' => 'required|integer|min:1|max:12',
            'ambito' => 'required|in:tutti,listino,agenti',
            'listino_id' => 'nullable|integer',
            'gestore_id' => 'nullable|integer',
            'telefonia_offerta_id' => 'nullable|integer',
            'tipo_contratto_id' => 'nullable|integer',
            'tipo_attivazione' => 'nullable|string|max:80',
            'is_business' => 'nullable',
            'attivo' => 'nullable',
        ];
    }
}
