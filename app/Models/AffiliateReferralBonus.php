<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateReferralBonus extends Model
{
    protected $table = 'affiliate_referral_bonuses';

    public const STATO_PENDING = 'pending';

    public const STATO_CREDITED = 'credited';

    public const STATO_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'importo' => 'float',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
