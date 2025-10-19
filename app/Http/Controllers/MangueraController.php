<?php

namespace App\Http\Controllers;

use App\Models\Manguera;
use App\Models\Dispensario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MangueraController extends Controller
{
    // GET v1/mangueras/dispensario/{idDisp}
    public function indexByDispensario($idDisp)
    {
        // Verifica existencia del dispensario (opcional)
        Dispensario::findOrFail($idDisp);

        $data = Manguera::where('id_dispensario', $idDisp)
            ->orderBy('id','desc')
            ->get();

        return response()->json($data);
    }

    // GET v1/mangueras/dispensario/{idDisp}/{id}
    public function show($idDisp, $id)
    {
        $m = Manguera::where('id_dispensario', $idDisp)->where('id', $id)->firstOrFail();
        return response()->json($m);
    }

    // POST v1/mangueras
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_dispensario' => ['required','integer','exists:dispensarios,id'],
            'identificador_manguera' => [
                'required','string','max:80',
                Rule::unique('mangueras')->where(fn($q)=>$q->where('id_dispensario', $request->id_dispensario))
            ],
            'estado' => ['required','in:O,F'],
        ]);

        $m = Manguera::create($validated);

        return response()->json([
            'message' => 'Manguera creada',
            'id' => $m->id,
            'data' => $m
        ], 201);
    }

    // POST v1/mangueras/{id}
    public function update($id, Request $request)
    {
        $m = Manguera::findOrFail($id);

        $validated = $request->validate([
            'id_dispensario' => ['required','integer','exists:dispensarios,id'],
            'identificador_manguera' => [
                'required','string','max:80',
                Rule::unique('mangueras')->ignore($m->id)->where(fn($q)=>$q->where('id_dispensario', $request->id_dispensario))
            ],
            'estado' => ['required','in:O,F'],
        ]);

        $m->update($validated);

        return response()->json(['message' => 'Manguera actualizada', 'data' => $m]);
    }

    // DELETE v1/mangueras/{id}
    public function destroy($id)
    {
        $m = Manguera::findOrFail($id);
        $m->delete();

        return response()->json(['message' => 'Manguera eliminada']);
    }
}
