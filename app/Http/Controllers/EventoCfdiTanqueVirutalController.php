<?php

namespace App\Http\Controllers;

use App\Models\EventoCfdiTanqueVirutal;
use Illuminate\Http\Request;

class EventoCfdiTanqueVirutalController extends Controller
{
    public function index() {
        return EventoCfdiTanqueVirutal::with('evento')->paginate(50);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'evento_id' => 'required|exists:evento_tanque_virtual,id',
            'uuid' => 'required|uuid',
            'tipo_cfdi' => 'nullable|in:Ingreso,Egreso,Traslado',
            'precio' => 'nullable|numeric',
            'fecha_hora_transaccion' => 'nullable|date',
            'vol_doc_valor' => 'nullable|numeric',
            'vol_doc_um' => 'nullable|string|max:20',
        ]);
        return EventoCfdiTanqueVirutal::create($data);
    }

    public function show(EventoCfdiTanqueVirutal $eventoCfdiTanqueVirutal) {
        return $eventoCfdiTanqueVirutal->load('evento');
    }

    public function update(Request $request, EventoCfdiTanqueVirutal $eventoCfdiTanqueVirutal) {
        $eventoCfdiTanqueVirutal->update($request->all());
        return $eventoCfdiTanqueVirutal;
    }

    public function destroy(EventoCfdiTanqueVirutal $eventoCfdiTanqueVirutal) {
        $eventoCfdiTanqueVirutal->delete();
        return response()->noContent();
    }
}
