<?php

namespace App\Http\Controllers;

use App\Models\FlotaVirtual;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FlotaVirtualController extends Controller
{
    /**
     * GET /v1/flota-virtual?page=1&tipo_contenedor=&producto=&estatus=&q=
     */
    public function index(Request $request)
    {
        $data = FlotaVirtual::query()
            ->when($request->filled('tipo_contenedor'), fn($q) => $q->where('tipo_contenedor', $request->tipo_contenedor))
            ->when($request->filled('producto'), fn($q) => $q->where('producto_clave', $request->producto))
            ->when($request->filled('estatus'), fn($q) => $q->where('estatus', $request->estatus))
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($w) use ($request) {
                    $w->where('clave_contenedor', 'ilike', "%{$request->q}%")
                      ->orWhere('descripcion', 'ilike', "%{$request->q}%")
                      ->orWhere('tanque_clave_identificacion', 'ilike', "%{$request->q}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(25);

        return response()->json($data);
    }

    /**
     * GET /v1/flota-virtual/{id}
     */
    public function show($id)
    {
        $item = FlotaVirtual::findOrFail($id);
        return response()->json($item);
    }

    /**
     * POST /v1/flota-virtual
     */
    public function store(Request $request)
    {
        // Validación base
        $base = $request->validate([
            'clave_contenedor' => ['required', 'string', 'max:80', 'unique:flota_virtual,clave_contenedor'],
            'tipo_contenedor'  => ['required', Rule::in(['AUTOTANQUE_VIRTUAL','DUCTO_VIRTUAL','TANQUE_VIRTUAL'])],
            'producto_clave'   => ['required', 'string', 'max:20'],
            'capacidad_valor'  => ['nullable', 'numeric', 'min:0'],
            'capacidad_um'     => ['nullable', 'string', 'max:20'],
            'estatus'          => ['required', Rule::in(['activo','inactivo'])],
            'descripcion'      => ['nullable', 'string', 'max:255'],
        ]);

        // Si es TANQUE_VIRTUAL, estos campos son obligatorios
        $tanque = [];
        if ($request->tipo_contenedor === 'TANQUE_VIRTUAL') {
            $tanque = $request->validate([
                'tanque_clave_identificacion' => ['required','string','max:80'],
                'tanque_descripcion'          => ['required','string','max:255'],
                'tanque_vigencia_calibracion' => ['required','date'],
                'tanque_estado'               => ['required', Rule::in(['O','F'])],

                'tanque_cap_total_valor'      => ['required','numeric','min:0'],
                'tanque_cap_total_um'         => ['required','string','max:10'],

                'tanque_cap_oper_valor'       => ['required','numeric','min:0'],
                'tanque_cap_oper_um'          => ['required','string','max:10'],

                'tanque_cap_util_valor'       => ['required','numeric','min:0'],
                'tanque_cap_util_um'          => ['required','string','max:10'],

                'tanque_cap_fondaje_valor'    => ['required','numeric','min:0'],
                'tanque_cap_fondaje_um'       => ['required','string','max:10'],

                'tanque_vol_min_oper_valor'   => ['required','numeric','min:0'],
                'tanque_vol_min_oper_um'      => ['required','string','max:10'],
            ]);
        }

        $item = FlotaVirtual::create(array_merge($base, $tanque));

        return response()->json($item, 201);
    }

    /**
     * POST /v1/flota-virtual/{id}
     */
    public function update($id, Request $request)
    {
        $item = FlotaVirtual::findOrFail($id);

        // Validación base
        $base = $request->validate([
            'clave_contenedor' => ['required', 'string', 'max:80', Rule::unique('flota_virtual','clave_contenedor')->ignore($item->id)],
            'tipo_contenedor'  => ['required', Rule::in(['AUTOTANQUE_VIRTUAL','DUCTO_VIRTUAL','TANQUE_VIRTUAL'])],
            'producto_clave'   => ['required', 'string', 'max:20'],
            'capacidad_valor'  => ['nullable', 'numeric', 'min:0'],
            'capacidad_um'     => ['nullable', 'string', 'max:20'],
            'estatus'          => ['required', Rule::in(['activo','inactivo'])],
            'descripcion'      => ['nullable', 'string', 'max:255'],
        ]);

        // Si es TANQUE_VIRTUAL, obligatorios
        $tanque = [];
        if ($request->tipo_contenedor === 'TANQUE_VIRTUAL') {
            $tanque = $request->validate([
                'tanque_clave_identificacion' => ['required','string','max:80'],
                'tanque_descripcion'          => ['required','string','max:255'],
                'tanque_vigencia_calibracion' => ['required','date'],
                'tanque_estado'               => ['required', Rule::in(['O','F'])],

                'tanque_cap_total_valor'      => ['required','numeric','min:0'],
                'tanque_cap_total_um'         => ['required','string','max:10'],

                'tanque_cap_oper_valor'       => ['required','numeric','min:0'],
                'tanque_cap_oper_um'          => ['required','string','max:10'],

                'tanque_cap_util_valor'       => ['required','numeric','min:0'],
                'tanque_cap_util_um'          => ['required','string','max:10'],

                'tanque_cap_fondaje_valor'    => ['required','numeric','min:0'],
                'tanque_cap_fondaje_um'       => ['required','string','max:10'],

                'tanque_vol_min_oper_valor'   => ['required','numeric','min:0'],
                'tanque_vol_min_oper_um'      => ['required','string','max:10'],
            ]);
        }

        $item->update(array_merge($base, $tanque));

        return response()->json($item);
    }

    /**
     * DELETE /v1/flota-virtual/{id}
     */
    public function destroy($id)
    {
        $item = FlotaVirtual::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Eliminado']);
    }
}
