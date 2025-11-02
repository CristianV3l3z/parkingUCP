<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'correo',
        'contrasena_hash',
        'telefono',
    ];

    protected $hidden = [
        'contrasena_hash',
    ];

    // para que Auth use la columna contrasena_hash como password
    public function getAuthPassword()
    {
        return $this->contrasena_hash;
    }

    // Mutator útil: si asignas $usuario->password = 'texto' automáticamente lo guarda en contrasena_hash
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['contrasena_hash'] = \Illuminate\Support\Facades\Hash::make($value);
        }
    }
}
