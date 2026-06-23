<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inpost_listino', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('package_type', 20)->unique();
            $table->decimal('locker_point', 10, 2)->nullable();
            $table->decimal('home_delivery', 10, 2)->nullable();
        });

        DB::table('inpost_listino')->insert([
            ['package_type' => 'small', 'created_at' => now(), 'updated_at' => now()],
            ['package_type' => 'medium', 'created_at' => now(), 'updated_at' => now()],
            ['package_type' => 'large', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('inpost_listino');
    }
};
