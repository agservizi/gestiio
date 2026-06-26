<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\InpostService;
use App\Models\InpostPickup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InpostPickupController extends Controller
{
    public function index(Request $request)
    {
        $records = $this->applicaFiltri($request)->paginate(config('configurazione.paginazione'));
        $records->appends($request->query());

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.InpostPickup.tabella', [
                    'records' => $records,
                    'controller' => get_class($this),
                ])->render()),
            ];
        }

        return view('Backend.InpostPickup.index', [
            'records' => $records,
            'controller' => get_class($this),
            'titoloPagina' => 'Elenco '.InpostPickup::NOME_PLURALE,
            'testoNuovo' => 'Nuovo '.InpostPickup::NOME_SINGOLARE,
            'testoCerca' => 'Cerca per città o contatto',
        ]);
    }

    public function create()
    {
        $record = new InpostPickup;
        $record->country_code = 'IT';
        $record->parcel_count = 1;

        $user = Auth::user();
        if ($user instanceof User && $user->hasPermissionTo('agente')) {
            $record->agente_id = $user->id;
        }

        return view('Backend.InpostPickup.edit', [
            'record' => $record,
            'controller' => get_class($this),
            'titoloPagina' => 'Nuovo '.InpostPickup::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco '.InpostPickup::NOME_PLURALE],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());
        $this->validatePickupWindow($request);

        $record = new InpostPickup;
        $this->salvaDati($record, $request);
        $this->creaPickupRemoto($record);

        return $this->backToIndex();
    }

    public function show($id)
    {
        $record = InpostPickup::with(['chiamate' => fn ($q) => $q->latest()])->find($id);
        abort_if(! $record, 404, 'Questo ritiro InPost non esiste');

        return view('Backend.InpostPickup.show', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => InpostPickup::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco '.InpostPickup::NOME_PLURALE],
        ]);
    }

    public function destroy($id)
    {
        $record = InpostPickup::find($id);
        abort_if(! $record, 404, 'Questo ritiro InPost non esiste');
        $record->delete();

        return [
            'success' => true,
            'redirect' => action([self::class, 'index']),
        ];
    }

    public function cutoffTime(Request $request)
    {
        $service = new InpostService;
        $result = $service->pickupCutoffTime(array_filter([
            'postCode' => $request->input('post_code') ?: $request->input('postCode'),
            'countryCode' => $request->input('country_code') ?: $request->input('countryCode', 'IT'),
        ]));

        return ['success' => true, 'data' => $result];
    }

    public function sync($id)
    {
        $record = InpostPickup::find($id);
        abort_if(! $record, 404, 'Questo ritiro InPost non esiste');
        abort_if(! $record->remote_id, 404, 'ID remoto InPost non disponibile');

        $service = new InpostService;
        $response = $service->getPickup($record->remote_id, $record);
        $this->applicaRispostaRemota($record, $response, 'pickupRead');
        $record->save();

        return redirect()->back()->with('success', 'Ritiro InPost sincronizzato.');
    }

    public function cancel($id)
    {
        $record = InpostPickup::find($id);
        abort_if(! $record, 404, 'Questo ritiro InPost non esiste');
        abort_if(! $record->remote_id, 404, 'ID remoto InPost non disponibile');

        $service = new InpostService;
        $response = $service->cancelPickup($record);
        $this->applicaRispostaRemota($record, $response, 'pickupCancel');
        $record->status = data_get($response, 'status') ?: (data_get($response, 'error') ? 'ERROR' : 'CANCELLED');
        $record->save();

        return redirect()->back()->with('success', 'Richiesta annullamento ritiro inviata a InPost.');
    }

    protected function applicaFiltri(Request $request)
    {
        $qb = InpostPickup::query()->latest()->with('agente:id,alias');
        $term = trim((string) $request->input('cerca'));

        if ($term !== '') {
            $qb->where(function ($q) use ($term) {
                $q->where('city', 'like', "%{$term}%")
                    ->orWhere('contact_name', 'like', "%{$term}%");
            });
        }

        return $qb;
    }

    protected function salvaDati(InpostPickup $record, Request $request): void
    {
        DB::transaction(function () use ($record, $request) {
            if (! $record->exists) {
                $record->agente_id = $request->input('agente_id') ?: Auth::id();
            }

            $record->contact_name = $request->input('contact_name');
            $record->contact_email = $request->input('contact_email');
            $record->contact_phone = $request->input('contact_phone');
            $record->street = $request->input('street');
            $record->building_number = $request->input('building_number');
            $record->post_code = $request->input('post_code');
            $record->city = $request->input('city');
            $record->country_code = $request->input('country_code', 'IT');
            $record->pickup_date = $request->input('pickup_date');
            $record->parcel_count = (int) ($request->input('parcel_count') ?: 1);
            $record->note = $request->input('note');
            $record->customer_reference = $request->input('customer_reference');

            $record->save();
        });
    }

    protected function creaPickupRemoto(InpostPickup $record): void
    {
        $service = new InpostService;
        $record->request_payload = $service->buildPickupPayload($record);
        $response = $service->createPickup($record);
        $this->applicaRispostaRemota($record, $response, 'pickupCreate');
        $record->save();
    }

    protected function applicaRispostaRemota(InpostPickup $record, array $response, string $key): void
    {
        $current = $record->response ?? [];
        $current[$key] = $response;
        $record->response = array_merge($current, $key === 'pickupCreate' ? $response : []);
        $record->remote_id = data_get($response, 'id')
            ?: data_get($response, 'pickupId')
            ?: data_get($response, 'orderId')
            ?: data_get($response, 'order.id')
            ?: $record->remote_id;
        $record->status = data_get($response, 'status')
            ?: data_get($response, 'order.status')
            ?: (data_get($response, 'error') ? 'ERROR' : ($record->status ?: 'CREATED'));
    }

    protected function validatePickupWindow(Request $request): void
    {
        $date = $request->input('pickup_date');
        if (! $date) {
            return;
        }

        $pickupDate = Carbon::parse($date);
        if ($pickupDate->isPast() && ! $pickupDate->isToday()) {
            throw ValidationException::withMessages(['pickup_date' => 'La data di ritiro non può essere nel passato.']);
        }
    }

    protected function rules(): array
    {
        return [
            'agente_id' => ['required'],
            'contact_name' => ['required', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['required', 'max:32'],
            'street' => ['required', 'max:200'],
            'building_number' => ['nullable', 'max:20'],
            'post_code' => ['required', 'max:20'],
            'city' => ['required', 'max:100'],
            'country_code' => ['required', 'size:2'],
            'pickup_date' => ['required', 'date'],
            'parcel_count' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'max:500'],
            'customer_reference' => ['nullable', 'max:100'],
        ];
    }

    protected function backToIndex()
    {
        return redirect()->action([self::class, 'index']);
    }
}
