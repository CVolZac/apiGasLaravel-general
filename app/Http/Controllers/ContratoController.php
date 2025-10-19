<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContratoController extends Controller
{
    public function index(Request $request)
    {
        $query = Contrato::with('contraparte:id,nombre,rfc')
            ->when($request->contraparte_id, fn($q,$v) => $q->where('contraparte_id', $v))
            ->when($request->clave_producto, fn($q,$v) => $q->where('clave_producto', $v))
            ->orderBy('id','desc');

        return $query->paginate(25);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $contrato = Contrato::create($data);
        return response()->json($contrato->load('contraparte:id,nombre,rfc'), 201);
    }

    public function show(Contrato $contrato)
    {
        return $contrato->load('contraparte:id,nombre,rfc');
    }

    public function update(Request $request, Contrato $contrato)
    {
        $data = $this->validateData($request, $contrato->id);
        $contrato->update($data);
        return $contrato->load('contraparte:id,nombre,rfc');
    }

    public function destroy(Contrato $contrato)
    {
        $contrato->delete();
        return response()->json([], 204);
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

            'clave_producto'    => ['required','string','max:10'],
            'clave_subproducto' => ['nullable','string','max:20'],
            'um'                => ['required','string','max:20'],

            'precio_base_formula' => ['nullable','string'],
            'descuento_pct'       => ['nullable','numeric','min:0'],
            'origen_precio'       => ['required', Rule::in(['contrato','evento'])],

            'tipos_cfdi'          => ['nullable','array'],
            'tipos_cfdi.*'        => ['in:I,E,T,P'],

            'uuid_requerido'      => ['required','boolean'],
            'validar_fecha_importe'=>['required','boolean'],

            'permiso_almacenamiento' => ['nullable','string','max:100'],
            'permiso_transporte'     => ['nullable','string','max:100'],

            'estatus' => ['nullable', Rule::in(['activo','inactivo'])],
        ]);
    }
}
