<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContratoController extends Controller
{
    // GET /v1/contratos?contraparte_id=&clave_producto=
    public function index(Request $request)
    {
        $query = Contrato::with('contraparte:id,nombre,rfc')
            ->when($request->contraparte_id, fn($q,$v) => $q->where('contraparte_id', $v))
            ->when($request->clave_producto, fn($q,$v) => $q->where('clave_producto', $v));

        $data = $query->orderBy('id','desc')->paginate(25);
        return response()->json($data);
    }

    // GET /v1/contratos/{id}
    public function show($id)
    {
        $k = Contrato::with('contraparte:id,nombre,rfc')->findOrFail($id);
        return response()->json($k);
    }

    // POST /v1/contratos
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contraparte_id' => ['required','integer','exists:contrapartes,id'],

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

            // Estas dos conviértelas a boolean en el FRONT si puedes:
            // <ion-select [value]="true/false"> para que lleguen como true/false
            'uuid_requerido'        => ['required'],
            'validar_fecha_importe' => ['required'],

            'permiso_almacenamiento' => ['nullable','string','max:100'],
            'permiso_transporte'     => ['nullable','string','max:100'],

            'estatus' => ['nullable', Rule::in(['activo','inactivo'])],
        ]);

        // Si por alguna razón llegan como 'si'/'no', conviértelos aquí a boolean:
        $validated['uuid_requerido']        = filter_var($validated['uuid_requerido'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ($validated['uuid_requerido']==='si');
        $validated['validar_fecha_importe'] = filter_var($validated['validar_fecha_importe'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ($validated['validar_fecha_importe']==='si');

        if ($validated['descuento_pct'] === null || $validated['descuento_pct'] === '') {
            $validated['descuento_pct'] = 0;
        }

        $k = Contrato::create($validated);

        return response()->json([
            'message' => 'Contrato creado',
            'id' => $k->id,
            'data' => $k->load('contraparte:id,nombre,rfc')
        ], 201);
    }

    // POST /v1/contratos/{id}
    public function update($id, Request $request)
    {
        $k = Contrato::findOrFail($id);

        $validated = $request->validate([
            'contraparte_id' => ['required','integer','exists:contrapartes,id'],
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
            'uuid_requerido'        => ['required'],
            'validar_fecha_importe' => ['required'],
            'permiso_almacenamiento' => ['nullable','string','max:100'],
            'permiso_transporte'     => ['nullable','string','max:100'],
            'estatus' => ['nullable', Rule::in(['activo','inactivo'])],
        ]);

        $validated['uuid_requerido']        = filter_var($validated['uuid_requerido'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ($validated['uuid_requerido']==='si');
        $validated['validar_fecha_importe'] = filter_var($validated['validar_fecha_importe'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ($validated['validar_fecha_importe']==='si');

        if ($validated['descuento_pct'] === null || $validated['descuento_pct'] === '') {
            $validated['descuento_pct'] = 0;
        }

        $k->update($validated);

        return response()->json([
            'message' => 'Contrato actualizado',
            'data' => $k->load('contraparte:id,nombre,rfc')
        ]);
    }

    // (Opcional)
    public function destroy($id)
    {
        $k = Contrato::findOrFail($id);
        $k->delete();
        return response()->json(['message' => 'Contrato eliminado']);
    }
}
