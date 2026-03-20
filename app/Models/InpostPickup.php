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
}
