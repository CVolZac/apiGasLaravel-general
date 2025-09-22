<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BitacoraComercializador;

class BitacoraComercializadorController extends Controller
{
    // Listar todos
    public function index()
    {
        $bitacoras = BitacoraComercializador::all();
        return response()->json(['Bitacora' => $bitacoras]);
    }

    // Obtener uno
    public function show($id)
    {
        $registro = BitacoraComercializador::findOrFail($id);
        return response()->json($registro);
    }

    // Crear nuevo
    public function store(Request $request)
    {
        $request->validate([
            'FechaYHoraEvento' => 'required|date',
            'TipoEvento' => 'required|integer',
            'DescripcionEvento' => 'required|string|max:500',
        ]);

        $registro = BitacoraComercializador::create($request->all());

        return response()->json([
            'message' => 'Registro creado correctamente',
            'data' => $registro
        ], 201);
    }

    // Actualizar
    public function update(Request $request, $id)
    {
        $registro = BitacoraComercializador::findOrFail($id);

        $request->validate([
            'FechaYHoraEvento' => 'required|date',
            'TipoEvento' => 'required|integer',
            'DescripcionEvento' => 'required|string|max:500',
        ]);

        $registro->update($request->all());

        return response()->json([
            'message' => 'Registro actualizado correctamente',
            'data' => $registro
        ]);
    }

    // Eliminar
    public function destroy($id)
    {
        $registro = BitacoraComercializador::findOrFail($id);
        $registro->delete();

        return response()->json(['message' => 'Registro eliminado']);
    }
}
