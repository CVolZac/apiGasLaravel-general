<?php

namespace App\Http\Controllers;

use App\Models\ServiciosComercializador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiciosComercializadoresController extends Controller
{
    public function index(Request $req)
    {
        $q = ServiciosComercializador::query()
            ->when($req->evento_id, fn($qq)=>$qq->where('evento_id',$req->evento_id))
            ->orderBy('tipo_servicio');

        return $q->get();
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'evento_id' => ['required','integer','exists:eventos_comercializacion,id'],
            'tipo_servicio' => ['required', Rule::in(['ALMACENAMIENTO','TRANSPORTE','LICUEFACCION','REGASIFICACION'])],
            'rfc_prestador' => ['nullable','string','max:13'],
            'permiso_prestador' => ['nullable','string','max:50'],
            'descripcion' => ['nullable','string','max:255'],
        ]);

        $row = ServiciosComercializador::create($data);
        return response()->json($row, 201);
    }

    public function show(ServiciosComercializador $servicios_comercializadore)
    {
        return $servicios_comercializadore;
    }

    public function update(Request $req, ServiciosComercializador $servicios_comercializadore)
    {
        $data = $req->validate([
            'tipo_servicio' => [Rule::in(['ALMACENAMIENTO','TRANSPORTE','LICUEFACCION','REGASIFICACION'])],
            'rfc_prestador' => ['nullable','string','max:13'],
            'permiso_prestador' => ['nullable','string','max:50'],
            'descripcion' => ['nullable','string','max:255'],
        ]);

        $servicios_comercializadore->update($data);
        return $servicios_comercializadore->refresh();
    }

    public function destroy(ServiciosComercializador $servicios_comercializadore)
    {
        $servicios_comercializadore->delete();
        return response()->noContent();
    }
}
