<?php

namespace App\Http\Controllers;

use App\Models\TanqueVirtual;
use Illuminate\Http\Request;

class TanqueVirtualController extends Controller
{
    public function index() {
        return TanqueVirtual::with('instalacion')->paginate(50);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'instalacion_id' => 'required|exists:comercializador_instalacion,id',
            'clave_identificacion_tanque' => 'required|string|max:100',
            'producto_clave' => 'required|string|max:20',
            'subproducto_clave' => 'nullable|string|max:20',
            'cap_total' => 'nullable|numeric',
            'cap_operativa' => 'nullable|numeric',
            'cap_util' => 'nullable|numeric',
            'cap_fondaje' => 'nullable|numeric',
            'cap_gas_talon' => 'nullable|numeric',
            'vol_min_operacion' => 'nullable|numeric',
            'estado' => 'nullable|in:activo,inactivo',
            'es_virtual' => 'boolean',
            'vigencia_calibracion' => 'nullable|date',
            'localizacion_descripcion' => 'nullable|string|max:255',
        ]);
        return TanqueVirtual::create($data);
    }

    public function show(TanqueVirtual $tanqueVirtual) {
        return $tanqueVirtual->load(['instalacion','eventos']);
    }

    public function update(Request $request, TanqueVirtual $tanqueVirtual) {
        $tanqueVirtual->update($request->all());
        return $tanqueVirtual;
    }

    public function destroy(TanqueVirtual $tanqueVirtual) {
        $tanqueVirtual->delete();
        return response()->noContent();
    }
}
