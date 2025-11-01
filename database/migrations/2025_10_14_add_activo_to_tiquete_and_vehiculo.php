<?php

// database/migrations/2025_10_14_add_activo_to_tiquete_and_vehiculo.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActivoToTiqueteAndVehiculo extends Migration
{
    public function up()
    {
        Schema::table('tiquete', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('estado');
        });

        Schema::table('vehiculo', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('descripcion');
        });
    }

    public function down()
    {
        Schema::table('tiquete', function (Blueprint $table) {
            $table->dropColumn('activo');
        });

        Schema::table('vehiculo', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
}
