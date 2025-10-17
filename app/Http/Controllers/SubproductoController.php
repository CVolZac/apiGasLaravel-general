<?php

namespace App\Http\Controllers;

use App\Models\Subproducto;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubproductoController extends Controller
{
    // GET /v1/subproductos/{idPlanta}
    public function index($idPlanta)
    {
        $q = Subproducto::with('producto:id,clave_producto,nombre')
            ->where('id_planta', $idPlanta)
            ->orderBy('nombre_subproducto');

        if ($s = request('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('clave_subproducto', 'like', "%$s%")
                  ->orWhere('nombre_subproducto', 'like', "%$s%");
            });
        }
        if (!is_null(request('activo'))) {
            $q->where('activo', (bool) request('activo'));
        }

        return response()->json($q->paginate(request('per_page', 10)));
    }

    // GET /v1/subproductos/{idPlanta}/{id}
    public function show($idPlanta, $id)
    {
        $item = Subproducto::where('id_planta', $idPlanta)->with('producto')->findOrFail($id);
        return response()->json($item);
    }

    // POST /v1/subproductos
    public function store(Request $r)
    {
        $data = $r->validate([
            'id_planta'         => ['required','integer'],
            'producto_id'       => ['required','integer','exists:productos,id'],
            'clave_subproducto' => ['required','regex:/^SP[A-Z0-9]{2,5}$/',
                Rule::unique('subproductos')->where(fn($q)=>$q->where('producto_id',$r->producto_id))],
            'nombre_subproducto'=> ['required','string','max:150'],
            'unidad_medida'     => ['required','string','max:20'],
            'octanaje'          => ['nullable','integer','between:0,110'],
            'composicion'       => ['nullable','string','max:255'],
            'activo'            => ['boolean'],
        ]);

        // validar que producto pertenezca a misma planta (si aplica)
        $producto = Producto::where('id_planta', $r->id_planta)->findOrFail($r->producto_id);

        $item = Subproducto::create($data);
        return response()->json(['message'=>'Creado','data'=>$item->load('producto')], 201);
    }

    // POST /v1/subproductos/{id}
    public function update(Request $r, $id)
    {
        $item = Subproducto::findOrFail($id);

        $data = $r->validate([
            'id_planta'         => ['required','integer'],
            'producto_id'       => ['required','integer','exists:productos,id'],
            'clave_subproducto' => ['required','regex:/^SP[A-Z0-9]{2,5}$/',
                Rule::unique('subproductos')->ignore($item->id)->where(fn($q)=>$q->where('producto_id',$r->producto_id))],
            'nombre_subproducto'=> ['required','string','max:150'],
            'unidad_medida'     => ['required','string','max:20'],
            'octanaje'          => ['nullable','integer','between:0,110'],
            'composicion'       => ['nullable','string','max:255'],
            'activo'            => ['boolean'],
        ]);

        // coherencia planta-producto
        Producto::where('id_planta', $r->id_planta)->findOrFail($r->producto_id);

        $item->update($data);
        return response()->json(['message'=>'Actualizado','data'=>$item->load('producto')]);
    }
}
