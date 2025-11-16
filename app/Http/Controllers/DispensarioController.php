<?php

namespace App\Http\Controllers;

use App\Models\Dispensario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DispensarioController extends Controller
{
    // GET v1/dispensarios/{idPlanta}
    public function index($idPlanta)
    {
        $data = Dispensario::where('id_planta', $idPlanta)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($data);
    }

    // GET v1/dispensarios/{idPlanta}/{id}
    public function show($idPlanta, $id)
    {
        $disp = Dispensario::where('id_planta', $idPlanta)->where('id', $id)->firstOrFail();
        return response()->json($disp);
    }

    // POST v1/dispensarios
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_planta' => ['required', 'integer'],
            'clave_dispensario' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dispensarios')->where(fn($q) => $q->where('id_planta', $request->id_planta))
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],

            // 🔥 Nuevo campo
            'numero_permiso_textual' => ['nullable', 'string', 'max:100'],
        ]);

        $disp = Dispensario::create($validated);

        return response()->json([
            'message' => 'Dispensario creado',
            'id' => $disp->id,
            'data' => $disp
        ], 201);
    }

    // POST v1/dispensarios/{id}
    public function update($id, Request $request)
    {
        $disp = Dispensario::findOrFail($id);

        $validated = $request->validate([
            'id_planta' => ['required', 'integer'],
            'clave_dispensario' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dispensarios')
                    ->ignore($disp->id)
                    ->where(fn($q) => $q->where('id_planta', $request->id_planta))
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],

            'numero_permiso_textual' => ['nullable', 'string', 'max:100'],
        ]);

        $disp->update($validated);

        return response()->json(['message' => 'Dispensario actualizado', 'data' => $disp]);
    }

    // DELETE v1/dispensarios/{id}
    public function destroy($id)
    {
        $disp = Dispensario::findOrFail($id);
        $disp->delete();

        return response()->json(['message' => 'Dispensario eliminado']);
    }
}
