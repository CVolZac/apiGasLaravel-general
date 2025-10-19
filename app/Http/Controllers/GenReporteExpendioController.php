<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\InformacionGeneralReporte;
use App\Models\Dispensario;
use App\Models\Manguera;
use App\Models\TotalizadorMangueraDia;    // ✅ Reemplaza a CorteExpendio
// use App\Models\AjusteTotalizadorManguera; // <- Opcional: si no existe el modelo, se maneja con class_exists
use App\Models\BitacoraDispensario;
use App\Models\Subproducto;

class GenReporteExpendioController extends Controller
{
    public function generarReporte($idPlanta, $yearMonth, $tipoDM)
    {
        date_default_timezone_set('America/Mexico_City');

        [$year, $month] = explode('-', $yearMonth);
        $year  = (int) $year;
        $month = (int) $month;

        if ($tipoDM == 0) {
            // Mensual del mes indicado
            return $this->generarReporteMensualExpendio($idPlanta, $year, $month);
        } elseif ($tipoDM == 1) {
            // Todos los diarios del mes indicado
            return $this->generarReportesDiariosPorMes($idPlanta, $year, $month);
        } else {
            // Paquete: mensual + diarios del mes
            return response()->json([
                "MENSUALES" => $this->generarReporteMensualExpendio($idPlanta, $year, $month)->getData(true),
                "DIARIOS"   => $this->generarReportesDiariosPorMes($idPlanta, $year, $month)
            ]);
        }
    }

    /* ============================
     *  REPORTE MENSUAL (EXPENDIO)
     * ============================
     */
    private function generarReporteMensualExpendio($idPlanta, $year, $month)
    {
        $igr = InformacionGeneralReporte::where('id_planta', $idPlanta)->firstOrFail();

        $dispensarios   = Dispensario::where('id_planta', $idPlanta)->get();
        $dispIds        = $dispensarios->pluck('id');
        $mangueras      = Manguera::whereIn('id_dispensario', $dispIds)->get();
        $mangueraIds    = $mangueras->pluck('id');

        // Cortes del mes (totalizadores por manguera)
        $cortes = TotalizadorMangueraDia::whereIn('id_manguera', $mangueraIds)
            ->whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->orderBy('fecha')
            ->get();

        // Ajustes del mes (opcional)
        if (class_exists(\App\Models\AjusteTotalizadorManguera::class)) {
            $ajustes = \App\Models\AjusteTotalizadorManguera::whereIn('id_manguera', $mangueraIds)
                ->whereYear('fecha_hora_ajuste', $year)
                ->whereMonth('fecha_hora_ajuste', $month)
                ->get()
                ->groupBy('id_manguera');
        } else {
            $ajustes = collect(); // si no tienes el modelo, no rompe
        }

        // Carácter para modalidad DIS
        $fechaCorteMes = Carbon::create($year, $month, 1)->endOfMonth();
        $caracter = $this->resolverCaracterDesdeTabla($igr, 'DIS', $fechaCorteMes);

        // Catálogo subproductos
        $subprods = Subproducto::where('id_planta', $idPlanta)->get()->keyBy('id');

        // Bloques por dispensario -> mangueras
        $bloquesDisp = $dispensarios->map(function ($disp) use ($mangueras, $cortes, $ajustes, $subprods) {
            $mangs = $mangueras->where('id_dispensario', $disp->id);

            $mangsMes = $mangs->map(function ($m) use ($cortes, $ajustes, $subprods) {
                $cortesM       = $cortes->where('id_manguera', $m->id);
                $volumenMes    = (float) $cortesM->sum('volumen_entregado_dia');
                $diasConCorte  = $cortesM->pluck('fecha')->unique()->count();

                $subprodId = $m->id_subproducto;
                $spModel   = $subprods->get($subprodId);
                $spTexto   = $spModel ? ($spModel->clave . ' — ' . $spModel->descripcion) : null;

                $ajustesM   = $ajustes->get($m->id) ?? collect();
                $numAjustes = $ajustesM->count();

                return [
                    "IdentificadorManguera"   => $m->identificador_manguera,
                    "Subproducto"             => $spTexto,
                    "IdSubproducto"           => $subprodId,
                    "TotalDiasReportados"     => $diasConCorte,
                    "SumaVolumenEntregadoMes" => ["ValorNumerico" => $volumenMes, "UnidadDeMedida" => "UM03"],
                    "AjustesAplicadosMes"     => $numAjustes,
                ];
            })->values();

            return [
                "ClaveIdentificacionDispensario" => $disp->clave_dispensario,
                "MANGUERAS"                      => $mangsMes,
            ];
        })->values();

        // Bitácora mensual (expendio)
        $inicioMes = Carbon::create($year, $month, 1)->startOfMonth();
        $finMes    = Carbon::create($year, $month, 1)->endOfMonth();

        $bitacora = BitacoraDispensario::where('id_planta', $igr->id_planta)
            ->whereBetween('fecha_hora_evento', [$inicioMes, $finMes])
            ->orderBy('fecha_hora_evento')
            ->get()
            ->map(function ($b, $i) {
                return [
                    "NumeroRegistro"                 => $i + 1,
                    "FechaYHoraEvento"               => Carbon::parse($b->fecha_hora_evento)->format('Y-m-d\TH:i:sP'),
                    "UsuarioResponsable"             => $b->usuario_responsable,
                    "TipoEvento"                     => $b->tipo_evento,
                    "DescripcionEvento"              => $b->descripcion_evento,
                    "IdentificacionComponenteAlarma" => $b->identificacion_componente_alarma,
                ];
            })->values();

        // Salida mensual
        return response()->json([
            "TipoReporte"            => "M",
            "Version"                => "1.0",
            "RfcContribuyente"       => $igr->rfc_contribuyente,
            "RfcProveedor"           => $igr->rfc_proveedor,
            "Caracter"               => $caracter,
            "ClaveInstalacion"       => $igr->clave_instalacion,
            "DescripcionInstalacion" => $igr->descripcion_instalacion,
            "Geolocalizacion"        => [
                "GeolocalizacionLatitud"  => $igr->geolocalizacion_latitud,
                "GeolocalizacionLongitud" => $igr->geolocalizacion_longitud,
            ],
            "NumeroDispensarios"     => (int) $dispensarios->count(),
            "FechaYHoraReporteMes"   => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d\T23:59:59P'),

            "DISPENSARIO"            => $bloquesDisp,
            "BITACORA"               => $bitacora,
        ]);
    }

