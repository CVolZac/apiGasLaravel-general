<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InformacionGeneralReporte;

class InformacionGeneralReporteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($idPlanta)
    {
        try {
            $data = InformacionGeneralReporte::where('id_planta', $idPlanta)->get();
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data['id_planta'] = $request['id_planta'];
            $data['rfc_contribuyente'] = $request['rfc_contribuyente'];
            $data['rfc_representante_legal'] = $request['rfc_representante_legal'];
            $data['rfc_proveedor'] = $request['rfc_proveedor'];

            // ✅ Manejo correcto del campo json
            $data['rfc_proveedores'] = $request->has('rfc_proveedores')
                ? json_encode(explode(',', $request['rfc_proveedores']))
                : json_encode([]);

            $data['tipo_caracter'] = $request['tipo_caracter'];
            $data['modalidad_permiso'] = $request['modalidad_permiso'];
            $data['numero_permiso'] = $request['numero_permiso'];
            $data['numero_contrato_asignacion'] = $request['numero_contrato_asignacion'];
            $data['instalacion_almacen_gas'] = $request['instalacion_almacen_gas'];
            $data['clave_instalacion'] = $request['clave_instalacion'];
            $data['descripcion_instalacion'] = $request['descripcion_instalacion'];
            $data['geolocalizacion_latitud'] = $request['geolocalizacion_latitud'];
            $data['geolocalizacion_longitud'] = $request['geolocalizacion_longitud'];
            $data['numero_pozos'] = $request['numero_pozos'];
            $data['numero_tanques'] = $request['numero_tanques'];
            $data['numero_ductos_entrada_salida'] = $request['numero_ductos_entrada_salida'];
            $data['numero_ductos_transporte'] = $request['numero_ductos_transporte'];
            $data['numero_dispensarios'] = $request['numero_dispensarios'];

            $res = InformacionGeneralReporte::create($data);
            return response()->json($res, 200);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        try {
            $data = InformacionGeneralReporte::find($id);
            if (!$data) {
                return response()->json(['error' => 'Resource not found.'], 404);
            }
            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $data['id_planta'] = $request['id_planta'];
            $data['rfc_contribuyente'] = $request['rfc_contribuyente'];
            $data['rfc_representante_legal'] = $request['rfc_representante_legal'];
            $data['rfc_proveedor'] = $request['rfc_proveedor'];
            $data['rfc_proveedores'] = explode(',', $request['rfc_proveedores']);
            $data['tipo_caracter'] = $request['tipo_caracter'];
            $data['modalidad_permiso'] = $request['modalidad_permiso'];
            $data['numero_permiso'] = $request['numero_permiso'];
            $data['numero_contrato_asignacion'] = $request['numero_contrato_asignacion'];
            $data['instalacion_almacen_gas'] = $request['instalacion_almacen_gas'];
            $data['clave_instalacion'] = $request['clave_instalacion'];
            $data['descripcion_instalacion'] = $request['descripcion_instalacion'];
            $data['geolocalizacion_latitud'] = $request['geolocalizacion_latitud'];
            $data['geolocalizacion_longitud'] = $request['geolocalizacion_longitud'];
            $data['numero_pozos'] = $request['numero_pozos'];
            $data['numero_tanques'] = $request['numero_tanques'];
            $data['numero_ductos_entrada_salida'] = $request['numero_ductos_entrada_salida'];
            $data['numero_ductos_transporte'] = $request['numero_ductos_transporte'];
            $data['numero_dispensarios'] = $request['numero_dispensarios'];

            $model = InformacionGeneralReporte::find($id);
            if (!$model) {
                return response()->json(['error' => 'Resource not found.'], 404);
            }

            $model->update($data);
            return response()->json($model, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $model = InformacionGeneralReporte::find($id);
            if (!$model) {
                return response()->json(['error' => 'Resource not found.'], 404);
            }
            
            $res = $model->delete();
            return response()->json(["deleted" => $res], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
