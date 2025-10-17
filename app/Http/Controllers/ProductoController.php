<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    // GET /v1/productos/{idPlanta}
    public function index($idPlanta)
    {
        $q = Producto::where('id_planta', $idPlanta)
            ->orderBy('nombre');

        // filtros opcionales ?search=&activo=
        if ($s = request('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('clave_producto', 'like', "%$s%")
                  ->orWhere('nombre', 'like', "%$s%")
                  ->orWhere('descripcion', 'like', "%$s%");
            });
        }
        if (!is_null(request('activo'))) {
            $q->where('activo', (bool) request('activo'));
        }

        return response()->json($q->paginate(request('per_page', 10)));
    }

    // GET /v1/productos/{idPlanta}/{id}
    public function show($idPlanta, $id)
    {
        $item = Producto::where('id_planta', $idPlanta)->findOrFail($id);
        $item->load('subproductos');
        return response()->json($item);
    }

    // POST /v1/productos
    public function store(Request $r)
    {
        $data = $r->validate([
            'id_planta'       => ['required','integer'],
            'clave_producto'  => ['required','regex:/^PR[0-9]{2}$/',
                                  Rule::unique('productos')->where(fn($q)=>$q->where('id_planta',$r->id_planta))],
            'nombre'          => ['required','string','max:120'],
            'unidad_medida'   => ['required','string','max:20'],
            'tipo_combustible'=> ['required','string','max:30'],
            'descripcion'     => ['nullable','string','max:255'],
            'activo'          => ['boolean'],
        ]);

        $item = Producto::create($data);
        return response()->json(['message'=>'Creado','data'=>$item], 201);
    }

    // POST /v1/productos/{id}
    public function update(Request $r, $id)
    {
        $item = Producto::findOrFail($id);

        $data = $r->validate([
            'id_planta'       => ['required','integer'],
            'clave_producto'  => ['required','regex:/^PR[0-9]{2}$/',
                                  Rule::unique('productos')->ignore($item->id)->where(fn($q)=>$q->where('id_planta',$r->id_planta))],
            'nombre'          => ['required','string','max:120'],
            'unidad_medida'   => ['required','string','max:20'],
            'tipo_combustible'=> ['required','string','max:30'],
            'descripcion'     => ['nullable','string','max:255'],
            'activo'          => ['boolean'],
        ]);

        $item->update($data);
        return response()->json(['message'=>'Actualizado','data'=>$item]);
    }
}
