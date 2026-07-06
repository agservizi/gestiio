<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SpedizioneBrt extends Model
{
    use HasFactory;

    protected $table = 'brt_spedizioni';

    public const NOME_SINGOLARE = 'spedizione brt';

    public const NOME_PLURALE = 'spedizioni brt';

    protected $casts = [
        'response' => 'array',
        'labels' => 'array',
        'dati_colli' => 'array',
        'altri_dati' => 'json',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            /** @var User|null $authUser */
            $authUser = Auth::user();
            if (! $authUser) {
                return;
            }

            if ($authUser->hasPermissionTo('agente')) {
                $builder->where('agente_id', $authUser->id);
            } elseif ($authUser->hasPermissionTo('supervisore')) {
                $builder->where('agente_id', $authUser->id);
            }
        });

    }

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function agente()
    {
        return $this->belongsTo(User::class, 'agente_id');
    }

    public function chiamate(): MorphMany
    {
        return $this->morphMany(ChiamataApi::class, 'service');
    }

    public function caricatoDa()
    {
        return $this->belongsTo(User::class, 'caricato_da_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | PER BLADE
    |--------------------------------------------------------------------------
    */

    public function esito()
    {
        if ($this->response['createResponse'] ?? false) {
            $esito = $this->response['createResponse']['executionMessage'];
            $severity = $esito['severity'];
            switch ($severity) {
                case 'ERROR':
                    return "<span class='badge badge-danger'>{$esito['code']}: {$esito['message']}</span>";

                case 'WARNING':
                    return "<span class='badge badge-warning'>{$esito['code']}: {$esito['message']}</span>";
            }
        }

    }

    public function esitoBall()
    {
        if ($this->esito == 'ANNULLATA') {
            return "<span class='bullet bullet-dot bg-info h-15px w-15px'></span>";
        }

        if ($this->response['createResponse'] ?? false) {
            $esito = $this->response['createResponse']['executionMessage'];
            $severity = $esito['severity'];

            $classe = match ($severity) {
                'ERROR' => 'danger',
                'WARNING' => 'warning',
                default => 'success',
            };

            return "<span class='bullet bullet-dot bg-$classe h-15px w-15px'></span>";
        }

    }

    public function tracking()
    {
        if (! ($this->response['createResponse'] ?? false) || ! ($this->response['createResponse']['labels']['label'] ?? false)) {
            return null;
        }

        $trackingResponse = $this->response['trackingResponse'] ?? [];
        $parcel = [];
        foreach ($this->response['createResponse']['labels']['label'] as $label) {
            $trackingByParcelId = $label['trackingByParcelID'] ?? null;
            $parcelId = $label['parcelID'] ?? null;
            if (! $trackingByParcelId) {
                continue;
            }

            $status = $parcelId ? $this->trackingStatusFromApi($trackingResponse[$parcelId] ?? []) : null;
            $text = $trackingByParcelId;
            if ($status) {
                $text .= ' - '.e($status);
            }

            $parcel[] = "<a href='https://www.mybrt.it/it/mybrt/my-parcels/incoming?parcelNumber={$trackingByParcelId}' target='_blank'>{$text}</a>";
        }

        return implode('<br>', $parcel);
    }

    public function trackingStatus(): ?string
    {
        $trackingResponse = $this->response['trackingResponse'] ?? [];
        if (! is_array($trackingResponse) || ! count($trackingResponse)) {
            return null;
        }

        foreach ($trackingResponse as $apiResponse) {
            if (! is_array($apiResponse)) {
                continue;
            }

            $status = $this->trackingStatusFromApi($apiResponse);
            if ($status) {
                return $status;
            }
        }

        return null;
    }

    public function trackingStatusBadge(): string
    {
        $status = $this->trackingStatus();
        if (! $status) {
            return "<span class='badge badge-light'>-</span>";
        }

        $normalized = mb_strtolower($status);
        $class = 'badge-light-primary';

        if (str_contains($normalized, 'consegn') || str_contains($normalized, 'delivered')) {
            $class = 'badge-light-success';
        } elseif (str_contains($normalized, 'giacenza') || str_contains($normalized, 'anomalia') || str_contains($normalized, 'failed')) {
            $class = 'badge-light-danger';
        } elseif (str_contains($normalized, 'transit') || str_contains($normalized, 'trasfer')) {
            $class = 'badge-light-warning';
        }

        return "<span class='badge {$class}'>".e($status).'</span>';
    }

    public function trackingUpdatedAtLabel(): ?string
    {
        $value = data_get($this->response, 'trackingUpdatedAt');
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function trackingTimelineHtml(): ?string
    {
        $trackingResponse = $this->response['trackingResponse'] ?? [];
        if (! is_array($trackingResponse) || ! count($trackingResponse)) {
            return null;
        }

        foreach ($trackingResponse as $apiResponse) {
            if (! is_array($apiResponse)) {
                continue;
            }

            $events = $this->extractTrackingEvents($apiResponse);
            if (! count($events)) {
                continue;
            }

            $items = [];
            foreach ($events as $event) {
                $date = $event['date'] ?: '-';
                $description = $event['description'] ?: 'Evento tracking';
                $items[] = "<li class='list-group-item d-flex justify-content-between align-items-start'><span>".e($description)."</span><span class='text-muted small ms-3'>".e($date).'</span></li>';
            }

            if (count($items)) {
                return "<ul class='list-group'>".implode('', $items).'</ul>';
            }
        }

        return null;
    }

    protected function trackingStatusFromApi(array $apiResponse): ?string
    {
        $possiblePaths = [
            'parcelIDResult.spedizione.statoSpedizione',
            'ttParcelIdResponse.spedizione.statoSpedizione',
            'spedizione.statoSpedizione',
            'status.description',
            'status',
        ];

        foreach ($possiblePaths as $path) {
            $value = data_get($apiResponse, $path);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        $possibleEventPaths = [
            'parcelIDResult.spedizione.eventi.evento',
            'ttParcelIdResponse.spedizione.eventi.evento',
            'spedizione.eventi.evento',
        ];

        foreach ($possibleEventPaths as $path) {
            $event = data_get($apiResponse, $path);
            if (! is_array($event)) {
                continue;
            }

            $first = isset($event[0]) ? $event[0] : $event;
            if (! is_array($first)) {
                continue;
            }

            foreach (['descrizione', 'description', 'statusDescription', 'testo'] as $key) {
                $value = $first[$key] ?? null;
                if (is_string($value) && trim($value) !== '') {
                    return $value;
                }
            }
        }

        return null;

    }

    protected function extractTrackingEvents(array $apiResponse): array
    {
        $possibleEventPaths = [
            'parcelIDResult.spedizione.eventi.evento',
            'ttParcelIdResponse.spedizione.eventi.evento',
            'spedizione.eventi.evento',
        ];

        foreach ($possibleEventPaths as $path) {
            $events = data_get($apiResponse, $path);
            if (! is_array($events)) {
                continue;
            }

            $events = isset($events[0]) ? $events : [$events];
            $mapped = [];
            foreach ($events as $event) {
                if (! is_array($event)) {
                    continue;
                }

                $description = $event['descrizione'] ?? $event['description'] ?? $event['statusDescription'] ?? $event['testo'] ?? null;
                $date = $event['data'] ?? $event['date'] ?? $event['dataEvento'] ?? null;
                $time = $event['ora'] ?? $event['time'] ?? $event['oraEvento'] ?? null;

                if ($date && $time) {
                    $date = trim($date.' '.$time);
                }

                $mapped[] = [
                    'description' => is_string($description) ? trim($description) : null,
                    'date' => is_string($date) ? trim($date) : null,
                ];
            }

            if (count($mapped)) {
                return $mapped;
            }
        }

        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}
