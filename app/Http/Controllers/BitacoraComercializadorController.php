<?php

namespace App\Http\Controllers;

use App\Models\BitacoraComercializador;
use Illuminate\Http\Request;

class BitacoraComercializadorController extends Controller
{
    public function index() {
        return BitacoraComercializador::latest()->paginate(50);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'entidad' => 'required|string|max:100',
            'entidad_id' => 'required|integer',
            'accion' => 'required|string|max:50',
            'usuario_id' => 'nullable|integer',
            'antes_json' => 'nullable|array',
            'despues_json' => 'nullable|array',
            'ip' => 'nullable|string|max:64',
            'user_agent' => 'nullable|string|max:500',
        ]);

        // permite arrays → json
        $data['antes_json'] = $data['antes_json'] ?? null;
        $data['despues_json'] = $data['despues_json'] ?? null;

        return BitacoraComercializador::create($data);
    }

    public function show(BitacoraComercializador $bitacoraComercializador) {
        return $bitacoraComercializador;
    }

    public function destroy(BitacoraComercializador $bitacoraComercializador) {
        $bitacoraComercializador->delete();
        return response()->noContent();
    }
}
