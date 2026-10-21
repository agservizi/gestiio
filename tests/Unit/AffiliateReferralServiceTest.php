<?php

namespace Tests\Unit;

use App\Http\Services\AffiliateReferralService;
use App\Models\AffiliateReferralBonus;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AffiliateReferralServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('affiliate_referral_bonuses');
        Schema::dropIfExists('contratti');
        Schema::dropIfExists('contratti_energia');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_progress_counts_only_esito_ok(): void
    {
        $referred = $this->createUser(10);
        $this->insertTel($referred, 'ok');
        $this->insertTel($referred, 'ko');
        $this->insertEn($referred, 'ok');
        $this->insertEn($referred, 'ok');
        $this->insertEn($referred, 'bozza');

        $p = app(AffiliateReferralService::class)->progressForReferred($referred);

        $this->assertSame(1, $p['telefonia']);
        $this->assertSame(2, $p['energia']);
        $this->assertTrue($p['ok']);
    }

    public function test_below_threshold_does_not_create_pending(): void
    {
        $referrer = $this->createUser(1);
        $referred = $this->createUser(2, $referrer);
        $this->insertEn($referred, 'ok'); // 1 energia, 0 tel

        $bonus = app(AffiliateReferralService::class)->evaluateReferralBonusForUser($referred);

        $this->assertNull($bonus);
        $this->assertSame(0, AffiliateReferralBonus::query()->count());
    }

    public function test_threshold_ok_creates_pending(): void
    {
        $referrer = $this->createUser(1);
        $referred = $this->createUser(2, $referrer);
        $this->insertTel($referred, 'ok');
        $this->insertEn($referred, 'ok');
        $this->insertEn($referred, 'ok');

        $bonus = app(AffiliateReferralService::class)->evaluateReferralBonusForUser($referred);

        $this->assertNotNull($bonus);
        $this->assertSame(AffiliateReferralBonus::STATO_PENDING, $bonus->stato);
        $this->assertSame(1, (int) $bonus->referrer_user_id);
        $this->assertSame(2, (int) $bonus->referred_user_id);
    }

    public function test_loaded_but_not_ok_does_not_meet_threshold(): void
    {
        $referred = $this->createUser(10);
        $this->insertTel($referred, 'ko');
        $this->insertEn($referred, 'ko');
        $this->insertEn($referred, 'ko');

        $p = app(AffiliateReferralService::class)->progressForReferred($referred);

        $this->assertFalse($p['ok']);
        $this->assertSame(0, $p['telefonia']);
        $this->assertSame(0, $p['energia']);
    }

    public function test_credit_throws_when_below_threshold(): void
    {
        $referrer = $this->createUser(1);
        $referred = $this->createUser(2, $referrer);
        $bonus = AffiliateReferralBonus::query()->create([
            'referrer_user_id' => $referrer,
            'referred_user_id' => $referred,
            'importo' => 25,
            'stato' => AffiliateReferralBonus::STATO_PENDING,
            'note' => 'test',
        ]);

        $this->expectException(\RuntimeException::class);
        app(AffiliateReferralService::class)->credit($bonus);
    }

    public function test_credit_ok_when_threshold_met(): void
    {
        $referrer = $this->createUser(1);
        $referred = $this->createUser(2, $referrer);
        $this->insertTel($referred, 'ok');
        $this->insertEn($referred, 'ok');
        $this->insertEn($referred, 'ok');
        $bonus = AffiliateReferralBonus::query()->create([
            'referrer_user_id' => $referrer,
            'referred_user_id' => $referred,
            'importo' => 25,
            'stato' => AffiliateReferralBonus::STATO_PENDING,
            'note' => 'test',
        ]);

        $out = app(AffiliateReferralService::class)->credit($bonus);

        $this->assertSame(AffiliateReferralBonus::STATO_CREDITED, $out->stato);
    }

    public function test_cancel_pending_when_threshold_lost(): void
    {
        $referrer = $this->createUser(1);
        $referred = $this->createUser(2, $referrer);
        AffiliateReferralBonus::query()->create([
            'referrer_user_id' => $referrer,
            'referred_user_id' => $referred,
            'importo' => 25,
            'stato' => AffiliateReferralBonus::STATO_PENDING,
            'note' => 'premature',
        ]);

        $out = app(AffiliateReferralService::class)->evaluateReferralBonusForUser($referred);

        $this->assertNull($out);
        $this->assertSame(
            AffiliateReferralBonus::STATO_CANCELLED,
            AffiliateReferralBonus::query()->first()->stato
        );
    }

    protected function createUser(int $id, ?int $referredBy = null): int
    {
        DB::table('users')->insert([
            'id' => $id,
            'nome' => 'N'.$id,
            'cognome' => 'C'.$id,
            'email' => "u{$id}@test.local",
            'password' => bcrypt('x'),
            'referred_by_user_id' => $referredBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    protected function insertTel(int $agenteId, string $esito): void
    {
        DB::table('contratti')->insert([
            'agente_id' => $agenteId,
            'esito_finale' => $esito,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertEn(int $agenteId, string $esito): void
    {
        DB::table('contratti_energia')->insert([
            'agente_id' => $agenteId,
            'esito_finale' => $esito,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function setUpSchema(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('nome')->nullable();
            $t->string('cognome')->nullable();
            $t->string('email')->unique();
            $t->string('password');
            $t->unsignedBigInteger('referred_by_user_id')->nullable();
            $t->string('affiliate_invite_code')->nullable();
            $t->timestamps();
        });

        Schema::create('contratti', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agente_id')->nullable();
            $t->string('esito_finale')->nullable();
            $t->timestamps();
        });

        Schema::create('contratti_energia', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agente_id')->nullable();
            $t->string('esito_finale')->nullable();
            $t->timestamps();
        });

        Schema::create('affiliate_referral_bonuses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('referrer_user_id');
            $t->unsignedBigInteger('referred_user_id');
            $t->decimal('importo', 10, 2)->default(0);
            $t->string('stato')->default('pending');
            $t->text('note')->nullable();
            $t->timestamps();
        });
    }
}
