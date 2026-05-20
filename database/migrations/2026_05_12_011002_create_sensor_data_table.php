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
            $table->decimal('v_input', 8, 3);
            $table->decimal('v_sistem', 8, 3);
            $table->decimal('arus_masuk', 8, 1);
            $table->decimal('arus_keluar', 8, 1);
            $table->decimal('suhu', 5, 3);
            $table->decimal('soc', 5, 2);
            $table->string('status_charging', 20);
            $table->string('status_kipas', 10);
            $table->string('status_lampu1', 10);
            $table->string('status_lampu2', 10);
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
