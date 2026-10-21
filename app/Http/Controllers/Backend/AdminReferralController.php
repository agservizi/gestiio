<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\AffiliateReferralService;
use App\Models\AffiliateReferralBonus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminReferralController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Auth::user()?->can('admin'), 403);
        abort_unless(Schema::hasTable('affiliate_referral_bonuses'), 404);

        $stato = (string) $request->input('stato', 'pending');
        if (! in_array($stato, ['pending', 'credited', 'cancelled', 'all'], true)) {
            $stato = 'pending';
        }
        $q = AffiliateReferralBonus::query()
            ->with([
                'referrer:id,nome,cognome,email',
                'referred:id,nome,cognome,email,email_verified_at',
            ])
            ->orderByDesc('id');

        if ($stato !== 'all') {
            $q->where('stato', $stato);
        }

        return view('Backend.AdminReferral.index', [
            'titoloPagina' => 'Referral affiliati',
            'bonuses' => $q->paginate(40)->withQueryString(),
            'stato' => $stato,
            'counts' => [
                'pending' => AffiliateReferralBonus::query()->where('stato', 'pending')->count(),
                'credited' => AffiliateReferralBonus::query()->where('stato', 'credited')->count(),
                'cancelled' => AffiliateReferralBonus::query()->where('stato', 'cancelled')->count(),
            ],
            'controller' => self::class,
        ]);
    }

    public function credit(int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->can('admin'), 403);
        $bonus = AffiliateReferralBonus::query()->findOrFail($id);
        try {
            app(AffiliateReferralService::class)->credit($bonus, Auth::user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Bonus referral accreditato (fuori plafond).');
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->can('admin'), 403);
        $bonus = AffiliateReferralBonus::query()->findOrFail($id);
        $motivo = (string) $request->input('motivo', '');
        app(AffiliateReferralService::class)->cancel($bonus, Auth::user(), $motivo ?: null);

        return back()->with('status', 'Bonus referral annullato.');
    }
}
