<?php

namespace App\Http\Controllers;

use App\Models\EventoTanqueVirtual;
use Illuminate\Http\Request;

class EventoTanqueVirtualController extends Controller
{
    public function index() {
        return EventoTanqueVirtual::with(['tanque','cfdis','complementos'])->paginate(50);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'tanque_virtual_id' => 'required|exists:tanque_virtual,id',
            'tipo_evento' => 'required|in:recepcion,entrega,existencia',
            'fecha_hora' => 'required|date',
            'volumen_valor' => 'required|numeric',
            'volumen_um' => 'required|string|max:20',
            'es_cierre_diario' => 'boolean',
            'es_consolidado_mensual' => 'boolean',
            'observaciones' => 'nullable|string|max:500',
            'geo_lat' => 'nullable|numeric',
            'geo_lng' => 'nullable|numeric',
        ]);
        return EventoTanqueVirtual::create($data);
    }

    public function show(EventoTanqueVirtual $eventoTanqueVirtual) {
        return $eventoTanqueVirtual->load(['tanque','cfdis','complementos']);
    }

    public function update(Request $request, EventoTanqueVirtual $eventoTanqueVirtual) {
        $eventoTanqueVirtual->update($request->all());
        return $eventoTanqueVirtual;
    }

    public function destroy(EventoTanqueVirtual $eventoTanqueVirtual) {
        $eventoTanqueVirtual->delete();
        return response()->noContent();
    }
}
