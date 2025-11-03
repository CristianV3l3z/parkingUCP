<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\vigilante; // tu modelo vigilante (tal como lo tienes)
use App\Models\tiquete;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class vehiculo extends Model {

    use HasFactory;

    protected $table = 'vehiculo';
    protected $primaryKey = 'id_vehiculo';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_usuario','placa','tipo_vehiculo','marca','id_tarifa','descripcion','activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActive($query) {
        return $query->where('activo', true);
    }

    public $timestamps = true;

    // Relaciones...
    public function usuario()
    {
        return $this->belongsTo(usuario::class, 'id_usuario', 'id_usuario');
    }

    public function tarifa()
    {
        return $this->belongsTo(tarifa::class, 'id_tarifa', 'id_tarifa');
    }

    /**
     * Relación: tiquetes asociados a este vehículo.
     */
    public function tiquetes()
    {
        return $this->hasMany(tiquete::class, 'id_vehiculo', 'id_vehiculo');
    }

    /**
     * Crear un tiquete automáticamente cuando se crea un vehículo.
     * Esto evita tocar el vehiculoController@store.
     */
    protected static function booted()
    {
        static::created(function ($vehiculo) {
            try {
                // Determinar id del vigilante: Auth o session('vigilante')
                $idVigilante = null;
                if (Auth::guard('vigilante')->check()) {
                    $user = Auth::guard('vigilante')->user();
                    $idVigilante = $user->id_vigilante ?? $user->id ?? null;
                } elseif (Auth::check()) {
                    $user = Auth::user();
                    $idVigilante = $user->id_vigilante ?? $user->id ?? null;
                }
                if (!$idVigilante && session()->has('vigilante')) {
                    $v = session('vigilante');
                    $idVigilante = $v['id_vigilante'] ?? $v['id'] ?? $v['id_usuario'] ?? null;
                }

                // Obtener nombre del vigilante (si hay id) - usamos el modelo importado 'vigilante'
                $vigilanteNombre = null;
                if ($idVigilante) {
                    $v = vigilante::find($idVigilante);
                    if ($v) {
                        if (!empty($v->nombre_completo)) {
                            $vigilanteNombre = $v->nombre_completo;
                        } else {
                            $parts = [];
                            if (!empty($v->nombre)) $parts[] = $v->nombre;
                            if (!empty($v->apellido)) $parts[] = $v->apellido;
                            $vigilanteNombre = count($parts) ? implode(' ', $parts) : ($v->nombre ?? null);
                        }
                    }
                }

                $idTarifa = $vehiculo->id_tarifa ?? null;

                $tiqueteData = [
                    'codigo_uuid' => (string) Str::uuid(),
                    'id_vehiculo' => $vehiculo->id_vehiculo,
                    'id_vigilante' => $idVigilante,
                    'id_tarifa' => $idTarifa,
                    'hora_entrada' => now(),
                    'hora_salida' => null,
                    'estado' => 1,
                    'observaciones' => $vehiculo->descripcion ?? 'Creado automáticamente al registrar vehículo'
                ];

                // añadir nombre si se consiguió
                if ($vigilanteNombre) {
                    $tiqueteData['vigilante_nombre'] = $vigilanteNombre;
                }

                // crea el tiquete
                tiquete::create($tiqueteData);

            } catch (\Throwable $e) {
                // No interrumpir la creación del vehículo si algo falla
                Log::error('Error auto-creando tiquete desde vehiculo::created - ' . $e->getMessage(), [
                    'vehiculo_id' => $vehiculo->id_vehiculo ?? null,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });
    }

    // Relación con vigilante cuando id_usuario apunta a la tabla vigilante
    public function vigilante()
    {
        return $this->belongsTo(vigilante::class, 'id_usuario', 'id_vigilante');
    }

    /**
     * Accessor: valor para mostrar del vigilante asociado a este vehículo.
     * Prioriza:
     *  1) relación vigilante (nombre o nombre_completo)
     *  2) vigilante_nombre del último tiquete
     *  3) null si no hay nada
     */
    public function getVigilanteDisplayAttribute()
    {
        // 1) intentar relación vigilante
        if ($this->relationLoaded('vigilante') && $this->vigilante) {
            // si el modelo vigilante tiene nombre_completo o display_name:
            if (!empty($this->vigilante->nombre_completo)) {
                return $this->vigilante->nombre_completo;
            }
            if (!empty($this->vigilante->display_name)) {
                return $this->vigilante->display_name;
            }
            if (!empty($this->vigilante->nombre)) {
                return $this->vigilante->nombre;
            }
        } else {
            // intentar cargar la relación si no está cargada (silencioso)
            try {
                $v = $this->vigilante()->first();
                if ($v) {
                    if (!empty($v->nombre_completo)) return $v->nombre_completo;
                    if (!empty($v->display_name)) return $v->display_name;
                    if (!empty($v->nombre)) return $v->nombre;
                }
            } catch (\Throwable $e) {
                // ignorar
            }
        }

        // 2) intentar obtener del último tiquete
        try {
            $last = $this->tiquetes()->orderByDesc('id_tiquete')->first();
            if ($last && !empty($last->vigilante_nombre)) {
                return $last->vigilante_nombre;
            }
        } catch (\Throwable $e) {
            // ignorar
        }

        return null;
    }

}
