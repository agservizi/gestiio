<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class InpostReturn extends Model
{
    use HasFactory;

    protected $table = 'inpost_returns';

    public const NOME_SINGOLARE = 'reso InPost';

    public const NOME_PLURALE = 'resi InPost';

    protected $casts = [
        'request_payload' => 'array',
        'response' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            /** @var User|null $authUser */
            $authUser = Auth::user();
            if (! $authUser) {
                return;
            }

            if ($authUser->hasPermissionTo('agente') || $authUser->hasPermissionTo('supervisore')) {
                $builder->where('agente_id', $authUser->id);
            }
        });
    }

    public function agente()
    {
        return $this->belongsTo(User::class, 'agente_id');
    }

    public function chiamate(): MorphMany
    {
        return $this->morphMany(ChiamataApi::class, 'service');
    }

    public function statusBadge(): string
    {
        $status = strtoupper((string) $this->status);

        $class = match (true) {
            in_array($status, ['COLLECTED', 'DELIVERED', 'EOL.1001']) => 'badge-light-success',
            in_array($status, ['CANCELLED', 'ERROR', 'FAILED']) => 'badge-light-danger',
            in_array($status, ['CREATED', 'CONFIRMED', 'PENDING', 'CRE.1001']) => 'badge-light-warning',
            default => 'badge-light-primary',
        };

        $label = $this->status ?: '-';

        return "<span class='badge {$class}'>".e($label).'</span>';
    }

    public function esitoBall(): string
    {
        $status = strtoupper((string) $this->status);

        $class = match (true) {
            in_array($status, ['ERROR', 'FAILED', 'CANCELLED']) => 'danger',
            in_array($status, ['PENDING', 'CREATED']) => 'warning',
            default => 'success',
        };

        return "<span class='bullet bullet-dot bg-{$class} h-15px w-15px'></span>";
    }

    public function qrCodeUrl(): ?string
    {
        return data_get($this->response, 'qrCodeUrl')
            ?: data_get($this->response, 'qr_code_url')
            ?: data_get($this->response, 'qr_code.url')
            ?: null;
    }

    public function trackingNumber(): ?string
    {
        return data_get($this->response, 'trackingNumber')
            ?: data_get($this->response, 'tracking_number')
            ?: null;
    }
}
