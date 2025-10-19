<?php

namespace App\Http\Controllers;

use App\Models\TotalizadorMangueraDia;
use App\Models\Manguera;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CortesExpendioController extends Controller
{
    // GET v1/cortes-expendio/{idPlanta}/{fecha}?idDispensario=&idManguera=
    public function indexByFecha($idPlanta, $fecha, Request $request)
    {
        $q = TotalizadorMangueraDia::query()
            ->whereDate('fecha', $fecha)
            ->whereHas('manguera', function($qq) use ($idPlanta, $request) {
                $qq->whereHas('dispensario', fn($d)=>$d->where('id_planta', $idPlanta));
                if ($request->filled('idDispensario')) {
                    $qq->where('id_dispensario', $request->idDispensario);
                }
                if ($request->filled('idManguera')) {
                    $qq->where('id', $request->idManguera);
                }
            })
            ->with(['manguera' => function($m){
                $m->select('id','id_dispensario','identificador_manguera','id_subproducto')
                  ->with(['dispensario:id,clave_dispensario']);
            }])
            ->orderBy('id','desc');

        $rows = $q->get()->map(function($r){
            // ayuda para la UI
            $r->manguera->subproducto_desc = $r->manguera->id_subproducto ?? null;
            return $r;
        });

        return response()->json($rows);
    }

    // POST v1/cortes-expendio
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha' => ['required','date'],
            'id_manguera' => ['required','integer','exists:mangueras,id'],
            'id_subproducto' => ['nullable','integer'],
            'totalizador_inicial_dia' => ['required','numeric','min:0'],
            'totalizador_final_dia' => ['required','numeric','min:0','gte:totalizador_inicial_dia'],
            'volumen_entregado_dia' => ['required','numeric','min:0'],
            'observaciones' => ['nullable','string'],
        ]);

        // evita duplicados
        $exists = TotalizadorMangueraDia::where('id_manguera', $validated['id_manguera'])
            ->whereDate('fecha', $validated['fecha'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Ya existe un corte para esa manguera y fecha'], 422);
        }

        $row = TotalizadorMangueraDia::create($validated);

        return response()->json(['message'=>'Corte guardado','id'=>$row->id,'data'=>$row], 201);
    }

    // POST v1/cortes-expendio/{id}
    public function update($id, Request $request)
    {
        $row = TotalizadorMangueraDia::findOrFail($id);

        $validated = $request->validate([
            'fecha' => ['required','date'],
            'id_manguera' => ['required','integer','exists:mangueras,id'],
            'id_subproducto' => ['nullable','integer'],
            'totalizador_inicial_dia' => ['required','numeric','min:0'],
            'totalizador_final_dia' => ['required','numeric','min:0','gte:totalizador_inicial_dia'],
            'volumen_entregado_dia' => ['required','numeric','min:0'],
            'observaciones' => ['nullable','string'],
        ]);

        // enforce unicidad (manguera+fecha)
        $conflict = TotalizadorMangueraDia::where('id_manguera', $validated['id_manguera'])
            ->whereDate('fecha', $validated['fecha'])
            ->where('id', '!=', $row->id)
            ->exists();
        if ($conflict) {
            return response()->json(['message' => 'Conflicto: ya existe otro corte para esa manguera y fecha'], 422);
        }

        $row->update($validated);
        return response()->json(['message'=>'Corte actualizado','data'=>$row]);
    }

    // DELETE v1/cortes-expendio/{id}
    public function destroy($id)
    {
        $row = TotalizadorMangueraDia::findOrFail($id);
        $row->delete();
        return response()->json(['message'=>'Corte eliminado']);
    }

    // GET v1/cortes-expendio/masivo-base/{idPlanta}/{fecha}?idDispensario=...
    public function masivoBase($idPlanta, $fecha, Request $request)
    {
        $request->validate(['idDispensario' => ['required','integer']]);
        $idDisp = (int)$request->idDispensario;

        $mangueras = Manguera::where('id_dispensario', $idDisp)
            ->whereHas('dispensario', fn($d)=>$d->where('id_planta',$idPlanta))
            ->get(['id','id_dispensario','identificador_manguera','id_subproducto']);

        // Jala cortes existentes del día para prellenar
        $cortes = TotalizadorMangueraDia::whereIn('id_manguera', $mangueras->pluck('id'))
            ->whereDate('fecha', $fecha)->get()->keyBy('id_manguera');

        $rows = $mangueras->map(function($m) use ($cortes){
            $c = $cortes->get($m->id);
            return [
                'id_manguera' => $m->id,
                'identificador_manguera' => $m->identificador_manguera,
                'id_subproducto' => $m->id_subproducto,
                'subproducto_desc' => $m->id_subproducto, // si manejas catálogo, aquí pones el texto
                'totalizador_inicial_dia' => $c->totalizador_inicial_dia ?? null,
                'totalizador_final_dia'   => $c->totalizador_final_dia ?? null,
                'volumen'                 => $c->volumen_entregado_dia ?? null,
                'observaciones'           => $c->observaciones ?? null,
            ];
        });

        return response()->json($rows->values());
    }

    // POST v1/cortes-expendio/masivo  body: { registros: [] }
    public function storeMasivo(Request $request)
    {
        $validated = $request->validate([
            'registros' => ['required','array','min:1'],
            'registros.*.fecha' => ['required','date'],
            'registros.*.id_manguera' => ['required','integer','exists:mangueras,id'],
            'registros.*.id_subproducto' => ['nullable','integer'],
            'registros.*.totalizador_inicial_dia' => ['required','numeric','min:0'],
            'registros.*.totalizador_final_dia' => ['required','numeric','min:0','gte:registros.*.totalizador_inicial_dia'],
            'registros.*.volumen_entregado_dia' => ['required','numeric','min:0'],
            'registros.*.observaciones' => ['nullable','string'],
        ]);

        $created = 0; $updated = 0;

        foreach ($validated['registros'] as $r) {
            $row = TotalizadorMangueraDia::firstOrNew([
                'id_manguera' => $r['id_manguera'],
                'fecha'       => $r['fecha'],
            ]);
            $row->fill([
                'id_subproducto'           => $r['id_subproducto'] ?? null,
                'totalizador_inicial_dia'  => $r['totalizador_inicial_dia'],
                'totalizador_final_dia'    => $r['totalizador_final_dia'],
                'volumen_entregado_dia'    => $r['volumen_entregado_dia'],
                'observaciones'            => $r['observaciones'] ?? null,
            ]);
            $row->exists ? $updated++ : $created++;
            $row->save();
        }

        return response()->json(['message'=>'Masivo procesado','created'=>$created,'updated'=>$updated]);
    }
}
