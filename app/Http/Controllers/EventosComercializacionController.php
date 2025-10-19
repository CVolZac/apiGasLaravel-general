<?php

namespace App\Http\Controllers;

use App\Models\EventoComercializacion;
use App\Models\EventosComercializacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;


class EventosComercializacionController extends Controller
{
    // app/Http/Controllers/EventoComercializacionController.php
    public function index(Request $r)
    {
        try {
            // Solo pagina, sin filtros ni whereDate
            $q = EventosComercializacion::orderBy('id', 'desc')->paginate(25);
            return response()->json($q);
        } catch (\Throwable $e) {
            Log::error('INDEX EVENTOS ERROR: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Error al listar eventos',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    // GET /v1/eventos-comercio/{id}
    public function show($id)
    {
        return response()->json(EventosComercializacion::findOrFail($id));
    }

    // POST /v1/eventos-comercio
    public function store(Request $r)
    {
        $base = $r->validate([
            'flota_virtual_id'     => ['required', 'integer', 'exists:flota_virtual,id'],
            'contraparte_id'       => ['nullable', 'integer'],
            'contrato_id'          => ['nullable', 'integer'],
            'tipo_evento'          => ['required', Rule::in(['RECEPCION', 'ENTREGA'])],
            'tipo_registro'        => ['required', Rule::in(['D', 'M'])],
            'producto_clave'       => ['required', 'string', 'max:20'],

            'fecha_hora_inicio'    => ['nullable', 'date'],
            'fecha_hora_fin'       => ['nullable', 'date'],

            'temperatura'          => ['nullable', 'numeric'],
            'presion_absoluta'     => ['nullable', 'numeric'],

            'volumen_inicial_valor' => ['nullable', 'numeric', 'min:0'],
            'volumen_inicial_um'   => ['nullable', 'string', 'max:10'],
            'volumen_movido_valor' => ['nullable', 'numeric', 'min:0'],
            'volumen_movido_um'    => ['nullable', 'string', 'max:10'],
            'volumen_final_tanque' => ['nullable', 'numeric', 'min:0'],

            'existencias'          => ['nullable', 'array'],   // JSON Existencias
            'complemento'          => ['nullable', 'array'],   // JSON Complemento
            'observaciones'        => ['nullable', 'string'],
        ]);

        // Defaults guía (si no vienen)
        $base['temperatura']      = $base['temperatura'] ?? 20.000;
        $base['presion_absoluta'] = $base['presion_absoluta'] ?? 101.325;

        $ev = EventosComercializacion::create($base);
        return response()->json($ev, 201);
    }

    // POST /v1/eventos-comercio/{id}
    public function update($id, Request $r)
    {
        $ev = EventosComercializacion::findOrFail($id);

        $base = $r->validate([
            'flota_virtual_id'     => ['required', 'integer', 'exists:flota_virtual,id'],
            'contraparte_id'       => ['nullable', 'integer'],
            'contrato_id'          => ['nullable', 'integer'],
            'tipo_evento'          => ['required', Rule::in(['RECEPCION', 'ENTREGA'])],
            'tipo_registro'        => ['required', Rule::in(['D', 'M'])],
            'producto_clave'       => ['required', 'string', 'max:20'],

            'fecha_hora_inicio'    => ['nullable', 'date'],
            'fecha_hora_fin'       => ['nullable', 'date'],

            'temperatura'          => ['nullable', 'numeric'],
            'presion_absoluta'     => ['nullable', 'numeric'],

            'volumen_inicial_valor' => ['nullable', 'numeric', 'min:0'],
            'volumen_inicial_um'   => ['nullable', 'string', 'max:10'],
            'volumen_movido_valor' => ['nullable', 'numeric', 'min:0'],
            'volumen_movido_um'    => ['nullable', 'string', 'max:10'],
            'volumen_final_tanque' => ['nullable', 'numeric', 'min:0'],

            'existencias'          => ['nullable', 'array'],
            'complemento'          => ['nullable', 'array'],
            'observaciones'        => ['nullable', 'string'],
        ]);

        $base['temperatura']      = $base['temperatura'] ?? 20.000;
        $base['presion_absoluta'] = $base['presion_absoluta'] ?? 101.325;

        $ev->update($base);
        return response()->json($ev);
    }

    // DELETE /v1/eventos-comercio/{id}
    public function destroy($id)
    {
        $ev = EventosComercializacion::findOrFail($id);
        $ev->delete();
        return response()->json(['message' => 'Eliminado']);
    }
}
