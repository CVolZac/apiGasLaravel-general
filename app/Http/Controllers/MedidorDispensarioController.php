<?php

namespace App\Http\Controllers;

use App\Models\MedidorDispensario;
use App\Models\Dispensario;
use Illuminate\Http\Request;

class MedidorDispensarioController extends Controller
{
    // GET v1/medidores/dispensario/{idDisp}
    public function indexByDispensario($idDisp)
    {
        Dispensario::findOrFail($idDisp);

        $data = MedidorDispensario::where('id_dispensario', $idDisp)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($data);
    }

    // GET v1/medidores/dispensario/{idDisp}/{id}
    public function show($idDisp, $id)
    {
        $d = MedidorDispensario::where('id_dispensario', $idDisp)->where('id', $id)->firstOrFail();
        return response()->json($d);
    }

    // POST v1/medidores
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_dispensario' => ['required','integer','exists:dispensarios,id'],
            'sistema_medicion_dispensario' => ['required','string','max:80'],
            'descripcion_medidor' => ['nullable','string'],
            'vigencia_calibracion' => ['nullable','date'],
            'incertidumbre_medicion' => ['nullable','numeric'],
        ]);

        $d = MedidorDispensario::create($validated);

        return response()->json([
            'message' => 'Medidor creado',
            'id' => $d->id,
            'data' => $d
        ], 201);
    }

    // POST v1/medidores/{id}
    public function update($id, Request $request)
    {
        $d = MedidorDispensario::findOrFail($id);

        $validated = $request->validate([
            'id_dispensario' => ['required','integer','exists:dispensarios,id'],
            'sistema_medicion_dispensario' => ['required','string','max:80'],
            'descripcion_medidor' => ['nullable','string'],
            'vigencia_calibracion' => ['nullable','date'],
            'incertidumbre_medicion' => ['nullable','numeric'],
        ]);

        $d->update($validated);

        return response()->json(['message' => 'Medidor actualizado', 'data' => $d]);
    }

    // DELETE v1/medidores/{id}
    public function destroy($id)
    {
        $d = MedidorDispensario::findOrFail($id);
        $d->delete();

        return response()->json(['message' => 'Medidor eliminado']);
    }
}
