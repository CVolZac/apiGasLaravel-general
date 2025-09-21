<?php

namespace App\Http\Controllers;

use App\Models\Complemento;
use Illuminate\Http\Request;

class ComplementoController extends Controller
{
    public function index() {
        return Complemento::with('evento')->paginate(50);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'evento_id' => 'required|exists:evento_tanque_virtual,id',
            'permiso_transporte' => 'nullable|string|max:100',
            'clave_vehiculo' => 'nullable|string|max:100',
            'tarifa_transporte' => 'nullable|numeric',
            'cargo_capacidad_trans' => 'nullable|numeric',
            'cargo_uso_trans' => 'nullable|numeric',
            'cargo_volumetrico_trans' => 'nullable|numeric',
            'trasvase_nombre' => 'nullable|string|max:255',
            'trasvase_rfc' => 'nullable|string|max:13',
            'trasvase_permiso' => 'nullable|string|max:100',
            'trasvase_descripcion' => 'nullable|string|max:255',
            'trasvase_cfdi' => 'nullable|uuid',
            'dictamen_rfc' => 'nullable|string|max:13',
            'dictamen_lote' => 'nullable|string|max:100',
            'dictamen_folio' => 'nullable|string|max:100',
            'dictamen_fecha' => 'nullable|date',
            'dictamen_resultado' => 'nullable|string|max:100',
            'certificado_rfc' => 'nullable|string|max:13',
            'certificado_folio' => 'nullable|string|max:100',
            'certificado_fecha' => 'nullable|date',
            'certificado_resultado' => 'nullable|string|max:100',
            'aclaracion_texto' => 'nullable|string',
        ]);
        return Complemento::create($data);
    }

    public function show(Complemento $complemento) {
        return $complemento->load('evento');
    }

    public function update(Request $request, Complemento $complemento) {
        $complemento->update($request->all());
        return $complemento;
    }

    public function destroy(Complemento $complemento) {
        $complemento->delete();
        return response()->noContent();
    }
}
