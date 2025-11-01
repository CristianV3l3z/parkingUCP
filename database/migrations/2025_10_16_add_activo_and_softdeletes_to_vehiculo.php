<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActivoAndSoftDeletesToVehiculo extends Migration
{
    public function up()
    {
        Schema::table('vehiculo', function (Blueprint $table) {
            // añade columna activo si no existe
            if (! Schema::hasColumn('vehiculo', 'activo')) {
                $table->boolean('activo')->default(true)->after('descripcion');
            }
            // añade deleted_at para soft deletes
            if (! Schema::hasColumn('vehiculo', 'deleted_at')) {
                $table->softDeletes(); // crea deleted_at nullable
            }
        });
    }

    public function down()
    {
        Schema::table('vehiculo', function (Blueprint $table) {
            if (Schema::hasColumn('vehiculo', 'activo')) {
                $table->dropColumn('activo');
            }
            if (Schema::hasColumn('vehiculo', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
}
