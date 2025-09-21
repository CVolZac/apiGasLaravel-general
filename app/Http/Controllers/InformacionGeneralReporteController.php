<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InformacionGeneralReporte;

class InformacionGeneralReporteController extends Controller
{
    public function index($idPlanta)
    {
        try {
            $data = InformacionGeneralReporte::where('id_planta', $idPlanta)->get();
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    // --- Helper para normalizar rfc_proveedores ---
    private function normalizeRfcProveedores($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(
                array_map(fn($v) => is_string($v) ? trim($v) : $v, $value),
                fn($v) => !is_null($v) && $v !== ''
            ));
        }
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') return [];
            return array_values(array_filter(
                array_map('trim', explode(',', $value)),
                fn($v) => $v !== ''
            ));
        }
        // también aceptamos JSON string
        if (is_string($value) && $this->looksLikeJson($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $this->normalizeRfcProveedores($decoded) : [];
        }
        return [];
    }

    private function looksLikeJson(string $s): bool
    {
        $s = trim($s);
        return (str_starts_with($s, '[') && str_ends_with($s, ']')) ||
               (str_starts_with($s, '{') && str_ends_with($s, '}'));
    }

    public function store(Request $request)
    {
        try {
            // Validación mínima; ajusta a tus reglas reales
            $validated = $request->validate([
                'id_planta' => 'required|integer',
                'rfc_contribuyente' => 'required|string',
                'rfc_representante_legal' => 'nullable|string',
                'rfc_proveedor' => 'nullable|string',
                // puede llegar como string (coma) o como array
                'rfc_proveedores' => 'nullable',
                'tipo_caracter' => 'nullable|string',
                'modalidad_permiso' => 'nullable|string',
                'numero_permiso' => 'nullable|string',
                'numero_contrato_asignacion' => 'nullable|string',
                'instalacion_almacen_gas' => 'nullable|string',
                'clave_instalacion' => 'nullable|string',
                'descripcion_instalacion' => 'nullable|string',
                'geolocalizacion_latitud' => 'nullable|numeric',
                'geolocalizacion_longitud' => 'nullable|numeric',
                'numero_pozos' => 'nullable|integer',
                'numero_tanques' => 'nullable|integer',
                'numero_ductos_entrada_salida' => 'nullable|integer',
                'numero_ductos_transporte' => 'nullable|integer',
                'numero_dispensarios' => 'nullable|integer',
            ]);

            // Normaliza proveedores
            $validated['rfc_proveedores'] = $this->normalizeRfcProveedores($request->input('rfc_proveedores', []));

            // Crea registro (si tu modelo castea json->array, no uses json_encode)
            $res = InformacionGeneralReporte::create($validated);

            return response()->json($res, 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

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

    public function update(Request $request, $id)
    {
        try {
            $model = InformacionGeneralReporte::find($id);
            if (!$model) {
                return response()->json(['error' => 'Resource not found.'], 404);
            }

            $validated = $request->validate([
                'id_planta' => 'sometimes|integer',
                'rfc_contribuyente' => 'sometimes|string',
                'rfc_representante_legal' => 'nullable|string',
                'rfc_proveedor' => 'nullable|string',
                'rfc_proveedores' => 'nullable',
                'tipo_caracter' => 'nullable|string',
                'modalidad_permiso' => 'nullable|string',
                'numero_permiso' => 'nullable|string',
                'numero_contrato_asignacion' => 'nullable|string',
                'instalacion_almacen_gas' => 'nullable|string',
                'clave_instalacion' => 'nullable|string',
                'descripcion_instalacion' => 'nullable|string',
                'geolocalizacion_latitud' => 'nullable|numeric',
                'geolocalizacion_longitud' => 'nullable|numeric',
                'numero_pozos' => 'nullable|integer',
                'numero_tanques' => 'nullable|integer',
                'numero_ductos_entrada_salida' => 'nullable|integer',
                'numero_ductos_transporte' => 'nullable|integer',
                'numero_dispensarios' => 'nullable|integer',
            ]);

            if ($request->has('rfc_proveedores')) {
                $validated['rfc_proveedores'] = $this->normalizeRfcProveedores($request->input('rfc_proveedores'));
            }

            $model->update($validated);
            return response()->json($model, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

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
