<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratti_energia_magic_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratto_energia_id')->constrained('contratti_energia')->cascadeOnDelete();
            $table->string('email');
            $table->string('purpose', 64)->default('richiesta_documenti');
            $table->string('token_hash', 128)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('used_ip', 45)->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['contratto_energia_id', 'purpose']);
            $table->index(['purpose', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratti_energia_magic_links');
    }
};
