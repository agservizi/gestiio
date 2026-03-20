<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SpedizioneInpost extends Model
{
    use HasFactory;

    protected $table = 'inpost_spedizioni';

    public const NOME_SINGOLARE = 'spedizione InPost';
    public const NOME_PLURALE = 'spedizioni InPost';

    protected $casts = [
        'request_payload' => 'array',
        'response' => 'array',
        'labels' => 'array',
        'dati_colli' => 'array',
        'altri_dati' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            /** @var User|null $authUser */
            $authUser = Auth::user();
            if (!$authUser) {
                return;
            }

            if ($authUser->hasPermissionTo('agente') || $authUser->hasPermissionTo('supervisore')) {
                $builder->where('agente_id', $authUser->id);
            }
        });
    }

    public function agente()
    {
        return $this->hasOne(User::class, 'id', 'agente_id');
    }

    public function chiamate(): MorphMany
    {
        return $this->morphMany(ChiamataApi::class, 'service');
    }

    public function esitoBall(): string
    {
        $class = match (strtoupper((string) $this->esito)) {
            'ERROR', 'FAILED' => 'danger',
            'WARNING', 'PENDING' => 'warning',
            default => 'success',
        };

        return "<span class='bullet bullet-dot bg-{$class} h-15px w-15px'></span>";
    }

    public function tracking(): ?string
    {
        if (!$this->tracking_number) {
            return null;
        }

        $url = app(\App\Http\Services\InpostService::class)->trackingUrl($this->tracking_number);

        return "<a href='{$url}' target='_blank'>" . e($this->tracking_number) . '</a>';
    }

    public function trackingStatus(): ?string
    {
        $response = $this->response ?? [];
        $paths = [
            'trackingResponse.status',
            'trackingResponse.currentStatus',
            'trackingResponse.shipment.status',
            'trackingResponse.shipment.currentStatus',
            'trackingResponse.events.0.eventCode',
        ];

        foreach ($paths as $path) {
            $value = data_get($response, $path);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    public function trackingStatusBadge(): string
    {
        $status = $this->trackingStatus();
        if (!$status) {
            return "<span class='badge badge-light'>-</span>";
        }

        $normalized = mb_strtolower($status);
        $class = 'badge-light-primary';

        if (str_contains($normalized, 'delivered') || str_contains($normalized, 'eol.1001')) {
            $class = 'badge-light-success';
        } elseif (str_contains($normalized, 'cancel') || str_contains($normalized, 'damaged') || str_contains($normalized, 'missing')) {
            $class = 'badge-light-danger';
        } elseif (str_contains($normalized, 'cre.') || str_contains($normalized, 'ready')) {
            $class = 'badge-light-warning';
        }

        return "<span class='badge {$class}'>" . e($status) . '</span>';
    }

    public function trackingUpdatedAtLabel(): ?string
    {
        $value = data_get($this->response, 'trackingUpdatedAt');
        if (!$value) {
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
        $events = data_get($this->response, 'trackingResponse.events', []);
        if (!is_array($events) || !count($events)) {
            return null;
        }

        $items = collect($events)->map(function ($event) {
            $label = data_get($event, 'description') ?: data_get($event, 'eventCode') ?: 'Evento';
            $date = data_get($event, 'timestamp') ?: data_get($event, 'date');

            return "<li class='list-group-item d-flex justify-content-between align-items-start'><span>" . e($label) . "</span><span class='text-muted small ms-3'>" . e((string) $date) . '</span></li>';
        })->all();

        return count($items) ? "<ul class='list-group'>" . implode('', $items) . '</ul>' : null;
    }
}
