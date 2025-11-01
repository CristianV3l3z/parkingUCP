<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVigilanteNombreToTiquete extends Migration
{
    public function up()
    {
        Schema::table('tiquete', function (Blueprint $table) {
            // varchar suficiente; ajusta longitud si quieres más
            $table->string('vigilante_nombre', 255)->nullable()->after('id_vigilante')->index();
        });
    }

    public function down()
    {
        Schema::table('tiquete', function (Blueprint $table) {
            $table->dropColumn('vigilante_nombre');
        });
    }
}
