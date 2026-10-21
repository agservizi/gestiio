<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'referred_by_user_id')) {
                $table->unsignedBigInteger('referred_by_user_id')->nullable()->after('id');
                $table->index('referred_by_user_id');
            }
            if (! Schema::hasColumn('users', 'affiliate_invite_code')) {
                $table->string('affiliate_invite_code', 32)->nullable()->unique()->after('referred_by_user_id');
            }
        });

        if (! Schema::hasTable('affiliate_referral_bonuses')) {
            Schema::create('affiliate_referral_bonuses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('referrer_user_id');
                $table->unsignedBigInteger('referred_user_id');
                $table->decimal('importo', 10, 2)->default(0);
                $table->string('stato', 32)->default('pending'); // pending|credited|cancelled
                $table->string('note', 255)->nullable();
                $table->timestamps();

                $table->index('referrer_user_id', 'arb_referrer_idx');
                $table->index('referred_user_id', 'arb_referred_idx');
                $table->unique(['referrer_user_id', 'referred_user_id'], 'arb_referrer_referred_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_referral_bonuses');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'affiliate_invite_code')) {
                $table->dropColumn('affiliate_invite_code');
            }
            if (Schema::hasColumn('users', 'referred_by_user_id')) {
                $table->dropColumn('referred_by_user_id');
            }
        });
    }
};
