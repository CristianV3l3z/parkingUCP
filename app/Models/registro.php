<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class registro extends Model
{
    use HasFactory;

    protected $table = 'registro';
    protected $primaryKey = 'id_registro';
    public $timestamps = false;

    protected $fillable = [
        'id_tiquete',
        'id_vigilante',
        'accion',
        'detalle',
        'created_at'
    ];

    // Relación con Tiquete
    public function tiquete()
    {
        return $this->belongsTo(tiquete::class, 'id_tiquete', 'id_tiquete');
    }

    // Relación con Vigilante
    public function vigilante()
    {
        return $this->belongsTo(vigilante::class, 'id_vigilante', 'id_vigilante');
    }
}
