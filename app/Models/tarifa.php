<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tarifa extends Model
{
    use HasFactory;

    protected $table = 'tarifa';
    protected $primaryKey = 'id_tarifa';
    public $timestamps = true;

    protected $fillable = [
        'tipo_vehiculo',
        'valor',
        'descripcion',
        'activo',
    ];

    // Relación con Vehiculo
    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'id_tarifa', 'id_tarifa');
    }
}