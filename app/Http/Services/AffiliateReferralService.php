<?php

namespace App\Http\Services;

use App\Models\AffiliateReferralBonus;
use App\Models\ContrattoEnergia;
use App\Models\ContrattoTelefonia;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AffiliateReferralService
{
    public const BONUS_DEFAULT = 25.0;

    /** Contratti telefonia minimi con esito OK (liquidabili) del nuovo affiliato */
    public const MIN_TELEFONIA = 1;

    /** Contratti energia minimi con esito OK (liquidabili) del nuovo affiliato */
    public const MIN_ENERGIA = 2;

    public function ensureInviteCode(User $user): string
    {
        if (! Schema::hasColumn('users', 'affiliate_invite_code')) {
            return $user->codiceAgente();
        }

        if ($user->affiliate_invite_code) {
            return (string) $user->affiliate_invite_code;
        }

        $code = strtoupper(Str::random(8));
        while (User::query()->where('affiliate_invite_code', $code)->exists()) {
            $code = strtoupper(Str::random(8));
        }
        $user->affiliate_invite_code = $code;
        $user->save();

        return $code;
    }

    public function resolveReferrerFromInvite(?string $invite): ?User
    {
        $invite = strtoupper(trim((string) $invite));
        if ($invite === '') {
            return null;
        }

        if (Schema::hasColumn('users', 'affiliate_invite_code')) {
            $byInvite = User::query()->where('affiliate_invite_code', $invite)->first();
            if ($byInvite) {
                return $byInvite;
            }
        }

        $id = User::resolveIdFromCodiceAgente($invite);

        return $id ? User::find($id) : null;
    }

    /**
     * Alla registrazione: collega solo referred_by. Il bonus NON si aggancia ancora.
     */
    public function attributeReferral(User $newUser, ?string $inviteCode): ?AffiliateReferralBonus
    {
        $referrer = $this->resolveReferrerFromInvite($inviteCode);
        if (! $referrer || (int) $referrer->id === (int) $newUser->id) {
            return null;
        }

        if (Schema::hasColumn('users', 'referred_by_user_id') && ! $newUser->referred_by_user_id) {
            $newUser->referred_by_user_id = $referrer->id;
            $newUser->save();
        }

        // Non creare riga bonus: scatta solo con evaluateReferralBonusForUser()
        return $this->evaluateReferralBonusForUser($newUser);
    }

    /**
     * Conta contratti OK/liquidabili del nuovo affiliato (esito_finale = ok).
     * Non basta “caricato”: bozze/KO non contano. I già pagati restano validi per la soglia.
     *
     * @return array{telefonia:int,energia:int,ok:bool}
     */
    public function progressForReferred(int $referredUserId): array
    {
        $tel = (int) ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
            ->where('agente_id', $referredUserId)
            ->where('esito_finale', 'ok')
            ->count();
        $en = (int) ContrattoEnergia::withoutGlobalScope('filtroOperatore')
            ->where('agente_id', $referredUserId)
            ->where('esito_finale', 'ok')
            ->count();

        return [
            'telefonia' => $tel,
            'energia' => $en,
            'ok' => $tel >= self::MIN_TELEFONIA && $en >= self::MIN_ENERGIA,
        ];
    }

    public function meetsThreshold(int $referredUserId): bool
    {
        return $this->progressForReferred($referredUserId)['ok'];
    }

    /**
     * Crea (pending) il bonus solo se il referral ha ≥2 energia OK e ≥1 telefonia OK.
     */
    public function evaluateReferralBonusForUser(User|int $referred): ?AffiliateReferralBonus
    {
        if (! Schema::hasTable('affiliate_referral_bonuses')) {
            return null;
        }

        $referredUser = $referred instanceof User ? $referred : User::find($referred);
        if (! $referredUser) {
            return null;
        }

        $referrerId = (int) ($referredUser->referred_by_user_id ?? 0);
        if ($referrerId <= 0 || $referrerId === (int) $referredUser->id) {
            return null;
        }

        $esistente = AffiliateReferralBonus::query()
            ->where('referrer_user_id', $referrerId)
            ->where('referred_user_id', $referredUser->id)
            ->first();

        if ($esistente && in_array($esistente->stato, [
            AffiliateReferralBonus::STATO_CREDITED,
            AffiliateReferralBonus::STATO_CANCELLED,
        ], true)) {
            return $esistente;
        }

        $progress = $this->progressForReferred((int) $referredUser->id);
        if (! $progress['ok']) {
            // Soglia non raggiunta: non agganciare; se c'era un pending prematuro, annullalo
            if ($esistente && $esistente->stato === AffiliateReferralBonus::STATO_PENDING) {
                $esistente->stato = AffiliateReferralBonus::STATO_CANCELLED;
                $esistente->note = trim((string) $esistente->note.' | Annullato automatico: soglia non raggiunta (tel='
                    .$progress['telefonia'].'/'.self::MIN_TELEFONIA
                    .' en='.$progress['energia'].'/'.self::MIN_ENERGIA.')');
                $esistente->save();
            }

            return null;
        }

        $note = 'Bonus referral: soglia OK (tel '.$progress['telefonia'].'+ en '.$progress['energia'].') — non plafond';

        if ($esistente) {
            if ($esistente->stato === AffiliateReferralBonus::STATO_CANCELLED) {
                // Riattiva solo se era cancellato per soglia e ora la raggiunge
                $esistente->stato = AffiliateReferralBonus::STATO_PENDING;
                $esistente->importo = self::BONUS_DEFAULT;
                $esistente->note = $note;
                $esistente->save();
            }

            return $esistente;
        }

        return AffiliateReferralBonus::query()->create([
            'referrer_user_id' => $referrerId,
            'referred_user_id' => $referredUser->id,
            'importo' => self::BONUS_DEFAULT,
            'stato' => AffiliateReferralBonus::STATO_PENDING,
            'note' => $note,
        ]);
    }

    /**
     * Hook da chiamare dopo create/update contratto del nuovo affiliato.
     */
    public function onContrattoCaricato(?int $agenteId): void
    {
        if (! $agenteId) {
            return;
        }
        try {
            $user = User::find($agenteId);
            if (! $user || ! $user->referred_by_user_id) {
                return;
            }
            $this->evaluateReferralBonusForUser($user);
        } catch (\Throwable $e) {
            \Log::error('affiliate_referral.evaluate_failed', [
                'agente_id' => $agenteId,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }

    public function inviteUrl(User $user): string
    {
        $code = $this->ensureInviteCode($user);
        $base = rtrim((string) config(
            'services.landing.register_url',
            'https://landing-gestiio.agenziaplinio.it/registrati'
        ), '/');
        $sep = str_contains($base, '?') ? '&' : '?';

        return $base.$sep.'ref='.urlencode($code);
    }

    public function credit(AffiliateReferralBonus $bonus, ?User $admin = null): AffiliateReferralBonus
    {
        if ($bonus->stato === AffiliateReferralBonus::STATO_CREDITED) {
            return $bonus;
        }

        if (! $this->meetsThreshold((int) $bonus->referred_user_id)) {
            $p = $this->progressForReferred((int) $bonus->referred_user_id);
            throw new \RuntimeException(
                'Bonus non attivabile: il nuovo affiliato deve avere almeno '
                .self::MIN_ENERGIA.' contratti energia OK e '.self::MIN_TELEFONIA.' telefonia OK '
                .'(liquidabili; attuale: en='.$p['energia'].', tel='.$p['telefonia'].').'
            );
        }

        $bonus->stato = AffiliateReferralBonus::STATO_CREDITED;
        $note = trim((string) $bonus->note);
        $who = $admin ? ('admin #'.$admin->id) : 'admin';
        $bonus->note = trim($note.' | Accreditato '.$who.' '.now()->format('d/m/Y H:i'));
        $bonus->save();

        return $bonus;
    }

    public function cancel(AffiliateReferralBonus $bonus, ?User $admin = null, ?string $motivo = null): AffiliateReferralBonus
    {
        $bonus->stato = AffiliateReferralBonus::STATO_CANCELLED;
        $note = trim((string) $bonus->note);
        $who = $admin ? ('admin #'.$admin->id) : 'admin';
        $extra = $motivo ? (' — '.$motivo) : '';
        $bonus->note = trim($note.' | Annullato '.$who.' '.now()->format('d/m/Y H:i').$extra);
        $bonus->save();

        return $bonus;
    }
}
