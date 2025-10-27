<?php

namespace App\Http\Controllers;

use App\Models\CfdisComercializador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CfdisComercializadoresController extends Controller
{
    public function index(Request $req)
    {
        $q = CfdisComercializador::query()
            ->when($req->evento_id, fn($qq)=>$qq->where('evento_id',$req->evento_id))
            ->when($req->tipo_cfdi, fn($qq)=>$qq->where('tipo_cfdi',$req->tipo_cfdi))
            ->orderByDesc('fecha_hora_cfdi');

        return $q->paginate($req->get('limit', 50));
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'evento_id' => ['required','integer','exists:eventos_comercializacion,id'],
            'uuid' => ['nullable','string','max:40','unique:cfdis_comercializadores,uuid'],
            'tipo_cfdi' => ['required', Rule::in(['I','E','T','P'])],
            'fecha_hora_cfdi' => ['required','date'],
            'volumen_documentado_valor' => ['required','numeric'],
            'volumen_documentado_um' => ['required', Rule::in(['UM01','UM03','UM04'])],
            'precio' => ['nullable','numeric'],
            'contraprestacion' => ['nullable','numeric'],
            'monto_total' => ['nullable','numeric'],
            'rfc_emisor' => ['nullable','string','max:13'],
            'rfc_receptor' => ['nullable','string','max:13'],
        ]);

        $row = CfdisComercializador::create($data);
        return response()->json($row, 201);
    }

    public function show(CfdisComercializador $cfdis_comercializadore)
    {
        return $cfdis_comercializadore;
    }

    public function update(Request $req, CfdisComercializador $cfdis_comercializadore)
    {
        $data = $req->validate([
            'uuid' => ['nullable','string','max:40', Rule::unique('cfdis_comercializadores','uuid')->ignore($cfdis_comercializadore->id)],
            'tipo_cfdi' => [Rule::in(['I','E','T','P'])],
            'fecha_hora_cfdi' => ['date'],
            'volumen_documentado_valor' => ['numeric'],
            'volumen_documentado_um' => [Rule::in(['UM01','UM03','UM04'])],
            'precio' => ['nullable','numeric'],
            'contraprestacion' => ['nullable','numeric'],
            'monto_total' => ['nullable','numeric'],
            'rfc_emisor' => ['nullable','string','max:13'],
            'rfc_receptor' => ['nullable','string','max:13'],
        ]);

        $cfdis_comercializadore->update($data);
        return $cfdis_comercializadore->refresh();
    }

    public function destroy(CfdisComercializador $cfdis_comercializadore)
    {
        $cfdis_comercializadore->delete();
        return response()->noContent();
    }
}
