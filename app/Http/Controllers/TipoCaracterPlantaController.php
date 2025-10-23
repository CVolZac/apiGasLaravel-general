<?php

namespace App\Http\Controllers;

use App\Models\TipoCaracterPlanta;
use Illuminate\Http\Request;

class TipoCaracterPlantaController extends Controller
{
    // Lista todos los renglones para un maestro
    public function indexPorInfoGeneral($infoGeneralId)
    {
        $rows = TipoCaracterPlanta::where('informacion_general_reporte_id', $infoGeneralId)->get();
        return response()->json($rows, 200);
    }

    // Crea uno (enforce exclusión mutua)
    public function store(Request $request)
    {
        $infoGeneralId = $request->input('informacion_general_reporte_id');

        $data = $this->validatePayload($request->all());
        $data = $this->normalizeMutualExclusion($data);

        $row = TipoCaracterPlanta::updateOrCreate(
            $this->makeWhere($infoGeneralId, $data),
            array_merge($data, ['informacion_general_reporte_id' => $infoGeneralId])
        );

        return response()->json($row, 201);
    }

    // Actualiza por id
    public function update(Request $request, $id)
    {
        $row = TipoCaracterPlanta::find($id);
        if (!$row) return response()->json(['error' => 'Not found'], 404);

        $data = $this->validatePayload($request->all());
        $data = $this->normalizeMutualExclusion($data);

        $row->update($data);
        return response()->json($row, 200);
    }

    // Elimina por id
    public function destroy($id)
    {
        $row = TipoCaracterPlanta::find($id);
        if (!$row) return response()->json(['error' => 'Not found'], 404);

        $row->delete();
        return response()->json(['deleted' => true], 200);
    }

    // Bulk Upsert: recibe un array de objetos
    public function bulkUpsert(Request $request, $infoGeneralId)
    {
        $items = $request->all();
        if (!is_array($items)) {
            return response()->json(['error' => 'El payload debe ser un arreglo de objetos'], 422);
        }

        $result = [];
        foreach ($items as $payload) {
            $data = $this->validatePayload($payload);
            $data = $this->normalizeMutualExclusion($data);

            $row = TipoCaracterPlanta::updateOrCreate(
                $this->makeWhere($infoGeneralId, $data),
                array_merge($data, ['informacion_general_reporte_id' => $infoGeneralId])
            );
            $result[] = $row;
        }

        return response()->json($result, 200);
    }

    // ----------------- Helpers -----------------

    private function validatePayload(array $payload): array
    {
        // Si quieres acotar catálogo de tipo_caracter, agrega ->in:permisionario,contratista,asignatario,usuario
        return validator($payload, [
            'tipo_caracter' => 'nullable|string',
            'modalidad_permiso' => 'nullable|string',
            'numero_permiso' => 'nullable|string',
            'numero_contrato_asignacion' => 'nullable|string',
        ])->validate();
    }

    private function normalizeMutualExclusion(array $data): array
    {
        // Normalizar cadenas vacías a null
        foreach (['tipo_caracter', 'modalidad_permiso', 'numero_permiso', 'numero_contrato_asignacion'] as $k) {
            if (array_key_exists($k, $data) && $data[$k] === '') {
                $data[$k] = null;
            }
        }

        $perm = $data['numero_permiso'] ?? null;
        $contr = $data['numero_contrato_asignacion'] ?? null;

        // Exclusión mutua:
        // - Si hay contrato => permiso = null y modalidad = null
        if (!empty($contr)) {
            $data['numero_permiso'] = null;
            $data['modalidad_permiso'] = null;
        }
        // - Si hay permiso => contrato = null
        if (!empty($perm)) {
            $data['numero_contrato_asignacion'] = null;
            // modalidad puede existir solo cuando hay permiso; si no hay permiso, modalidad = null
        } else {
            if (empty($contr)) {
                // cuando no hay ni permiso ni contrato, modalidad no aplica
                $data['modalidad_permiso'] = null;
            }
        }

        return $data;
    }

    private function makeWhere($infoGeneralId, array $data): array
    {
        // llave compuesta para upsert; evita duplicados
        return [
            'informacion_general_reporte_id' => $infoGeneralId,
            'tipo_caracter' => $data['tipo_caracter'] ?? null,
            'modalidad_permiso' => $data['modalidad_permiso'] ?? null,
            'numero_permiso' => $data['numero_permiso'] ?? null,
            'numero_contrato_asignacion' => $data['numero_contrato_asignacion'] ?? null,
        ];
    }
}
