<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InformacionGeneralReporte;
use Illuminate\Support\Facades\Schema;

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

    private function normalizeRfc(?string $r): ?string
    {
        if ($r === null) return null;
        $r = trim($r);
        if ($r === '') return null;
        // recorta a 13 (evita el error de PG “value too long for type character varying(13)”)
        return mb_substr($r, 0, 13);
    }

    // --- Helper para normalizar rfc_proveedores ---
    private function normalizePayload(Request $request): array
    {
        // 1) Normalizar RFCs y numéricos
        $payload = $request->all();

        $payload['rfc_contribuyente'] = $this->normalizeRfc($payload['rfc_contribuyente'] ?? null);
        $payload['rfc_representante_legal'] = $this->normalizeRfc($payload['rfc_representante_legal'] ?? null);
        $payload['rfc_proveedor'] = $this->normalizeRfc($payload['rfc_proveedor'] ?? null);

        // 2) rfc_proveedores puede venir como string "A,B" | array | json-string
        $rp = $payload['rfc_proveedores'] ?? [];
        if (is_string($rp)) {
            $rp = trim($rp);
            if ($rp === '') {
                $rp = [];
            } else {
                // ¿string JSON?
                if ((str_starts_with($rp, '[') && str_ends_with($rp, ']')) || (str_starts_with($rp, '{') && str_ends_with($rp, '}'))) {
                    $dec = json_decode($rp, true);
                    $rp = is_array($dec) ? $dec : array_map('trim', explode(',', $rp));
                } else {
                    $rp = array_map('trim', explode(',', $rp));
                }
            }
        }
        if (is_array($rp)) {
            // normaliza cada RFC a 13 chars o descarta vacíos
            $rp = array_values(array_filter(array_map(function ($v) {
                if (!is_string($v)) return null;
                $v = $this->normalizeRfc($v);
                return $v ?: null;
            }, $rp), fn($v) => $v !== null));
        }
        $payload['rfc_proveedores'] = $rp;

        // 3) Filtra solo columnas válidas (ignora extras sin romper)
        $cols = Schema::getColumnListing('informacion_general_reporte');
        $payload = array_intersect_key($payload, array_flip($cols));

        return $payload;
    }


    public function store(Request $request)
    {
        try {
            // “Acepta lo que venga” (con normalización) y hace UPSERT por id_planta
            $data = $this->normalizePayload($request);

            if (!isset($data['id_planta'])) {
                return response()->json(['error' => 'id_planta requerido'], 422);
            }

            // Si ya existe registro para la planta, actualiza; si no, crea
            $model = InformacionGeneralReporte::where('id_planta', $data['id_planta'])->first();
            if ($model) {
                $model->forceFill($data)->save();
            } else {
                $model = new InformacionGeneralReporte();
                $model->forceFill($data)->save();
            }

            $model->refresh();
            return response()->json($model, $model->wasRecentlyCreated ? 201 : 200);
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

            // “Acepta lo que venga” (con normalización)
            $data = $this->normalizePayload($request);

            // Evita que cambien el id por accidente si viniera en el payload
            unset($data['id']);

            $model->forceFill($data)->save();
            $model->refresh();

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
