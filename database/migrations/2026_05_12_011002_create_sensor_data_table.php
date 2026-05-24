<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sensor_data', function (Blueprint $table) {
            $table->id();
            $table->decimal('v_in', 8, 3);
            $table->decimal('v_out', 8, 3);
            $table->decimal('a_in', 8, 1);
            $table->decimal('a_out', 8, 1);
            $table->decimal('suhu', 5, 3);
            $table->decimal('soc', 5, 2);
            $table->string('s_relay_charge', 10); // <-- INI WADAH BARUNYA
            $table->string('s_charge', 20);
            $table->string('s_fan', 10);
            $table->string('s_lamp1', 10);
            $table->string('s_lamp2', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_data');
    }
};
