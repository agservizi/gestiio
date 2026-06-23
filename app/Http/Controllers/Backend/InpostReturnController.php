<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\InpostService;
use App\Models\InpostReturn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class InpostReturnController extends Controller
{
    public function index(Request $request)
    {
        $records = $this->applicaFiltri($request)->paginate(config('configurazione.paginazione'));
        $records->appends($request->query());

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.InpostReturn.tabella', [
                    'records' => $records,
                    'controller' => get_class($this),
                ])->render()),
            ];
        }

        return view('Backend.InpostReturn.index', [
            'records' => $records,
            'controller' => get_class($this),
            'titoloPagina' => 'Elenco '.InpostReturn::NOME_PLURALE,
            'testoNuovo' => 'Nuovo '.InpostReturn::NOME_SINGOLARE,
            'testoCerca' => 'Cerca per destinatario',
        ]);
    }

    public function create()
    {
        $record = new InpostReturn;

        $user = Auth::user();
        if ($user instanceof User && $user->hasPermissionTo('agente')) {
            $record->agente_id = $user->id;
        }

        return view('Backend.InpostReturn.edit', [
            'record' => $record,
            'controller' => get_class($this),
            'titoloPagina' => 'Nuovo '.InpostReturn::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco '.InpostReturn::NOME_PLURALE],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());
        $record = new InpostReturn;
        $this->salvaDati($record, $request);
        $this->creaReturnRemoto($record);

        return $this->backToIndex();
    }

    public function show($id)
    {
        $record = InpostReturn::with(['chiamate' => fn ($q) => $q->latest()])->find($id);
        abort_if(! $record, 404, 'Questo reso InPost non esiste');

        return view('Backend.InpostReturn.show', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => InpostReturn::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco '.InpostReturn::NOME_PLURALE],
        ]);
    }

    public function destroy($id)
    {
        $record = InpostReturn::find($id);
        abort_if(! $record, 404, 'Questo reso InPost non esiste');
        $record->delete();

        return [
            'success' => true,
            'redirect' => action([self::class, 'index']),
        ];
    }

    public function etichetta($id)
    {
        $record = InpostReturn::find($id);
        abort_if(! $record, 404, 'Questo reso InPost non esiste');

        $url = $record->qrCodeUrl();
        abort_if(! $url, 404, 'QR code non disponibile');

        $service = new InpostService;
        $raw = $service->requestRawPublic('get', $url, [], $record);

        return Response::make($raw['body'], 200, [
            'Content-Type' => $raw['content_type'] ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="inpost-return-'.$record->id.'.pdf"',
        ]);
    }

    protected function applicaFiltri(Request $request)
    {
        $qb = InpostReturn::query()->latest()->with('agente:id,alias');
        $term = trim((string) $request->input('cerca'));

        if ($term !== '') {
            $qb->where('receiver_name', 'like', "%{$term}%");
        }

        return $qb;
    }

    protected function salvaDati(InpostReturn $record, Request $request): void
    {
        DB::transaction(function () use ($record, $request) {
            if (! $record->exists) {
                $record->agente_id = $request->input('agente_id') ?: Auth::id();
            }

            $record->receiver_name = $request->input('receiver_name');
            $record->receiver_email = $request->input('receiver_email');
            $record->receiver_phone = $request->input('receiver_phone');
            $record->point_id = $request->input('point_id');
            $record->point_label = $request->input('point_label');
            $record->customer_reference = $request->input('customer_reference');

            $record->save();
        });
    }

    protected function creaReturnRemoto(InpostReturn $record): void
    {
        $service = new InpostService;
        $record->request_payload = $service->buildReturnPayload($record);
        $response = $service->createReturn($record);
        $record->response = array_merge($record->response ?? [], $response);
        $record->remote_id = data_get($response, 'id') ?: data_get($response, 'shipmentId') ?: $record->remote_id;
        $record->status = data_get($response, 'status') ?: (data_get($response, 'error') ? 'ERROR' : 'CREATED');
        $record->save();
    }

    protected function rules(): array
    {
        return [
            'agente_id' => ['required'],
            'receiver_name' => ['required', 'max:120'],
            'receiver_email' => ['nullable', 'email', 'max:255'],
            'receiver_phone' => ['nullable', 'max:32'],
            'point_id' => ['required', 'max:255'],
            'point_label' => ['nullable', 'max:255'],
            'customer_reference' => ['nullable', 'max:100'],
        ];
    }

    protected function backToIndex()
    {
        return redirect()->action([self::class, 'index']);
    }
}
