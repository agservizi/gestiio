<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class InpostPickup extends Model
{
    use HasFactory;

    protected $table = 'inpost_pickups';

    public const NOME_SINGOLARE = 'ritiro InPost';
    public const NOME_PLURALE = 'ritiri InPost';

    protected $casts = [
        'pickup_date' => 'date',
        'request_payload' => 'array',
        'response' => 'array',
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

    public function statusBadge(): string
    {
        $status = strtoupper((string) $this->status);

        $class = match (true) {
            in_array($status, ['COLLECTED']) => 'badge-light-success',
            in_array($status, ['CANCELLED', 'INVALIDATED', 'INFEASIBLE', 'NONCOMPLIANT', 'EMPTY', 'UNLABELLED']) => 'badge-light-danger',
            in_array($status, ['CREATED', 'PENDING', 'RESCHEDULED']) => 'badge-light-warning',
            in_array($status, ['DUPLICATED']) => 'badge-light-info',
            default => 'badge-light-primary',
        };

        $label = $this->status ?: '-';

        return "<span class='badge {$class}'>" . e($label) . '</span>';
    }

    public function indirizzoCompleto(): string
    {
        return implode(', ', array_filter([
            trim($this->street . ' ' . ($this->building_number ?: '')),
            $this->post_code,
            $this->city,
        ]));
    }
}
