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
                // 1) Intentar resolver id de vigilante desde varios lugares:
                $idVigilante = null;
                $vigilanteNombre = null;

                // Prioridad:
                //  - Auth guard 'vigilante'
                //  - Auth default (web)
                //  - session('vigilante') array
                //  - session()->get('user') u otros (por si usas otra llave)

                // Intentar guard 'vigilante'
                try {
                    if (\Illuminate\Support\Facades\Auth::guard('vigilante')->check()) {
                        $u = \Illuminate\Support\Facades\Auth::guard('vigilante')->user();
                        $idVigilante = $u->id_vigilante ?? $u->id ?? null;
                    }
                } catch (\Throwable $e) {
                    // guard 'vigilante' puede no existir, ignorar
                    Log::debug('vehiculo::booted - guard vigilante check failed: '.$e->getMessage());
                }

                // Si no encontrado, intentar Auth::user() (web or default)
                if (empty($idVigilante)) {
                    try {
                        if (\Illuminate\Support\Facades\Auth::check()) {
                            $u2 = \Illuminate\Support\Facades\Auth::user();
                            $idVigilante = $u2->id_vigilante ?? $u2->id ?? $idVigilante;
                        }
                    } catch (\Throwable $e) {
                        Log::debug('vehiculo::booted - Auth::check() failed: '.$e->getMessage());
                    }
                }

                // Si no hay id, intentar session('vigilante')
                if (empty($idVigilante) && session()->has('vigilante')) {
                    $sv = session('vigilante');
                    $idVigilante = $sv['id_vigilante'] ?? $sv['id'] ?? $sv['id_usuario'] ?? $idVigilante;
                }

                // Si aún no hay id, intentar session()->get('user') u otras llaves comunes
                if (empty($idVigilante)) {
                    $sv2 = session()->get('user');
                    if (is_array($sv2) && !empty($sv2['id_vigilante'])) {
                        $idVigilante = $sv2['id_vigilante'];
                    }
                }

                // Obtener nombre si hay id
                if ($idVigilante) {
                    try {
                        $v = \App\Models\vigilante::find($idVigilante);
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
                    } catch (\Throwable $e) {
                        Log::debug('vehiculo::booted - error finding vigilante model: '.$e->getMessage());
                    }
                }

                Log::info('vehiculo::booted - resolved vigilante', [
                    'vehiculo_id' => $vehiculo->id_vehiculo ?? null,
                    'id_vigilante' => $idVigilante,
                    'vigilante_nombre' => $vigilanteNombre
                ]);

                // Preparar tiqueteData
                $idTarifa = $vehiculo->id_tarifa ?? null;

                $tiqueteData = [
                    'codigo_uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'id_vehiculo' => $vehiculo->id_vehiculo,
                    'id_vigilante' => $idVigilante,
                    'id_tarifa' => $idTarifa,
                    'hora_entrada' => now(),
                    'hora_salida' => null,
                    'estado' => 1,
                    'observaciones' => $vehiculo->descripcion ?? 'Creado automáticamente al registrar vehículo'
                ];

                if ($vigilanteNombre) {
                    $tiqueteData['vigilante_nombre'] = $vigilanteNombre;
                }

                \App\Models\tiquete::create($tiqueteData);

            } catch (\Throwable $e) {
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
