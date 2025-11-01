<?php
// (encabezado del archivo ya existente...)
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\vigilante;
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
        //return $this->belongsTo(tarifa::class, 'id_tarifa', 'id_tarifa');
        return $this->belongsTo(tarifa::class, 'id_tarifa', 'id_tarifa');
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
                if (Auth::check()) {
                    // Si tu guard usa id_vigilante en el user, intenta varios campos
                    $idVigilante = Auth::user()->id_vigilante ?? Auth::id() ?? null;
                }
                if (!$idVigilante && session()->has('vigilante')) {
                    $v = session('vigilante');
                    $idVigilante = $v['id_vigilante'] ?? $v['id'] ?? $v['id_usuario'] ?? null;
                }

                // Obtener nombre del vigilante (si hay id)
                $vigilanteNombre = null;
                if ($idVigilante) {
                    $v = Vigilante::find($idVigilante);
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
        return $this->belongsTo(Vigilante::class, 'id_usuario', 'id_vigilante');
    }

}
