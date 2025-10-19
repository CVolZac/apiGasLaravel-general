<?php

namespace App\Http\Controllers;

use App\Models\BitacoraDispensario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BitacoraDispensarioController extends Controller
{
    // GET v1/bitacora-dispensario/{idPlanta}
    public function indexByPlanta($idPlanta)
    {
        $data = BitacoraDispensario::planta((int)$idPlanta)
            ->orderBy('fecha_hora', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($data);
    }

    // GET v1/bitacora-dispensario/{idPlanta}/{id}
    public function show($idPlanta, $id)
    {
        $row = BitacoraDispensario::planta((int)$idPlanta)->where('id', (int)$id)->firstOrFail();
        return response()->json($row);
    }

    // POST v1/bitacora-dispensario
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_planta'       => ['required','integer'],
            'fecha_hora'      => ['required','date'],
            'tipo_evento'     => ['required','string','max:50',
                // opcional: fuerza catálogo
                Rule::in([
                    'ALERTA_FUGA','PARO_EMERGENCIA','FALLA_ENERGIA','FALLA_COMUNICACION',
                    'MANTENIMIENTO','CALIBRACION','FUERA_DE_OPERACION','ENTRADA_EN_OPERACION',
                    'AJUSTE_CONFIG','RESETEO_SISTEMA','SUSTITUCION_COMPONENTE','ALERTA_RANGO',
                    'VIGENCIA_CALIB_VENC','ACTUALIZACION_SW',
                ])
            ],
            'severidad'       => ['required', Rule::in(['INFO','WARN','CRIT'])],
            'componente_tipo' => ['required', Rule::in(['DISPENSARIO','MANGUERA','MEDIDOR'])],
            'id_componente'   => ['required','integer'],
            'id_dispensario'  => ['nullable','integer'],
            'usuario_responsable' => ['nullable','string','max:120'],
            'descripcion'     => ['nullable','string'],
        ]);

        $row = BitacoraDispensario::create($validated);

        return response()->json([
            'message' => 'Evento registrado',
            'id'      => $row->id,
            'data'    => $row,
        ], 201);
    }

    // POST v1/bitacora-dispensario/{id}
    public function update($id, Request $request)
    {
        $row = BitacoraDispensario::findOrFail((int)$id);

        $validated = $request->validate([
            'id_planta'       => ['required','integer'],
            'fecha_hora'      => ['required','date'],
            'tipo_evento'     => ['required','string','max:50',
                Rule::in([
                    'ALERTA_FUGA','PARO_EMERGENCIA','FALLA_ENERGIA','FALLA_COMUNICACION',
                    'MANTENIMIENTO','CALIBRACION','FUERA_DE_OPERACION','ENTRADA_EN_OPERACION',
                    'AJUSTE_CONFIG','RESETEO_SISTEMA','SUSTITUCION_COMPONENTE','ALERTA_RANGO',
                    'VIGENCIA_CALIB_VENC','ACTUALIZACION_SW',
                ])
            ],
            'severidad'       => ['required', Rule::in(['INFO','WARN','CRIT'])],
            'componente_tipo' => ['required', Rule::in(['DISPENSARIO','MANGUERA','MEDIDOR'])],
            'id_componente'   => ['required','integer'],
            'id_dispensario'  => ['nullable','integer'],
            'usuario_responsable' => ['nullable','string','max:120'],
            'descripcion'     => ['nullable','string'],
        ]);

        $row->update($validated);

        return response()->json([
            'message' => 'Evento actualizado',
            'data'    => $row,
        ]);
    }

    // DELETE v1/bitacora-dispensario/{id}
    public function destroy($id)
    {
        $row = BitacoraDispensario::findOrFail((int)$id);
        $row->delete();

        return response()->json(['message' => 'Evento eliminado']);
    }
}
