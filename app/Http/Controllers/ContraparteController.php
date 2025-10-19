<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contraparte;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContraparteController extends Controller
{
    public function index(Request $request)
    {
        $q      = $request->get('q');
        $ambito = $request->get('ambito');
        $rol    = $request->get('rol');

        $query = Contraparte::query();

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'ILIKE', "%{$q}%")
                  ->orWhere('rfc', 'ILIKE', "%{$q}%");
            });
        }
        if ($ambito) {
            $query->where('ambito', $ambito);
        }
        if ($rol) {
            $query->where('rol', $rol);
        }

        return $query->orderBy('id', 'desc')->paginate(25);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $contraparte = Contraparte::create($data);
        return response()->json($contraparte, 201);
    }

    public function show(Contraparte $contraparte)
    {
        return $contraparte->loadCount('contratos');
    }

    public function update(Request $request, Contraparte $contraparte)
    {
        $data = $this->validateData($request, $contraparte->id);
        $contraparte->update($data);
        return $contraparte;
    }

    public function destroy(Contraparte $contraparte)
    {
        // Si quieres impedir borrar con contratos relacionados, cambia a ->withCount('contratos') y valida
        $contraparte->delete();
        return response()->json([], 204);
    }

    private function validateData(Request $request, $id = null): array
    {
        // RFC: requerido si ambito = nacional
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
