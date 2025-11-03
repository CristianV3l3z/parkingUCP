<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\vehiculo;
use App\Models\vigilante;

class tiquete extends Model
{
    use HasFactory;

    protected $table = 'tiquete';
    protected $primaryKey = 'id_tiquete';
    public $timestamps = true;

    protected $fillable = [
    'codigo_uuid','id_vehiculo','id_vigilante','id_tarifa',
    'hora_entrada','hora_salida','estado','observaciones','activo',
    'vigilante_nombre'
    ];

    protected $casts = [
    'activo' => 'boolean',
    ];

    // scope
    public function scopeActive($query) {
        return $query->where('activo', true);
    }

    // Generar UUID automáticamente al crear un tiquete
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->codigo_uuid)) {
                $model->codigo_uuid = (string) Str::uuid();
            }
        });
    }

    // Relaciones
    public function vehiculo()
    {
        return $this->belongsTo(vehiculo::class, 'id_vehiculo', 'id_vehiculo');
    }

    public function vigilante()
    {
        return $this->belongsTo(vigilante::class, 'id_vigilante', 'id_vigilante');
    }

    public function tarifa()
    {
        return $this->belongsTo(tarifa::class, 'id_tarifa', 'id_tarifa');
    }

        // Convertir hora_entrada a zona horaria America/Bogota automáticamente
    public function getHoraEntradaAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->setTimezone('America/Bogota');
        }
        return null;
    }

    // Convertir hora_salida a zona horaria America/Bogota automáticamente
    public function getHoraSalidaAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->setTimezone('America/Bogota');
        }
        return null;
    }
}
