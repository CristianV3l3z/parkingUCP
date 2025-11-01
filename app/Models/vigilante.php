<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vigilante extends Model
{
    use HasFactory;

    // Nombre de la tabla
    protected $table = 'vigilante';

    // Nombre de la clave primaria
    protected $primaryKey = 'id_vigilante';

    // Si la clave primaria es autoincremental
    public $incrementing = true;

    // Tipo de clave primaria
    protected $keyType = 'int';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
        'correo',
        'contrasena_hash',
    ];

    // Laravel ya gestiona created_at y updated_at
    public $timestamps = true;

    /**
     * Relación: un vigilante tiene muchos tiquetes.
     * foreignKey = id_vigilante en la tabla tiquete
     * localKey = id_vigilante en esta tabla
     */
    public function tiquetes()
    {
        return $this->hasMany(tiquete::class, 'id_vigilante', 'id_vigilante');
    }

    /**
     * Relación: (opcional) un vigilante puede estar referido en vehiculo.id_usuario
     * Algunos lugares de tu app asocian vehiculo.id_usuario al vigilante,
     * por eso exponemos esta relación inversa.
     */
    public function vehiculos()
    {
        return $this->hasMany(vehiculo::class, 'id_usuario', 'id_vigilante');
    }

    /**
     * Accessor: nombre para mostrar del vigilante.
     * Si en el futuro agregas 'apellido' o 'nombre_completo', puedes ajustar aquí.
     */
    public function getDisplayNameAttribute()
    {
        // si tu tabla más adelante tiene nombre_completo o apellido, puedes usarlos
        if (!empty($this->nombre_completo)) {
            return $this->nombre_completo;
        }

        // aquí solo hay 'nombre' según tu esquema
        return $this->nombre ?? null;
    }
}