    /* ==================================
     *  DIARIOS DEL MES (EXPENDIO)
     * ==================================
     */
    private function generarReportesDiariosPorMes($idPlanta, $year, $month)
    {
        $dispensarios = Dispensario::where('id_planta', $idPlanta)->get();
        $mangueras    = Manguera::whereIn('id_dispensario', $dispensarios->pluck('id'))->get();

        $fechasUnicas = TotalizadorMangueraDia::whereIn('id_manguera', $mangueras->pluck('id'))
            ->whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->format('Y-m-d'))
            ->unique()
            ->values();

        if ($fechasUnicas->isEmpty()) {
            return [];
        }

        $reportes = [];
        foreach ($fechasUnicas as $fecha) {
            $reporte = $this->generarReporteDiarioPorFecha($idPlanta, $fecha);
            $reportes[] = ["Fecha" => $fecha, "REPORTE" => $reporte];
        }
        return $reportes;
    }

    /* ============================
     *  REPORTE DIARIO (EXPENDIO)
     * ============================
     */
    private function generarReporteDiarioPorFecha($idPlanta, $fecha)
    {
        if (empty($fecha)) abort(400, 'La fecha es obligatoria');

        $igr = InformacionGeneralReporte::where('id_planta', $idPlanta)->firstOrFail();

        $dispensarios = Dispensario::where('id_planta', $idPlanta)->get();
        $mangueras    = Manguera::whereIn('id_dispensario', $dispensarios->pluck('id'))->get()->keyBy('id');

        $caracter = $this->resolverCaracterDesdeTabla($igr, 'DIS', $fecha);

        // Catálogo subproductos
        $subprods = Subproducto::where('id_planta', $idPlanta)->get()->keyBy('id');

        // Cortes del día
        $cortesDia = TotalizadorMangueraDia::whereIn('id_manguera', $mangueras->keys())
            ->whereDate('fecha', $fecha)
            ->orderBy('id_manguera')
            ->get();

        // Agrupar por dispensario
        $porDisp = [];
        foreach ($cortesDia as $c) {
            $m = $mangueras->get($c->id_manguera);
            if (!$m) continue;

            $dispId = $m->id_dispensario;
            $porDisp[$dispId] = $porDisp[$dispId] ?? [];
            $sp = $subprods->get($m->id_subproducto);
            $porDisp[$dispId][] = [
                "IdentificadorManguera"     => $m->identificador_manguera,
                "IdSubproducto"             => $m->id_subproducto,
                "Subproducto"               => $sp ? ($sp->clave . ' — ' . $sp->descripcion) : null,
                "TotalizadorInicialDia"     => ["ValorNumerico" => (float) $c->totalizador_inicial_dia, "UnidadDeMedida" => "UM03"],
                "TotalizadorFinalDia"       => ["ValorNumerico" => (float) $c->totalizador_final_dia,   "UnidadDeMedida" => "UM03"],
                "VolumenEntregadoDia"       => ["ValorNumerico" => (float) $c->volumen_entregado_dia,   "UnidadDeMedida" => "UM03"],
                "Observaciones"             => $c->observaciones,
            ];
        }

        // Bloques DISPENSARIO
        $bloquesDisp = [];
        foreach ($dispensarios as $d) {
            $mangs = $porDisp[$d->id] ?? [];
            $bloquesDisp[] = [
                "ClaveIdentificacionDispensario" => $d->clave_dispensario,
                "MANGUERAS"                      => array_values($mangs),
            ];
        }

        // Bitácora del día (expendio)
        $bitacora = BitacoraDispensario::where('id_planta', $igr->id_planta)
            ->whereDate('fecha_hora_evento', $fecha)
            ->orderBy('fecha_hora_evento')
            ->get()
            ->map(function ($b, $i) {
                return [
                    "NumeroRegistro"                 => $i + 1,
                    "FechaYHoraEvento"               => Carbon::parse($b->fecha_hora_evento)->format('Y-m-d\TH:i:sP'),
                    "UsuarioResponsable"             => $b->usuario_responsable,
                    "TipoEvento"                     => $b->tipo_evento,
                    "DescripcionEvento"              => $b->descripcion_evento,
                    "IdentificacionComponenteAlarma" => $b->identificacion_componente_alarma,
                ];
            })->values();

        // Salida diaria
        return [
            "TipoReporte"            => "D",
            "Version"                => "1.0",
            "RfcContribuyente"       => $igr->rfc_contribuyente,
            "RfcProveedor"           => $igr->rfc_proveedor,
            "Caracter"               => $caracter,
            "ClaveInstalacion"       => $igr->clave_instalacion,
            "DescripcionInstalacion" => $igr->descripcion_instalacion,
            "Geolocalizacion"        => [
                "GeolocalizacionLatitud"  => $igr->geolocalizacion_latitud,
                "GeolocalizacionLongitud" => $igr->geolocalizacion_longitud,
            ],
            "NumeroDispensarios"     => (int) $dispensarios->count(),
            "FechaYHoraCorte"        => Carbon::parse($fecha)->endOfDay()->format('Y-m-d\TH:i:sP'),

            "DISPENSARIO"            => $bloquesDisp,
            "BITACORA"               => $bitacora,
        ];
    }

    /* ===== caracter desde tabla tipo_caracter_planta (igual que en almacén) ===== */
    private function resolverCaracterDesdeTabla($dataGeneral, string $modalidadNecesaria, $fechaRef = null): array
    {
        $ref  = $fechaRef ? ($fechaRef instanceof \Carbon\Carbon ? $fechaRef : \Carbon\Carbon::parse($fechaRef)) : \Carbon\Carbon::now();
        $igrId = $dataGeneral->id;

        $row = DB::table('tipo_caracter_planta')
            ->where('informacion_general_reporte_id', $igrId)
            ->whereRaw('LOWER(tipo_caracter) = ?', ['permisionario'])
            ->whereRaw("UPPER(COALESCE(modalidad_permiso, '')) = ?", [strtoupper($modalidadNecesaria)])
            ->orderByDesc('id')
            ->first();

        if (!$row) {
            $row = DB::table('tipo_caracter_planta')
                ->where('informacion_general_reporte_id', $igrId)
                ->orderByDesc('id')
                ->first();
        }

        if ($row) {
            $tipo = strtolower($row->tipo_caracter ?? '');
            if ($tipo === 'permisionario') {
                return [
                    'TipoCaracter'     => 'permisionario',
                    'ModalidadPermiso' => $row->modalidad_permiso,
                    'NumPermiso'       => $row->numero_permiso,
                ];
            }
            if ($tipo === 'contratista' || $tipo === 'asignatario') {
                return [
                    'TipoCaracter'           => $tipo,
                    'NumContratoOAsignacion' => $row->numero_contrato_asignacion,
                ];
            }
            if ($tipo === 'usuario') {
                return [
                    'TipoCaracter'                 => 'usuario',
                    'InstalacionAlmacenGasNatural' => $dataGeneral->instalacion_almacen_gas,
                ];
            }
        }

        // Fallback (compatibilidad)
        return $this->obtenerCaracterLegacy($dataGeneral);
    }

    private function obtenerCaracterLegacy($dataGeneral)
    {
        switch ($dataGeneral->tipo_caracter) {
            case 'permisionario':
                return [
                    'TipoCaracter'     => $dataGeneral->tipo_caracter,
                    'ModalidadPermiso' => $dataGeneral->modalidad_permiso,
                    'NumPermiso'       => $dataGeneral->numero_permiso,
                ];
            case 'asignatario':
            case 'contratista':
                return [
                    'TipoCaracter'           => $dataGeneral->tipo_caracter,
                    'NumContratoOAsignacion' => $dataGeneral->numero_contrato_asignacion,
                ];
            case 'usuario':
                return [
                    'TipoCaracter'                 => $dataGeneral->tipo_caracter,
                    'InstalacionAlmacenGasNatural' => $dataGeneral->instalacion_almacen_gas,
                ];
            default:
                return [
                    'TipoCaracter' => $dataGeneral->tipo_caracter,
                ];
        }
    }
}
