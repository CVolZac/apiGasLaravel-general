<?php

namespace App\Http\Controllers;

use App\Models\ComercializadorInstalacion;
use Illuminate\Http\Request;

class ComercializadorInstalacionController extends Controller
{
    public function index() {
        return ComercializadorInstalacion::paginate(50);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'rfc_contribuyente' => 'required|string|max:13',
            'rfc_representante_legal' => 'nullable|string|max:13',
            'caracter' => 'nullable|string|max:50',
            'modalidad_permiso' => 'nullable|string|max:100',
            'num_permiso' => 'nullable|string|max:100',
            'clave_instalacion' => 'nullable|string|max:100',
            'descripcion_instalacion' => 'nullable|string|max:255',
            'geolat' => 'nullable|numeric',
            'geolng' => 'nullable|numeric',
        ]);
        return ComercializadorInstalacion::create($data);
    }

    public function show(ComercializadorInstalacion $comercializadorInstalacion) {
        return $comercializadorInstalacion->load('tanques');
    }

    public function update(Request $request, ComercializadorInstalacion $comercializadorInstalacion) {
        $comercializadorInstalacion->update($request->all());
        return $comercializadorInstalacion;
    }

    public function destroy(ComercializadorInstalacion $comercializadorInstalacion) {
        $comercializadorInstalacion->delete();
        return response()->noContent();
    }
}
