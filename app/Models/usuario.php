<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    
    protected $table = 'usuario'; // nombre exacto de la tabla

    protected $primaryKey = 'id_usuario'; // clave primaria personalizada

    public $timestamps = true; // usar created_at y updated_at

    protected $fillable = [
        'nombre',
        'correo',
        'contrasena_hash',
        'telefono',
    ];

    protected $hidden = [
        'contrasena_hash',
    ];

    // Para que Laravel use 'contrasena_hash' en lugar de 'password'
    // Si la columna password no se llama "password", Laravel necesita saber dónde está:
    public function getAuthPassword()
    {
        return $this->contrasena_hash;
    }
}
