<?php

namespace App\Http\Controllers;

use App\Models\PedimentosComercializador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PedimentosComercializadoresController extends Controller
{
    public function index(Request $req)
    {
        $q = PedimentosComercializador::query()
            ->when($req->evento_id, fn($qq)=>$qq->where('evento_id',$req->evento_id))
            ->orderByDesc('id');

        return $q->paginate($req->get('limit', 50));
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'evento_id' => ['required','integer','exists:eventos_comercializacion,id'],
            'numero_pedimento' => ['required','string','max:30'],
            'incoterm' => ['nullable','string','max:10'],
            'medio_trans_aduana' => ['nullable','string','max:5'],
            'precio_import_export' => ['nullable','numeric'],
            'volumen_documentado_valor' => ['required','numeric'],
            'volumen_documentado_um' => ['required', Rule::in(['UM01','UM03','UM04'])],
            'pais_origen_destino' => ['required','string','size:3'],
            'punto_internacion_extraccion' => ['required','string','max:20'],
        ]);

        $row = PedimentosComercializador::create($data);
        return response()->json($row, 201);
    }

    public function show(PedimentosComercializador $pedimentos_comercializadore)
    {
        return $pedimentos_comercializadore;
    }

    public function update(Request $req, PedimentosComercializador $pedimentos_comercializadore)
    {
        $data = $req->validate([
            'numero_pedimento' => ['string','max:30'],
            'incoterm' => ['nullable','string','max:10'],
            'medio_trans_aduana' => ['nullable','string','max:5'],
            'precio_import_export' => ['nullable','numeric'],
            'volumen_documentado_valor' => ['numeric'],
            'volumen_documentado_um' => [Rule::in(['UM01','UM03','UM04'])],
            'pais_origen_destino' => ['string','size:3'],
            'punto_internacion_extraccion' => ['string','max:20'],
        ]);

        $pedimentos_comercializadore->update($data);
        return $pedimentos_comercializadore->refresh();
    }

    public function destroy(PedimentosComercializador $pedimentos_comercializadore)
    {
        $pedimentos_comercializadore->delete();
        return response()->noContent();
    }
}
