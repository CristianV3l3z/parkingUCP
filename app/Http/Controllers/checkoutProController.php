<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\tiquete;
use App\Models\pago;
use Carbon\Carbon;

class CheckoutProController extends Controller
{
    // --- Crear o reutilizar preferencia MP para un tiquete ---
    public function crearPreferencia(Request $request, $id = null)
    {
        $idTiquete = $id ?? $request->input('id_tiquete');
        if (!$idTiquete) {
            return response()->json(['message' => 'id_tiquete es requerido'], 400);
        }

        $tiquete = tiquete::with('vehiculo','tarifa')->find($idTiquete);
        if (!$tiquete) {
            return response()->json(['message' => 'Tiquete no encontrado'], 404);
        }

        // El tiquete debe estar en estado abierto (1) para poder iniciar pago
        if ((int)$tiquete->estado !== 1) {
            return response()->json(['message' => 'El tiquete no está en estado abierto (1).'], 400);
        }

        // Calcula monto (ajusta a tu lógica si necesitas)
        $valorHora = $tiquete->tarifa->valor ?? 0;
        $horaEntrada = $tiquete->hora_entrada ? Carbon::parse($tiquete->hora_entrada) : Carbon::parse($tiquete->created_at);
        $horaSalida = $tiquete->hora_salida ? Carbon::parse($tiquete->hora_salida) : Carbon::now();
        $hours = (int) ceil(max(0, $horaSalida->floatDiffInHours($horaEntrada)));
        $monto = max((float)$valorHora * max(1, $hours), 0);

        // Intentamos resolver id_usuario (robusto: auth() o session)
        $idUsuario = null;
        if (auth()->check()) {
            // si tu usuario en Auth tiene id_usuario usa eso
            $user = auth()->user();
            $idUsuario = $user->id_usuario ?? $user->id ?? null;
        } else {
            $idUsuario = session('vigilante.id_usuario') ?? session('usuario.id_usuario') ?? null;
        }

        // Revisar si existe pago para este tiquete (orden descendente)
        $existing = pago::where('id_tiquete', $tiquete->id_tiquete)->orderBy('created_at','desc')->first();

        // Si ya existe pago aprobado -> no permitir nuevo intento
        if ($existing && $existing->estado_pago === 'aprobado') {
            return response()->json(['message' => 'Este tiquete ya tiene un pago aprobado.'], 400);
        }

        // Si existe y tiene init_point y está pendiente -> devolverlo para reintento inmediato
        if ($existing && $existing->mp_init_point && $existing->estado_pago === 'pendiente') {
            return response()->json([
                'init_point' => $existing->mp_init_point,
                'preference_id' => $existing->mp_preference_id,
                'pago_id' => $existing->id_pago,
                'reused' => true
            ], 200);
        }

        // Construir payload preference MP
        $preferenceData = [
            "items" => [
                [
                    "title" => "Pago tiquete #{$tiquete->id_tiquete}",
                    "quantity" => 1,
                    "unit_price" => (float)$monto,
                    "currency_id" => "COP"
                ]
            ],
            "external_reference" => (string)$tiquete->id_tiquete,
            "back_urls" => [
                "success" => env('APP_URL') . "/checkout/success",
                "pending" => env('APP_URL') . "/checkout/pending",
                "failure" => env('APP_URL') . "/checkout/failure",
            ],
            "notification_url" => env('APP_URL') . "/api/checkout/webhook",
            "auto_return" => "approved"
        ];

        try {
            $token = env('MERCADOPAGO_ACCESS_TOKEN');
            if (!$token) {
                Log::error('MERCADOPAGO_ACCESS_TOKEN no definido en .env');
                return response()->json(['message'=>'MERCADOPAGO_ACCESS_TOKEN no configurado'], 500);
            }

            $resp = Http::withToken($token)
                ->post('https://api.mercadopago.com/checkout/preferences', $preferenceData);

            if ($resp->failed()) {
                Log::error('Error creating MP preference', ['resp'=>$resp->body(),'tiquete'=>$tiquete->id_tiquete]);
                return response()->json(['message'=>'Error creando preferencia MP','detail'=>$resp->body()], 500);
            }

            $json = $resp->json();

            // Generar recibo local único
            $recibo = 'REC-' . strtoupper(uniqid());

            DB::beginTransaction();
            try {
                if ($existing) {
                    // actualizar el pago existente para reintento (no insertar duplicado)
                    $existing->id_usuario = $idUsuario;
                    $existing->monto = $monto;
                    $existing->metodo_pago = 'mercado_pago';
                    $existing->fecha_pago = now(); // registramos intento ahora
                    $existing->estado_pago = 'pendiente';
                    $existing->recibo = $recibo;
                    $existing->mp_preference_id = $json['id'] ?? null;
                    $existing->mp_init_point = $json['init_point'] ?? null;
                    $existing->save();
                    $pago = $existing;
                } else {
                    // crear nuevo pago
                    $pago = pago::create([
                        'id_tiquete'       => $tiquete->id_tiquete,
                        'id_usuario'       => $idUsuario,
                        'monto'            => $monto,
                        'metodo_pago'      => 'mercado_pago',
                        'fecha_pago'       => now(),
                        'estado_pago'      => 'pendiente',
                        'recibo'           => $recibo,
                        'mp_preference_id' => $json['id'] ?? null,
                        'mp_init_point'    => $json['init_point'] ?? null,
                    ]);
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Error guardando pago local', ['error'=>$e->getMessage()]);
                return response()->json(['message'=>'Error guardando pago local','error'=>$e->getMessage()], 500);
            }

            // devolvemos init_point al front
            return response()->json([
                'init_point' => $json['init_point'] ?? null,
                'preference_id' => $json['id'] ?? null,
                'pago_id' => $pago->id_pago ?? null
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Excepción creando preferencia: '.$e->getMessage());
            return response()->json(['message'=>'Excepción al crear preferencia','error'=>$e->getMessage()], 500);
        }
    }

    // --- status para hacer polling desde front ---
    public function status($id_tiquete)
    {
        $pago = pago::where('id_tiquete', $id_tiquete)->orderBy('created_at','desc')->first();
        if (!$pago) return response()->json(['message'=>'No hay pagos para este tiquete'], 404);
        return response()->json(['pago' => $pago], 200);
    }

    // --- webhook (actualiza mp_payment_id, mp_raw_response, mapea estado y cierra tiquete cuando aprobado) ---
    public function webhook(Request $request)
    {
        Log::info('MP webhook received', ['payload' => $request->all()]);

        $data = $request->all();
        $paymentId = null;
        if (isset($data['data']['id'])) {
            $paymentId = $data['data']['id'];
        } elseif (isset($data['id'])) {
            $paymentId = $data['id'];
        }

        if (!$paymentId) {
            return response()->json(['message'=>'No payment id en webhook'], 400);
        }

        try {
            $token = env('MERCADOPAGO_ACCESS_TOKEN');
            if (!$token) {
                Log::error('MERCADOPAGO_ACCESS_TOKEN no definido en .env (webhook)');
                return response()->json(['message'=>'MERCADOPAGO_ACCESS_TOKEN no configurado'], 500);
            }

            $resp = Http::withToken($token)->get("https://api.mercadopago.com/v1/payments/{$paymentId}");
            if ($resp->failed()) {
                Log::warning('MP fetch payment failed', ['id'=>$paymentId,'body'=>$resp->body()]);
                return response()->json(['message'=>'No se pudo obtener payment'], 500);
            }

            $payment = $resp->json();
            $statusMp = strtolower($payment['status'] ?? ($payment['collection_status'] ?? 'unknown'));
            $external = $payment['external_reference'] ?? null;

            // mapeo a español para tu columna check
            $mapped = $this->mapMpStatusToLocal($statusMp);

            // encontrar pago local
            $pago = pago::where('mp_payment_id', $paymentId)
                ->orWhere('mp_preference_id', $payment['preference_id'] ?? null)
                ->orWhere(function($q) use ($external) {
                    if ($external) $q->where('id_tiquete', $external);
                })->orderBy('created_at','desc')->first();

            if (!$pago) {
                // creamos fallback record
                $pago = pago::create([
                    'id_tiquete' => $external,
                    'id_usuario' => null,
                    'monto' => $payment['transaction_amount'] ?? 0,
                    'metodo_pago' => 'mercado_pago',
                    'fecha_pago' => ($mapped === 'aprobado' ? now() : null),
                    'estado_pago' => $mapped,
                    'recibo' => 'REC-' . strtoupper(uniqid()),
                    'mp_payment_id' => $paymentId,
                    'mp_raw_response' => json_encode($payment)
                ]);
            } else {
                $pago->estado_pago = $mapped;
                $pago->mp_payment_id = $paymentId;
                $pago->mp_raw_response = json_encode($payment);
                if ($mapped === 'aprobado') {
                    $pago->fecha_pago = now();
                }
                $pago->save();
            }

            // si aprobado => cerrar tiquete
            if ($mapped === 'aprobado' && $external) {
                $tiquete = tiquete::find($external);
                if ($tiquete) {
                    $tiquete->hora_salida = $tiquete->hora_salida ?? now();
                    $tiquete->estado = 0;
                    $tiquete->save();
                }
            }

            return response()->json(['ok'=>true],200);

        } catch (\Throwable $e) {
            Log::error('Error processing MP webhook: '.$e->getMessage(), ['exception' => $e]);
            return response()->json(['message'=>'exception','error'=>$e->getMessage()], 500);
        }
    }

    private function mapMpStatusToLocal(string $s): string
    {
        $s = strtolower(trim($s));
        if (in_array($s, ['approved','approved_by_payment','paid'])) return 'aprobado';
        if (in_array($s, ['in_process','pending','pending_review'])) return 'pendiente';
        if (in_array($s, ['rejected','refused','failure','cancelled'])) return 'rechazado';
        return 'pendiente';
    }
}
