<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ProdottoEbike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function App\getInputNumero;

class EbikeProdottoController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()?->hasPermissionTo('admin'), 403);

            return $next($request);
        });
    }

    /**
     * @return mixed
     */
    public function index(Request $request)
    {
        $records = ProdottoEbike::query()
            ->orderByDesc('id')
            ->paginate(config('configurazione.paginazione'))
            ->withQueryString();

        return view('Backend.EbikeProdotto.index', [
            'records' => $records,
            'controller' => get_class($this),
            'titoloPagina' => 'Catalogo ebike',
            'testoNuovo' => 'Nuovo '.ProdottoEbike::NOME_SINGOLARE,
        ]);
    }

    /**
     * @return mixed
     */
    public function create()
    {
        $record = new ProdottoEbike;
        $record->sku = $this->generaSku();

        return view('Backend.EbikeProdotto.edit', [
            'record' => $record,
            'controller' => get_class($this),
            'titoloPagina' => 'Nuovo '.ProdottoEbike::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna al catalogo'],
        ]);
    }

    /**
     * @return mixed
     */
    public function store(Request $request)
    {
        $request->validate($this->rules(null));
        $record = new ProdottoEbike;
        $this->salvaDati($record, $request);

        return $this->backToIndex('Prodotto creato correttamente.');
    }

    /**
     * @param  int  $id
     * @return mixed
     */
    public function edit($id)
    {
        $record = ProdottoEbike::find($id);
        abort_if(! $record, 404, 'Questo prodotto non esiste');

        return view('Backend.EbikeProdotto.edit', [
            'record' => $record,
            'controller' => get_class($this),
            'titoloPagina' => 'Modifica '.ProdottoEbike::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna al catalogo'],
        ]);
    }

    /**
     * @param  int  $id
     * @return mixed
     */
    public function update(Request $request, $id)
    {
        $record = ProdottoEbike::find($id);
        abort_if(! $record, 404, 'Questo prodotto non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);

        return $this->backToIndex('Prodotto aggiornato correttamente.');
    }

    /**
     * @param  int  $id
     * @return mixed
     */
    public function destroy($id)
    {
        $record = ProdottoEbike::find($id);
        abort_if(! $record, 404, 'Questo prodotto non esiste');

        abort_if($record->righe()->exists(), 422, 'Non eliminabile: presente in uno o piu ordini. Disattivalo invece di eliminarlo.');

        $record->delete();

        return [
            'success' => true,
            'redirect' => action([self::class, 'index']),
        ];
    }

    /**
     * @param  ProdottoEbike  $model
     * @return mixed
     */
    protected function salvaDati($model, Request $request)
    {
        $nuovo = ! $model->id;

        $model->nome = trim((string) $request->input('nome'));
        if ($nuovo) {
            $model->sku = $this->generaSku();
        }
        $model->descrizione = $request->input('descrizione');
        $model->prezzo = getInputNumero($request->input('prezzo'));
        $model->giacenza = (int) $request->input('giacenza', 0);
        $model->attivo = $request->boolean('attivo');

        if ($request->hasFile('immagine')) {
            $model->immagine = $request->file('immagine')->store('ebike/prodotti', 'public');
        }

        $model->save();

        return $model;
    }

    protected function backToIndex(?string $status = null)
    {
        $redirect = redirect()->action([self::class, 'index']);
        if ($status !== null) {
            $redirect->with('status', $status);
        }

        return $redirect;
    }

    protected function rules($id = null)
    {
        return [
            'nome' => ['required', 'max:255'],
            'descrizione' => ['nullable', 'string'],
            'prezzo' => ['required'],
            'giacenza' => ['required', 'integer', 'min:0'],
            'immagine' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function generaSku(): string
    {
        $ultimo = ProdottoEbike::withTrashed()
            ->where('sku', 'like', 'EB-%')
            ->orderByRaw('CAST(SUBSTRING(sku, 4) AS UNSIGNED) DESC')
            ->value('sku');

        $numero = 1;
        if ($ultimo && preg_match('/^EB-(\d+)$/', $ultimo, $matches)) {
            $numero = ((int) $matches[1]) + 1;
        }

        return 'EB-'.str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }
}
