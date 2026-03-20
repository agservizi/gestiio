<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\InpostService;
use App\Models\InpostReturn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InpostReturnController extends Controller
{
    public function index()
    {
        return view('Backend.InpostReturn.index', [
            'records' => InpostReturn::with('agente:id,alias')->latest()->paginate(config('configurazione.paginazione')),
            'controller' => self::class,
            'titoloPagina' => 'Elenco ' . InpostReturn::NOME_PLURALE,
        ]);
    }

    public function create()
    {
        $record = new InpostReturn();
        /** @var User|null $authUser */
        $authUser = Auth::user();
        if ($authUser?->hasPermissionTo('agente')) {
            $record->agente_id = Auth::id();
        }

        return view('Backend.InpostReturn.edit', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => 'Nuovo ' . InpostReturn::NOME_SINGOLARE,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());
        $record = new InpostReturn();
        $this->saveRecord($record, $request);
        $this->sendRemote($record);

        return redirect()->action([self::class, 'index']);
    }

    public function show($id)
    {
        $record = InpostReturn::with(['chiamate' => fn ($q) => $q->latest()])->findOrFail($id);

        return view('Backend.InpostReturn.show', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => InpostReturn::NOME_SINGOLARE,
        ]);
    }

    public function sync($id)
    {
        $record = InpostReturn::findOrFail($id);
        if (!$record->remote_id) {
            return redirect()->back()->withErrors(['remote_id' => 'Remote ID return non disponibile']);
        }

        $service = new InpostService();
        $response = $service->getReturn($record->remote_id, $record);
        $record->response = array_merge($record->response ?? [], ['sync' => $response]);
        $record->status = data_get($response, 'status') ?: $record->status;
        $record->save();

        return redirect()->back();
    }

    protected function saveRecord(InpostReturn $record, Request $request): void
    {
        $record->agente_id = $request->input('agente_id');
        $record->customer_reference = $request->input('customer_reference');
        $record->receiver_name = $request->input('receiver_name');
        $record->receiver_email = $request->input('receiver_email');
        $record->receiver_phone = $request->input('receiver_phone');
        $record->point_id = $request->input('point_id');
        $record->point_label = $request->input('point_label');
        $record->payload_json = $request->input('payload_json');
        $record->save();
    }

    protected function sendRemote(InpostReturn $record): void
    {
        $service = new InpostService();
        $payload = $this->payloadForRecord($record);
        $record->request_payload = $payload;
        $response = $service->createReturn($payload, $record);
        $record->response = $response;
        $record->remote_id = data_get($response, 'id') ?: data_get($response, 'returnId') ?: data_get($response, 'uuid');
        $record->status = data_get($response, 'status') ?: (data_get($response, 'error') ? 'ERROR' : 'CREATED');
        $record->save();
    }

    protected function payloadForRecord(InpostReturn $record): array
    {
        $payload = [
            'customerReference' => $record->customer_reference ?: (string) $record->id,
            'receiver' => [
                'name' => $record->receiver_name,
                'email' => $record->receiver_email,
                'phone' => $record->receiver_phone,
            ],
            'destinationPoint' => [
                'id' => $record->point_id,
                'name' => $record->point_label,
            ],
        ];

        $extra = json_decode((string) $record->payload_json, true);
        if (is_array($extra)) {
            $payload = array_replace_recursive($payload, $extra);
        }

        return $payload;
    }

    protected function rules(): array
    {
        return [
            'agente_id' => ['required'],
            'customer_reference' => ['nullable', 'max:255'],
            'receiver_name' => ['required', 'max:255'],
            'receiver_email' => ['nullable', 'email', 'max:255'],
            'receiver_phone' => ['nullable', 'max:50'],
            'point_id' => ['required', 'max:255'],
            'point_label' => ['nullable', 'max:255'],
            'payload_json' => ['nullable'],
        ];
    }
}
