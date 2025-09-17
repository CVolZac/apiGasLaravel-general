<?php

namespace App\Http\Controllers;

use App\Models\ComplementoExtranjero;
use App\Models\EventoAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplementoExtranjeroController extends Controller
{
    public function show($eventoId)
    {
        $comp = ComplementoExtranjero::where('evento_id', $eventoId)->first();
        return $comp
            ? response()->json($comp, 200)
            : response()->json(['message' => 'No encontrado'], 404);
    }

    public function store(Request $request, $eventoId)
    {
        $data = $request->validate([
            'permiso_importacion' => ['nullable','string','max:100'],
        ]);

        EventoAlmacen::findOrFail($eventoId);

        try {
            $comp = DB::transaction(function () use ($data, $eventoId) {
                return ComplementoExtranjero::updateOrCreate(
                    ['evento_id' => $eventoId],
                    $data + ['evento_id' => $eventoId]
                );
            });

            return response()->json($comp, 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function update(Request $request, $eventoId)
    {
        $data = $request->validate([
            'permiso_importacion' => ['sometimes','nullable','string','max:100'],
        ]);

        $comp = ComplementoExtranjero::where('evento_id', $eventoId)->firstOrFail();

        try {
            $comp->update($data);
            return response()->json($comp, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function destroy($eventoId)
    {
        $comp = ComplementoExtranjero::where('evento_id', $eventoId)->first();
        if (!$comp) return response()->json(['deleted' => false], 200);

        try {
            $comp->delete();
            return response()->json(['deleted' => true], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
