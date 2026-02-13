<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('brt_listino', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->decimal('da_peso', 4, 1);
            $table->decimal('a_peso', 4, 1)->nullable();
            $table->decimal('home_delivery')->nullable();
            $table->decimal('brt_fermopoint')->nullable();
            $table->boolean('al_kg')->default(0);
        });

        \DB::table('brt_listino')->insert(array(
            0 =>
                array(
                    'id' => 1,
                    'created_at' => '2023-09-27 21:42:33',
                    'updated_at' => '2023-09-29 18:40:14',
                    'da_peso' => '0.0',
                    'a_peso' => '1.0',
                    'home_delivery' => '4.60',
                    'brt_fermopoint' => '4.00',
                    'al_kg' => 0,
                ),
            1 =>
                array(
                    'id' => 2,
                    'created_at' => '2023-09-27 21:43:23',
                    'updated_at' => '2023-09-29 18:36:10',
                    'da_peso' => '1.1',
                    'a_peso' => '3.0',
                    'home_delivery' => '4.90',
                    'brt_fermopoint' => '4.20',
                    'al_kg' => 0,
                ),
            2 =>
                array(
                    'id' => 3,
                    'created_at' => '2023-09-27 21:43:53',
                    'updated_at' => '2023-09-29 18:37:47',
                    'da_peso' => '3.1',
                    'a_peso' => '5.0',
                    'home_delivery' => '5.90',
                    'brt_fermopoint' => '4.90',
                    'al_kg' => 0,
                ),
            3 =>
                array(
                    'id' => 4,
                    'created_at' => '2023-09-27 21:44:13',
                    'updated_at' => '2023-09-29 18:40:28',
                    'da_peso' => '5.1',
                    'a_peso' => '10.0',
                    'home_delivery' => '6.90',
                    'brt_fermopoint' => '5.90',
                    'al_kg' => 0,
                ),
            4 =>
                array(
                    'id' => 5,
                    'created_at' => '2023-09-27 21:44:36',
                    'updated_at' => '2023-09-29 18:40:36',
                    'da_peso' => '10.1',
                    'a_peso' => '20.0',
                    'home_delivery' => '9.90',
                    'brt_fermopoint' => '8.90',
                    'al_kg' => 0,
                ),
            5 =>
                array(
                    'id' => 6,
                    'created_at' => '2023-09-27 21:45:08',
                    'updated_at' => '2023-09-29 18:40:43',
                    'da_peso' => '20.1',
                    'a_peso' => '30.0',
                    'home_delivery' => '13.90',
                    'brt_fermopoint' => NULL,
                    'al_kg' => 0,
                ),
            6 =>
                array(
                    'id' => 7,
                    'created_at' => '2023-09-27 21:45:23',
                    'updated_at' => '2023-09-27 21:45:23',
                    'da_peso' => '30.0',
                    'a_peso' => NULL,
                    'home_delivery' => '0.25',
                    'brt_fermopoint' => NULL,
                    'al_kg' => 1,
                ),
        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brt_listino');
    }
};
