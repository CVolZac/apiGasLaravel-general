<?php

namespace App\Http\Controllers;

use App\Models\ComplementoTransporte;
use App\Models\EventoAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplementoTransporteController extends Controller
{
    public function show($eventoId)
    {
        $comp = ComplementoTransporte::where('evento_id', $eventoId)->first();
        return $comp
            ? response()->json($comp, 200)
            : response()->json(['message' => 'No encontrado'], 404);
    }

    public function store(Request $request, $eventoId)
    {
        $data = $request->validate([
            'permiso'           => ['required','string','max:100'],
            'clave_vehiculo'    => ['nullable','string','max:100'],
            'tarifa'            => ['nullable','numeric'],
            'cargo_capacidad'   => ['nullable','numeric'],
            'cargo_uso'         => ['nullable','numeric'],
            'cargo_volumetrico' => ['nullable','numeric'],
        ]);

        EventoAlmacen::findOrFail($eventoId);

        try {
            $comp = DB::transaction(function () use ($data, $eventoId) {
                return ComplementoTransporte::updateOrCreate(
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
            'permiso'           => ['sometimes','string','max:100'],
            'clave_vehiculo'    => ['sometimes','nullable','string','max:100'],
            'tarifa'            => ['sometimes','nullable','numeric'],
            'cargo_capacidad'   => ['sometimes','nullable','numeric'],
            'cargo_uso'         => ['sometimes','nullable','numeric'],
            'cargo_volumetrico' => ['sometimes','nullable','numeric'],
        ]);

        $comp = ComplementoTransporte::where('evento_id', $eventoId)->firstOrFail();

        try {
            $comp->update($data);
            return response()->json($comp, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function destroy($eventoId)
    {
        $comp = ComplementoTransporte::where('evento_id', $eventoId)->first();
        if (!$comp) return response()->json(['deleted' => false], 200);

        try {
            $comp->delete();
            return response()->json(['deleted' => true], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
