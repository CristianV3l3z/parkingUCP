<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pago extends Model
{
    use HasFactory;

    protected $table = 'pago';
    protected $primaryKey = 'id_pago';

    // Campos que se pueden asignar (corrigido: id_tiquete)
    protected $fillable = [
        'id_tiquete',
        'id_usuario',
        'monto',
        'metodo_pago',
        'fecha_pago',
        'estado_pago',
        'recibo',
        // campos opcionales que puedes agregar en la tabla para MP
        'mp_preference_id',
        'mp_init_point',
        'mp_payment_id',
        'mp_raw_response'
    ];

    public $timestamps = true;

    /**
     * Relación con el tiquete.
     */
    public function tiquete()
    {
        return $this->belongsTo(\App\Models\tiquete::class, 'id_tiquete', 'id_tiquete');
    }

    /**
     * Relación con el usuario (puede ser vigilante o usuario del sistema).
     */
    public function usuario()
    {
        // Ajusta el namespace del modelo Usuario si tu modelo se llama distinto
        return $this->belongsTo(\App\Models\usuario::class, 'id_usuario', 'id_usuario');
    }
}
