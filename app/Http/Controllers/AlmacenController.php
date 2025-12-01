<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Almacen;

class AlmacenController extends Controller
{
    /**
     * GET todos los tanques de la planta.
     */
    public function index($idPlanta)
    {
        try {
            $data = Almacen::where('id_planta', $idPlanta)->get();
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            return response()->json([ 'error' => $th->getMessage() ], 501);
        }
    }

    /**
     * Crear tanque (planta o autotanque)
     */
    public function store(Request $request)
    {
        try {

            // ------ VALIDACIONES POR TIPO ------
            $tipo = $request->input('tipo_tanque', 'planta');

            if ($tipo === 'planta') {

                $data = $request->validate([
                    'id_planta' => 'required|integer',
                    'tipo_tanque' => 'required|in:planta,autotanque',
                    'clave_almacen' => 'required|string',
                    'localizacion_descripcion_almacen' => 'required|string',
                    'vigencia_calibracion_tanque' => 'required|string',
                    'capacidad_almacen' => 'required|integer',
                    'capacidad_operativa' => 'required|integer',
                    'capacidad_util' => 'required|integer',
                    'capacidad_fondaje' => 'required|integer',
                    'volumen_minimo_operacion' => 'required|integer',
                    'estado_tanque' => 'required|string'
                ]);

            } else { // AUTOTANQUE

                $data = $request->validate([
                    'id_planta' => 'required|integer',
                    'tipo_tanque' => 'required|in:planta,autotanque',
                    'numero_economico' => 'required|string',
                    'placas' => 'required|string',
                    'permiso_cre' => 'nullable|string',
                    'descripcion_tanque' => 'nullable|string',
                    'capacidad_util' => 'required|integer',
                    'estado_tanque' => 'required|string'
                ]);
            }

            $res = Almacen::create($data);
            return response()->json($res, 200);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    /**
     * Ver un tanque
     */
    public function show(Request $request, Almacen $almacen)
    {
        try {
            $data = Almacen::find($request->id);
            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    /**
     * Editar tanque
     */
    public function update(Request $request, Almacen $almacen)
    {
        try {

            $tipo = $request->input('tipo_tanque', 'planta');

            if ($tipo === 'planta') {
                $data = $request->validate([
                    'clave_almacen' => 'required|string',
                    'localizacion_descripcion_almacen' => 'required|string',
                    'vigencia_calibracion_tanque' => 'required|string',
                    'capacidad_almacen' => 'required|integer',
                    'capacidad_operativa' => 'required|integer',
                    'capacidad_util' => 'required|integer',
                    'capacidad_fondaje' => 'required|integer',
                    'volumen_minimo_operacion' => 'required|integer',
                    'estado_tanque' => 'required|string'
                ]);

            } else { // AUTOTANQUE

                $data = $request->validate([
                    'numero_economico' => 'required|string',
                    'placas' => 'required|string',
                    'permiso_cre' => 'nullable|string',
                    'descripcion_tanque' => 'nullable|string',
                    'capacidad_util' => 'required|integer',
                    'estado_tanque' => 'required|string'
                ]);
            }

            Almacen::find($request->id)->update($data);
            $res = Almacen::find($request->id);
            return response()->json($res, 200);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    /**
     * Eliminar tanque
     */
    public function destroy(Request $request, Almacen $almacen)
    {
        try {
            $res = Almacen::find($request->id)->delete();
            return response()->json(["deleted" => $res], 200);
        } catch (\Throwable $th) {
            return response()->json([ 'error' => $th->getMessage()], 500);
        }
    }
}
