<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_version_releases')) {
            Schema::create('app_version_releases', function (Blueprint $table): void {
                $table->id();
                $table->string('version', 32);
                $table->string('title', 191)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_major')->default(false);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('released_at')->useCurrent();
                $table->timestamps();

                $table->unique('version');
                $table->index(['released_at']);
            });
        }

        $now = now();

        DB::table('app_version_releases')->updateOrInsert(
            ['version' => '4.0'],
            [
                'title' => 'Gestiio 4.0',
                'notes' => 'Baseline storica precedente al registro versioni strutturato.',
                'is_major' => true,
                'released_at' => $now->copy()->subDay(),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('app_version_releases')->updateOrInsert(
            ['version' => '5.0'],
            [
                'title' => 'Gestiio 5.0',
                'notes' => implode("\n", [
                    'Motore offerte QC (Confronta, honesty, listino Sheets A2A/Plenitude).',
                    'Indici mercato PUN/PSV via GME app API.',
                    'Loghi ufficiali gestori su Confronta (A2A, Plenitude, Enel, Fastweb, Sentra).',
                    'Registro versioni applicative in sistema.',
                ]),
                'is_major' => true,
                'released_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('app_version_releases');
    }
};
