<?php

namespace App\Http\Controllers;

use App\Models\EventoAlmacen;
use App\Models\Cfdi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EventoAlmacenController extends Controller
{
    /**
     * GET /v1/eventoAlmacen/{idPlanta}
     * Query params opcionales: ?from=YYYY-MM-DD&to=YYYY-MM-DD&tipo=entrada|salida&page=1
     */
    public function index($idAlmacen, Request $request)
    {
        try {
            $from = $request->query('from');
            $to   = $request->query('to');
            $tipo = $request->query('tipo');

            $eventos = EventoAlmacen::with([
                'almacen',
                'tanque',
                'complementoTransporte',
                'complementoTrasvase',
                'complementoNacional',
                'complementoExtranjero',
                'cfdis',
            ])
                ->deAlmacen($idAlmacen)
                ->entreFechas($from, $to)
                ->tipo($tipo)
                ->orderByDesc('fecha_inicio_evento')
                ->paginate(50);

            return response()->json($eventos, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 501);
        }
    }

    /**
     * GET /v1/eventoAlmacen/{idPlanta}/{id}
     */
    public function show($idAlmacen, $id)
    {
        try {
            $evento = EventoAlmacen::with([
                'almacen',
                'tanque',
                'complementoTransporte',
                'complementoNacional',
                'complementoExtranjero',
                'complementoTrasvase',    // si lo usas
                'cfdis',                  // o 'eventoCfdis' si usas la pivote enriquecida
            ])
                ->deAlmacen($idAlmacen)
                ->findOrFail($id);

            return response()->json($evento, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 404);
        }
    }

    /**
     * POST /v1/eventoAlmacen
     * Crea el evento. Acepta solo los campos del EVENTO. (CFDIs/Pedimentos vía sus propios endpoints o en una segunda fase)
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        try {
            $res = DB::transaction(function () use ($data) {
                $evento = EventoAlmacen::create([
                    'id_almacen'          => $data['id_almacen'],
                    'id_tanque'           => $data['id_tanque'] ?? null,
                    'id_producto'         => $data['id_producto'] ?? null,
                    'lote'                => $data['lote'] ?? null,

                    'tipo_evento'         => $data['tipo_evento'],

                    'volumen_inicial'     => $data['volumen_inicial'] ?? null,
                    'volumen_movido'      => $data['volumen_movido'] ?? null,     // compatibilidad
                    'volumen_movido_vo'   => $data['volumen_movido_vo'] ?? null,
                    'volumen_movido_vc'   => $data['volumen_movido_vc'] ?? null,
                    'volumen_final'       => $data['volumen_final'] ?? null,
                    'unidad'              => $data['unidad'] ?? null,

                    'fecha_inicio_evento' => $data['fecha_inicio_evento'],
                    'fecha_fin_evento'    => $data['fecha_fin_evento'] ?? null,

                    'modo_medicion'       => $data['modo_medicion'] ?? null,
                    'nivel'               => $data['nivel'] ?? null,
                    'temperatura'         => $data['temperatura'] ?? null,
                    'presion_absoluta'    => $data['presion_absoluta'] ?? null,
                    'densidad'            => $data['densidad'] ?? null,
                    'caudal'              => $data['caudal'] ?? null,
                    'tiempo'              => $data['tiempo'] ?? null,
                    'totalizador'         => $data['totalizador'] ?? null,

                    'origen'              => $data['origen'] ?? null,
                    'observaciones'       => $data['observaciones'] ?? null,
                ]);

                // (Opcional) Si envías CFP/Complementos en el mismo payload, aquí puedes crearlos.
                // Hecho así a propósito: mantener simple el primer corte.

                return $evento->load([
                    'almacen',
                    'tanque',
                    'complementoTransporte',
                    'complementoTrasvase',
                    'complementoNacional',
                    'complementoExtranjero',
                    'cfdis'
                ]);
            });

            return response()->json($res, 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /v1/eventoAlmacen/{id}
     * Actualiza el evento.
     */
    public function update(Request $request, $id)
    {
        $data = $this->validateData($request, updating: true);

        try {
            $res = DB::transaction(function () use ($id, $data) {
                $evento = EventoAlmacen::findOrFail($id);

                $evento->fill([
                    'id_almacen'          => $data['id_almacen'] ?? $evento->id_almacen,
                    'id_tanque'           => $data['id_tanque']  ?? $evento->id_tanque,
                    'id_producto'         => $data['id_producto'] ?? $evento->id_producto,
                    'lote'                => $data['lote'] ?? $evento->lote,

                    'tipo_evento'         => $data['tipo_evento'] ?? $evento->tipo_evento,

                    'volumen_inicial'     => array_key_exists('volumen_inicial', $data) ? $data['volumen_inicial'] : $evento->volumen_inicial,
                    'volumen_movido'      => array_key_exists('volumen_movido', $data) ? $data['volumen_movido'] : $evento->volumen_movido,
                    'volumen_movido_vo'   => array_key_exists('volumen_movido_vo', $data) ? $data['volumen_movido_vo'] : $evento->volumen_movido_vo,
                    'volumen_movido_vc'   => array_key_exists('volumen_movido_vc', $data) ? $data['volumen_movido_vc'] : $evento->volumen_movido_vc,
                    'volumen_final'       => array_key_exists('volumen_final', $data) ? $data['volumen_final'] : $evento->volumen_final,
                    'unidad'              => $data['unidad'] ?? $evento->unidad,

                    'fecha_inicio_evento' => $data['fecha_inicio_evento'] ?? $evento->fecha_inicio_evento,
                    'fecha_fin_evento'    => array_key_exists('fecha_fin_evento', $data) ? $data['fecha_fin_evento'] : $evento->fecha_fin_evento,

                    'modo_medicion'       => $data['modo_medicion'] ?? $evento->modo_medicion,
                    'nivel'               => array_key_exists('nivel', $data) ? $data['nivel'] : $evento->nivel,
                    'temperatura'         => array_key_exists('temperatura', $data) ? $data['temperatura'] : $evento->temperatura,
                    'presion_absoluta'    => array_key_exists('presion_absoluta', $data) ? $data['presion_absoluta'] : $evento->presion_absoluta,
                    'densidad'            => array_key_exists('densidad', $data) ? $data['densidad'] : $evento->densidad,
                    'caudal'              => array_key_exists('caudal', $data) ? $data['caudal'] : $evento->caudal,
                    'tiempo'              => array_key_exists('tiempo', $data) ? $data['tiempo'] : $evento->tiempo,
                    'totalizador'         => array_key_exists('totalizador', $data) ? $data['totalizador'] : $evento->totalizador,

                    'origen'              => $data['origen'] ?? $evento->origen,
                    'observaciones'       => array_key_exists('observaciones', $data) ? $data['observaciones'] : $evento->observaciones,
                ])->save();

                return $evento->fresh()->load([
                    'almacen',
                    'tanque',
                    'complementoTransporte',
                    'complementoTrasvase',
                    'complementoNacional',
                    'complementoExtranjero',
                    'cfdis'
                ]);
            });

            return response()->json($res, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * DELETE /v1/eventoAlmacen/{id}
     */
    public function destroy($id)
    {
        try {
            $deleted = EventoAlmacen::findOrFail($id)->delete();
            return response()->json(['deleted' => (bool)$deleted], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /* ========= Validación interna ========= */

    private function validateData(Request $request, bool $updating = false): array
    {
        // Reglas mínimas alineadas a tu formulario
        $rules = [
            'id_almacen'          => [$updating ? 'sometimes' : 'required', 'integer', 'exists:almacen,id'], // ajusta a 'almacenes'
            'id_tanque'           => ['sometimes', 'nullable', 'integer'],
            'id_producto'         => ['sometimes', 'nullable', 'integer'],
            'lote'                => ['sometimes', 'nullable', 'string', 'max:100'],

            'tipo_evento'         => [$updating ? 'sometimes' : 'required', Rule::in(['entrada', 'salida'])],

            'volumen_inicial'     => ['sometimes', 'nullable', 'numeric'],
            'volumen_movido'      => ['sometimes', 'nullable', 'numeric'],
            'volumen_movido_vo'   => ['sometimes', 'nullable', 'numeric'],
            'volumen_movido_vc'   => ['sometimes', 'nullable', 'numeric'],
            'volumen_final'       => ['sometimes', 'nullable', 'numeric'],
            'unidad'              => ['sometimes', 'nullable', 'string', 'max:12'],

            'fecha_inicio_evento' => [$updating ? 'sometimes' : 'required', 'date'],
            'fecha_fin_evento'    => ['sometimes', 'nullable', 'date', 'after_or_equal:fecha_inicio_evento'],

            'modo_medicion'       => ['sometimes', 'nullable', Rule::in(['estatica', 'dinamica'])],
            'nivel'               => ['sometimes', 'nullable', 'numeric'],
            'temperatura'         => ['sometimes', 'nullable', 'numeric'],
            'presion_absoluta'    => ['sometimes', 'nullable', 'numeric'],
            'densidad'            => ['sometimes', 'nullable', 'numeric'],
            'caudal'              => ['sometimes', 'nullable', 'numeric'],
            'tiempo'              => ['sometimes', 'nullable', 'numeric'],
            'totalizador'         => ['sometimes', 'nullable', 'numeric'],

            'origen'              => ['sometimes', 'nullable', Rule::in(['nacional', 'extranjero'])],
            'observaciones'       => ['sometimes', 'nullable', 'string'],
        ];

        return $request->validate($rules);
    }
}
