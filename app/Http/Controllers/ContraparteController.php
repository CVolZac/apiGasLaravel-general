<?php

namespace App\Http\Controllers;

use App\Models\Contraparte;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContraparteController extends Controller
{
    // GET /v1/contrapartes?q=&ambito=&rol=
    public function index(Request $request)
    {
        $q      = $request->get('q');
        $ambito = $request->get('ambito');
        $rol    = $request->get('rol');

        $query = Contraparte::query();

        // Postgres: búsqueda case-insensitive portable
        if ($q) {
            $qLower = mb_strtolower($q, 'UTF-8');
            $query->where(function ($w) use ($qLower) {
                $w->whereRaw('LOWER(nombre) LIKE ?', ['%'.$qLower.'%'])
                  ->orWhereRaw('LOWER(rfc) LIKE ?',    ['%'.$qLower.'%']);
            });
        }
        if ($ambito) $query->where('ambito', $ambito);
        if ($rol)    $query->where('rol', $rol);

        $data = $query->orderBy('id', 'desc')->paginate(25);
        return response()->json($data);
    }

    // GET /v1/contrapartes/{id}
    public function show($id)
    {
        $c = Contraparte::withCount('contratos')->findOrFail($id);
        return response()->json($c);
    }

    // POST /v1/contrapartes
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'  => ['required','string','min:3'],
            'ambito'  => ['required', Rule::in(['nacional','extranjero'])],
            'rol'     => ['required', Rule::in(['cliente','proveedor','mixto'])],
            'rfc'     => [
                Rule::requiredIf(fn() => $request->ambito === 'nacional'),
                'nullable',
                'regex:/^([A-ZÑ&]{3,4})(\d{2})(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[A-Z\d]{3}$/i'
            ],
            'permiso_contraparte' => ['nullable','string','max:100'],
            'pais'    => ['required','string','max:3'],
            'correo'  => ['nullable','email'],
            'telefono'=> ['nullable','string','max:30'],
            'punto_internacion' => ['nullable','string','max:150'],
            'medio_aduana'      => ['nullable','string','max:150'],
            'observaciones'     => ['nullable','string'],
            'estatus'           => ['nullable', Rule::in(['activo','inactivo'])],
        ]);

        $c = Contraparte::create($validated);

        return response()->json([
            'message' => 'Contraparte creada',
            'id' => $c->id,
            'data' => $c
        ], 201);
    }

    // POST /v1/contrapartes/{id}
    public function update($id, Request $request)
    {
        $c = Contraparte::findOrFail($id);

        $validated = $request->validate([
            'nombre'  => ['required','string','min:3'],
            'ambito'  => ['required', Rule::in(['nacional','extranjero'])],
            'rol'     => ['required', Rule::in(['cliente','proveedor','mixto'])],
            'rfc'     => [
                Rule::requiredIf(fn() => $request->ambito === 'nacional'),
                'nullable',
                'regex:/^([A-ZÑ&]{3,4})(\d{2})(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[A-Z\d]{3}$/i'
            ],
            'permiso_contraparte' => ['nullable','string','max:100'],
            'pais'    => ['required','string','max:3'],
            'correo'  => ['nullable','email'],
            'telefono'=> ['nullable','string','max:30'],
            'punto_internacion' => ['nullable','string','max:150'],
            'medio_aduana'      => ['nullable','string','max:150'],
            'observaciones'     => ['nullable','string'],
            'estatus'           => ['nullable', Rule::in(['activo','inactivo'])],
        ]);

        $c->update($validated);

        return response()->json([
            'message' => 'Contraparte actualizada',
            'data' => $c
        ]);
    }

    // (Opcional)
    public function destroy($id)
    {
        $c = Contraparte::findOrFail($id);
        $c->delete();
        return response()->json(['message' => 'Contraparte eliminada']);
    }
}
