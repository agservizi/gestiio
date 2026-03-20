<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\InpostService;
use App\Models\InpostPickup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InpostPickupController extends Controller
{
    public function index()
    {
        return view('Backend.InpostPickup.index', [
            'records' => InpostPickup::with('agente:id,alias')->latest()->paginate(config('configurazione.paginazione')),
            'controller' => self::class,
            'titoloPagina' => 'Elenco ' . InpostPickup::NOME_PLURALE,
        ]);
    }

    public function create()
    {
        $record = new InpostPickup();
        /** @var User|null $authUser */
        $authUser = Auth::user();
        if ($authUser?->hasPermissionTo('agente')) {
            $record->agente_id = Auth::id();
        }

        return view('Backend.InpostPickup.edit', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => 'Nuovo ' . InpostPickup::NOME_SINGOLARE,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());
        $record = new InpostPickup();
        $this->saveRecord($record, $request);
        $this->sendRemote($record);

        return redirect()->action([self::class, 'index']);
    }

    public function show($id)
    {
        $record = InpostPickup::with(['chiamate' => fn ($q) => $q->latest()])->findOrFail($id);

        return view('Backend.InpostPickup.show', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => InpostPickup::NOME_SINGOLARE,
        ]);
    }

    public function sync($id)
    {
        $record = InpostPickup::findOrFail($id);
        if (!$record->remote_id) {
            return redirect()->back()->withErrors(['remote_id' => 'Remote ID pickup non disponibile']);
        }

        $service = new InpostService();
        $response = $service->getPickup($record->remote_id, $record);
        $record->response = array_merge($record->response ?? [], ['sync' => $response]);
        $record->status = data_get($response, 'status') ?: $record->status;
        $record->save();

        return redirect()->back();
    }

    protected function saveRecord(InpostPickup $record, Request $request): void
    {
        $record->agente_id = $request->input('agente_id');
        $record->customer_reference = $request->input('customer_reference');
        $record->pickup_date = $request->input('pickup_date');
        $record->contact_name = $request->input('contact_name');
        $record->contact_email = $request->input('contact_email');
        $record->contact_phone = $request->input('contact_phone');
        $record->street = $request->input('street');
        $record->building_number = $request->input('building_number');
        $record->post_code = $request->input('post_code');
        $record->city = $request->input('city');
        $record->country_code = $request->input('country_code');
        $record->parcel_count = (int) $request->input('parcel_count', 1);
        $record->note = $request->input('note');
        $record->payload_json = $request->input('payload_json');
        $record->save();
    }

    protected function sendRemote(InpostPickup $record): void
    {
        $service = new InpostService();
        $payload = $this->payloadForRecord($record);
        $record->request_payload = $payload;
        $response = $service->createPickup($payload, $record);
        $record->response = $response;
        $record->remote_id = data_get($response, 'id') ?: data_get($response, 'pickupId') ?: data_get($response, 'uuid');
        $record->status = data_get($response, 'status') ?: (data_get($response, 'error') ? 'ERROR' : 'CREATED');
        $record->save();
    }

    protected function payloadForRecord(InpostPickup $record): array
    {
        $payload = [
            'customerReference' => $record->customer_reference ?: (string) $record->id,
            'pickupDate' => $record->pickup_date,
            'contact' => [
                'name' => $record->contact_name,
                'email' => $record->contact_email,
                'phone' => $record->contact_phone,
            ],
            'address' => [
                'street' => $record->street,
                'buildingNumber' => $record->building_number,
                'postCode' => $record->post_code,
                'city' => $record->city,
                'countryCode' => $record->country_code,
            ],
            'parcelCount' => $record->parcel_count ?: 1,
            'note' => $record->note,
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
            'pickup_date' => ['required', 'date'],
            'contact_name' => ['required', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['required', 'max:50'],
            'street' => ['required', 'max:255'],
            'building_number' => ['nullable', 'max:50'],
            'post_code' => ['required', 'max:30'],
            'city' => ['required', 'max:255'],
            'country_code' => ['required', 'size:2'],
            'parcel_count' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'max:500'],
            'payload_json' => ['nullable'],
        ];
    }
}
