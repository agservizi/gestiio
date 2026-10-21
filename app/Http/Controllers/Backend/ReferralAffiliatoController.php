<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\AffiliateReferralService;
use App\Models\AffiliateReferralBonus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ReferralAffiliatoController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        abort_unless($user && ($user->can('agente') || $user->can('admin')), 403);

        $service = app(AffiliateReferralService::class);
        $code = $service->ensureInviteCode($user);
        $url = $service->inviteUrl($user);

        $bonuses = collect();
        if (Schema::hasTable('affiliate_referral_bonuses')) {
            $bonuses = AffiliateReferralBonus::query()
                ->with('referred:id,nome,cognome,email')
                ->where('referrer_user_id', $user->id)
                ->orderByDesc('id')
                ->limit(50)
                ->get();
        }

        return view('Backend.ReferralAffiliato.index', [
            'titoloPagina' => 'Invita affiliato',
            'inviteCode' => $code,
            'inviteUrl' => $url,
            'bonuses' => $bonuses,
            'bonusDefault' => AffiliateReferralService::BONUS_DEFAULT,
            'controller' => self::class,
        ]);
    }
}
