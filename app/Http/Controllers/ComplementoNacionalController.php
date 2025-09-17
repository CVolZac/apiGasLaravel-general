<?php

namespace App\Http\Controllers;

use App\Models\ComplementoNacional;
use App\Models\EventoAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplementoNacionalController extends Controller
{
    public function show($eventoId)
    {
        $comp = ComplementoNacional::where('evento_id', $eventoId)->first();
        return $comp
            ? response()->json($comp, 200)
            : response()->json(['message' => 'No encontrado'], 404);
    }

    public function store(Request $request, $eventoId)
    {
        $data = $request->validate([
            'rfc_proveedor'     => ['nullable','string','max:13'],
            'nombre_proveedor'  => ['nullable','string','max:255'],
            'permiso_proveedor' => ['nullable','string','max:100'],
        ]);

        // Validar existencia del evento
        EventoAlmacen::findOrFail($eventoId);

        try {
            $comp = DB::transaction(function () use ($data, $eventoId) {
                // upsert por evento_id (1:1)
                $comp = ComplementoNacional::updateOrCreate(
                    ['evento_id' => $eventoId],
                    $data + ['evento_id' => $eventoId]
                );
                return $comp;
            });

            return response()->json($comp, 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function update(Request $request, $eventoId)
    {
        $data = $request->validate([
            'rfc_proveedor'     => ['sometimes','nullable','string','max:13'],
            'nombre_proveedor'  => ['sometimes','nullable','string','max:255'],
            'permiso_proveedor' => ['sometimes','nullable','string','max:100'],
        ]);

        $comp = ComplementoNacional::where('evento_id', $eventoId)->firstOrFail();

        try {
            $comp->update($data);
            return response()->json($comp, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function destroy($eventoId)
    {
        $comp = ComplementoNacional::where('evento_id', $eventoId)->first();
        if (!$comp) return response()->json(['deleted' => false], 200);

        try {
            $comp->delete();
            return response()->json(['deleted' => true], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
