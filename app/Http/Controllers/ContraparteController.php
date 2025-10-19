<?php

namespace App\Http\Controllers;

use App\Models\Contraparte;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ContraparteController extends Controller
{
    public function index(Request $request)
    {
        try {
            $q      = $request->get('q');
            $ambito = $request->get('ambito');
            $rol    = $request->get('rol');

            $query = Contraparte::query();

            if ($q) {
                $qLower = mb_strtolower($q, 'UTF-8');
                // Case-insensitive portable (Postgres/MySQL) sin depender de ILIKE
                $query->where(function ($w) use ($qLower) {
                    $w->whereRaw('LOWER(nombre) LIKE ?', ['%'.$qLower.'%'])
                      ->orWhereRaw('LOWER(rfc) LIKE ?',    ['%'.$qLower.'%']);
                });
            }

            if ($ambito) {
                $query->where('ambito', $ambito);
            }
            if ($rol) {
                $query->where('rol', $rol);
            }

            return $query->orderBy('id', 'desc')->paginate(25);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Error al listar contrapartes.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $this->validateData($request);
            $contraparte = Contraparte::create($data);
            return response()->json($contraparte, 201);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Error al crear contraparte.',
                'error'   => $e->getMessage(),
                'trace'   => config('app.debug') ? $e->getTrace() : null,
            ], 500);
        }
    }

    public function show(Contraparte $contraparte)
    {
        try {
            return $contraparte->loadCount('contratos');
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Error al obtener la contraparte.','error'=>$e->getMessage()], 500);
        }
    }

    public function update(Request $request, Contraparte $contraparte)
    {
        try {
            $data = $this->validateData($request, $contraparte->id);
            $contraparte->update($data);
            return $contraparte;
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Error al actualizar contraparte.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Contraparte $contraparte)
    {
        try {
            $contraparte->delete();
            return response()->json([], 204);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Error al eliminar contraparte.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function validateData(Request $request, $id = null): array
    {
        // RFC requerido si ámbito = nacional
        return $request->validate([
            'nombre'  => ['required', 'string', 'min:3'],
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
            'estatus' => ['nullable', Rule::in(['activo','inactivo'])],
        ]);
    }
}
