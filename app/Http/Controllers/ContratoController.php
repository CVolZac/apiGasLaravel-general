<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContratoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Contrato::with('contraparte:id,nombre,rfc')
                ->when($request->contraparte_id, fn($q,$v) => $q->where('contraparte_id', $v))
                ->when($request->clave_producto, fn($q,$v) => $q->where('clave_producto', $v));

            return $query->orderBy('id','desc')->paginate(25);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Error al listar contratos.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $this->validateData($request);

            // 🔁 Normalización para valores que llegan desde Ionic
            $data = $this->normalize($data);

            $contrato = Contrato::create($data);
            return response()->json($contrato->load('contraparte:id,nombre,rfc'), 201);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Error al crear contrato.',
                'error'   => $e->getMessage(),
                'trace'   => config('app.debug') ? $e->getTrace() : null,
            ], 500);
        }
    }

    public function show(Contrato $contrato)
    {
        try {
            return $contrato->load('contraparte:id,nombre,rfc');
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Error al obtener contrato.','error'=>$e->getMessage()], 500);
        }
    }

    public function update(Request $request, Contrato $contrato)
    {
        try {
            $data = $this->validateData($request, $contrato->id);

            // 🔁 Normalización de booleanos/arrays
            $data = $this->normalize($data);

            $contrato->update($data);
            return $contrato->load('contraparte:id,nombre,rfc');
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Error al actualizar contrato.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Contrato $contrato)
    {
        try {
            $contrato->delete();
            return response()->json([], 204);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Error al eliminar contrato.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function validateData(Request $request, $id = null): array
    {
        return $request->validate([
            'contraparte_id' => ['required','exists:contrapartes,id'],

            'vigencia_inicio'=> ['nullable','date'],
            'vigencia_fin'   => ['nullable','date','after_or_equal:vigencia_inicio'],

            'moneda'         => ['required', Rule::in(['MXN','USD','EUR'])],
            'incoterm'       => ['nullable','string','max:10'],
            'lugar_entrega'  => ['nullable','string','max:150'],

            'clave_producto'    => ['required','string','max:20'],
            'clave_subproducto' => ['nullable','string','max:20'],
            'um'                => ['required','string','max:20'],

            'precio_base_formula' => ['nullable','string'],
            'descuento_pct'       => ['nullable','numeric','min:0'],
            'origen_precio'       => ['required', Rule::in(['contrato','evento'])],

            'tipos_cfdi'          => ['nullable','array'],
            'tipos_cfdi.*'        => ['in:I,E,T,P'],

            // llegan como 'si'/'no' desde Ionic; los normalizamos en normalize()
            'uuid_requerido'       => ['required'],
            'validar_fecha_importe'=> ['required'],

            'permiso_almacenamiento' => ['nullable','string','max:100'],
            'permiso_transporte'     => ['nullable','string','max:100'],

            'estatus' => ['nullable', Rule::in(['activo','inactivo'])],
        ]);
    }

    /**
     * Normaliza el payload para que sea compatible con las columnas de Postgres:
     * - 'si'/'no' -> boolean
     * - tipos_cfdi string -> array (si viniera mal)
     * - descuento_pct vacío -> 0
     */
    private function normalize(array $data): array
    {
        // Si vienen como 'si'/'no' o 'true'/'false' string, convertir a bool
        foreach (['uuid_requerido', 'validar_fecha_importe'] as $boolField) {
            if (array_key_exists($boolField, $data)) {
                $val = $data[$boolField];
                if (is_string($val)) {
                    $valLower = mb_strtolower($val, 'UTF-8');
                    $data[$boolField] = in_array($valLower, ['si','true','1','on','yes'], true);
                } else {
                    $data[$boolField] = (bool)$val;
                }
            }
        }

        // tipos_cfdi debe ser array (['I','P',...]); si llega string CSV, lo partimos
        if (isset($data['tipos_cfdi']) && !is_array($data['tipos_cfdi'])) {
            $data['tipos_cfdi'] = array_filter(array_map('trim', explode(',', (string)$data['tipos_cfdi'])));
        }

        // descuento_pct: si llega '', poner 0
        if (array_key_exists('descuento_pct', $data) && ($data['descuento_pct'] === '' || $data['descuento_pct'] === null)) {
            $data['descuento_pct'] = 0;
        }

        return $data;
    }
}
