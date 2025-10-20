<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InformacionGeneralReporte;
use App\Models\EventosComercializacion;
use Carbon\Carbon;

class GenReporteVolumetricoComController extends Controller
{
    /**
     * GET /v1/reporte-comercializacion/{idPlanta}/{yearMonth}/{tipoDM}
     * - tipoDM = 0 => Mensual
     * - tipoDM = 1 => Diarios del mes
     * - tipoDM = 2 => Paquete (mensual + diarios)
     */
    public function generar($idPlanta, $yearMonth, $tipoDM)
    {
        date_default_timezone_set('America/Mexico_City');

        [$year, $month] = explode('-', $yearMonth);
        $year  = (int) $year;
        $month = (int) $month;

        if ($tipoDM == 0) {
            return $this->reporteMensualCom($idPlanta, $year, $month);
        } elseif ($tipoDM == 1) {
            return $this->reportesDiariosDelMesCom($idPlanta, $year, $month);
        }

        return response()->json([
            'MENSUALES' => $this->reporteMensualCom($idPlanta, $year, $month)->getData(true), // embebido plano
            'DIARIOS'   => $this->reportesDiariosDelMesCom($idPlanta, $year, $month)->getData(true),
        ]);
    }

    /* ==========================
     *  REPORTE MENSUAL — COM
     * ========================== */
    private function reporteMensualCom($idPlanta, $year, $month)
    {
        $igr = InformacionGeneralReporte::where('id_planta', $idPlanta)->firstOrFail();

        // Contenedores (flota virtual) de la planta
        $contenedores = DB::table('flota_virtual')
            ->orderBy('id')
            ->get();

        // Eventos del mes (comercialización)
        $eventos = EventosComercializacion::query()
            ->whereIn('flota_virtual_id', function ($q) use ($idPlanta) {
                $q->select('id')->from('flota_virtual');
            })
            ->whereYear('fecha_hora_inicio', $year)
            ->whereMonth('fecha_hora_inicio', $month)
            ->orderBy('fecha_hora_inicio')
            ->get();

        $recepciones = $eventos->where('tipo_evento', 'RECEPCION');
        $entregas    = $eventos->where('tipo_evento', 'ENTREGA');

        $sumaRecepcion = (float) $recepciones->sum('volumen_movido_valor');
        $sumaEntrega   = (float) $entregas->sum('volumen_movido_valor');

        // Carácter con modalidad COM (permisionario comercializador)
        $caracter = $this->resolverCaracterDesdeTabla($igr, 'COM', Carbon::create($year, $month, 1)->endOfMonth());

        // Mapeo de "tanques" (contenedores virtuales) al reporte
        $tanques = $contenedores->map(function ($c) {
            return [
                "ClaveIdentificacionTanque"     => $c->clave_contenedor ?? ('FV-' . $c->id),
                "LocalizacionDescripcionTanque" => $c->tanque_descripcion ?? null,
                "VigenciaCalibracionTanque"     => $c->tanque_vigencia_calibracion ?? null,
                "CapacidadTotalTanque"          => ["ValorNumerico" => (float) ($c->tanque_cap_total_valor ?? 0), "UnidadDeMedida" => $c->tanque_cap_total_um ?? 'UM03'],
                "CapacidadOperativaTanque"      => ["ValorNumerico" => (float) ($c->tanque_cap_oper_valor ?? 0),  "UnidadDeMedida" => $c->tanque_cap_oper_um  ?? 'UM03'],
                "CapacidadUtilTanque"           => ["ValorNumerico" => (float) ($c->tanque_cap_util_valor ?? 0),  "UnidadDeMedida" => $c->tanque_cap_util_um  ?? 'UM03'],
                "CapacidadFondajeTanque"        => ["ValorNumerico" => (float) ($c->tanque_cap_fondaje_valor ?? 0),"UnidadDeMedida" => $c->tanque_cap_fondaje_um ?? 'UM03'],
                "CapacidadGasTalon"             => ["ValorNumerico" => 0.0, "UnidadDeMedida" => "UM03"],
                "VolumenMinimoOperacion"        => ["ValorNumerico" => (float) ($c->tanque_vol_min_oper_valor ?? 0), "UnidadDeMedida" => $c->tanque_vol_min_oper_um ?? 'UM03'],
                "EstadoTanque"                  => $c->tanque_estado ?? null,
                "Medidores"                     => [], // si más adelante agregas medidores virtuales
            ];
        })->values()->toArray();

        // Bitácora comercial (opcional, si tienes tabla separada; si no, deja [])
        $inicioMes = Carbon::create($year, $month, 1)->startOfMonth();
        $finMes    = Carbon::create($year, $month, 1)->endOfMonth();

        // EJEMPLO: si tienes una tabla bitácora_comercializacion con esos campos:
        $bitacora = DB::table('bitacora_comercializacion')
            ->whereBetween(DB::raw('"fecha_hora_evento"::timestamp'), [$inicioMes->toDateTimeString(), $finMes->toDateTimeString()])
            ->orderBy('fecha_hora_evento')
            ->get()
            ->map(function ($r, $i) {
                return [
                    "NumeroRegistro"                 => $i + 1,
                    "fecha_hora_evento"               => $this->fmtIso($r->fecha_hora_evento, 'Y-m-d\TH:i:sP'),
                    "UsuarioResponsable"             => $r->UsuarioResponsable ?? null,
                    "TipoEvento"                     => $r->TipoEvento ?? null,
                    "DescripcionEvento"              => $r->DescripcionEvento ?? null,
                    "IdentificacionComponenteAlarma" => $r->IdentificacionComponenteAlarma ?? null,
                ];
            })->toArray();

        return response()->json([
            "TipoReporte"            => "M",
            "Version"                => "1.0",
            "RfcContribuyente"       => $igr->rfc_contribuyente,
            "RfcProveedor"           => $igr->rfc_proveedor,
            "Caracter"               => $caracter,                  // {TipoCaracter, ModalidadPermiso/NumPermiso...}
            "ClaveInstalacion"       => $igr->clave_instalacion,
            "DescripcionInstalacion" => $igr->descripcion_instalacion,
            "Geolocalizacion"        => [
                "GeolocalizacionLatitud"  => $igr->geolocalizacion_latitud,
                "GeolocalizacionLongitud" => $igr->geolocalizacion_longitud,
            ],
            "NumeroPozos"                        => (int) $igr->numero_pozos,
            "NumeroTanques"                      => (int) $igr->numero_tanques,
            "NumeroDuctosEntradaSalida"          => (int) $igr->numero_ductos_entrada_salida,
            "NumeroDuctosTransporteDistribucion" => (int) $igr->numero_ductos_transporte,
            "NumeroDispensarios"                 => (int) $igr->numero_dispensarios,

            "FechaYHoraReporteMes"               => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d\T23:59:59P'),

            "Producto" => [[
                "ClaveProducto"           => "PR12",     // ajusta si manejas múltiples productos/composición
                "ComposDePropanoEnGasLP"  => 91.5720,
                "ComposDeButanoEnGasLP"   => 8.4279,

                "TANQUE" => $tanques,

                "REPORTEDEVOLUMENMENSUAL" => [
                    "CONTROLDEEXISTENCIAS" => [
                        // si quieres, puedes derivar de último evento del mes
                        "VolumenExistenciasMes"     => ["ValorNumerico" => 0.0, "UnidadDeMedida" => "UM03"],
                        "FechaYHoraEstaMedicionMes" => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d\T23:59:00P'),
                    ],
                    "RECEPCIONES" => [
                        "TotalRecepcionesMes"     => $recepciones->count(),
                        "SumaVolumenRecepcionMes" => ["ValorNumerico" => $sumaRecepcion, "UnidadDeMedida" => "UM03"],
                        "PoderCalorifico"         => ["ValorNumerico" => 11500, "UnidadDeMedida" => "UM03"], // placeholder
                        "TotalDocumentosMes"      => $recepciones->count(),                                     // si más tarde conectas CFDIs, cámbialo
                        "Complemento"             => [],                                                        // aquí podrías inyectar CFDIs si los extraes del JSON complemento
                    ],
                    "ENTREGAS" => [
                        "TotalEntregasMes"        => $entregas->count(),
                        "SumaVolumenEntregadoMes" => ["ValorNumerico" => $sumaEntrega, "UnidadDeMedida" => "UM03"],
                        "PoderCalorifico"         => ["ValorNumerico" => 11500, "UnidadDeMedida" => "UM03"],
                        "TotalDocumentosMes"      => $entregas->count(),
                        "Complemento"             => [],
                    ],
                ],
            ]],

            "BITACORA" => $bitacora,
        ]);
    }

    /* ==========================================
     *  LISTA DE REPORTES DIARIOS DEL MES — COM
     *  Devuelve: [ { Fecha, REPORTE }, ... ]
     * ========================================== */
    private function reportesDiariosDelMesCom($idPlanta, $year, $month)
    {
        // fechas únicas del mes a partir de fecha_hora_inicio
        $fechas = EventosComercializacion::query()
            ->whereIn('flota_virtual_id', function ($q) use ($idPlanta) {
                $q->select('id')->from('flota_virtual');
            })
            ->whereYear('fecha_hora_inicio', $year)
            ->whereMonth('fecha_hora_inicio', $month)
            ->pluck('fecha_hora_inicio')
            ->map(fn($f) => $this->fmtIso($f, 'Y-m-d'))
            ->filter()
            ->unique()
            ->values();

        $out = [];
        foreach ($fechas as $fecha) {
            $out[] = [
                "Fecha"   => $fecha,
                "REPORTE" => $this->reporteDiarioPorFechaCom($idPlanta, $fecha),
            ];
        }

        return response()->json($out);
    }

    /* ==========================
     *  REPORTE DIARIO — COM
     * ========================== */
    private function reporteDiarioPorFechaCom($idPlanta, $fecha) : array
    {
        $igr = InformacionGeneralReporte::where('id_planta', $idPlanta)->firstOrFail();

        // contenedores
        $contenedores = DB::table('flota_virtual')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        // eventos de ese día
        $eventos = EventosComercializacion::query()
            ->whereIn('flota_virtual_id', $contenedores->keys())
            ->whereDate('fecha_hora_inicio', $fecha)
            ->orderBy('fecha_hora_inicio')
            ->get()
            ->groupBy('flota_virtual_id');

        // Carácter diario con modalidad COM
        $caracter = $this->resolverCaracterDesdeTabla($igr, 'COM', $fecha);

        // Para cada contenedor, armamos bloque tipo TANQUE con existencias y movimientos
        $tanques = [];
        foreach ($eventos as $flotaId => $lista) {
            $c = $contenedores->get($flotaId);

            $recepciones = $lista->where('tipo_evento', 'RECEPCION');
            $entregas    = $lista->where('tipo_evento', 'ENTREGA');

            // volumen inicial/final (si los capturas), si no, deja 0
            $primero     = $lista->sortBy('fecha_hora_inicio')->first();
            $ultimo      = $lista->sortByDesc('fecha_hora_fin')->first();

            $volInicial  = (float) ($primero->volumen_inicial_valor ?? 0);
            $volFinal    = (float) ($ultimo->volumen_final_tanque   ?? 0);

            // horas acumulado (si existen)
            $horaRecep   = optional($recepciones->last())->fecha_hora_fin;
            $horaEntreg  = optional($entregas->last())->fecha_hora_fin;

            $existencias = [
                "VolumenExistenciasAnterior" => ["ValorNumerico" => $volInicial, "UnidadDeMedida" => "UM03"],
                "VolumenAcumOpsRecepcion"    => ["ValorNumerico" => (float) $recepciones->sum('volumen_movido_valor'), "UnidadDeMedida" => "UM03"],
                "HoraRecepcionAcumulado"     => $this->fmtIso($horaRecep, 'H:i:sP'),
                "VolumenAcumOpsEntrega"      => ["ValorNumerico" => (float) $entregas->sum('volumen_movido_valor'),   "UnidadDeMedida" => "UM03"],
                "HoraEntregaAcumulado"       => $this->fmtIso($horaEntreg, 'H:i:sP'),
                "VolumenExistencias"         => ["ValorNumerico" => $volFinal, "UnidadDeMedida" => "UM03"],
                "FechaYHoraEstaMedicion"     => $this->fmtIso(Carbon::parse($fecha)->endOfDay(), 'Y-m-d\TH:i:sP'),
                "FechaYHoraMedicionAnterior" => $this->fmtIso(Carbon::parse($fecha)->subDay()->endOfDay(), 'Y-m-d\TH:i:sP'),
            ];

            $mapRecepciones = $recepciones->map(function ($e) {
                return [
                    "VolumenDespuesRecepcion"   => ["ValorNumerico" => (float) ($e->volumen_final_tanque ?? 0),  "UnidadDeMedida" => $e->volumen_movido_um ?? 'UM03'],
                    "VolumenRecepcion"          => ["ValorNumerico" => (float) ($e->volumen_movido_valor ?? 0),  "UnidadDeMedida" => $e->volumen_movido_um ?? 'UM03'],
                    "Temperatura"               => (float) ($e->temperatura ?? 20.0),
                    "PresionAbsoluta"           => (float) ($e->presion_absoluta ?? 101.325),
                    "FechaYHoraInicioRecepcion" => $this->fmtIso($e->fecha_hora_inicio, 'Y-m-d\TH:i:sP'),
                    "FechaYHoraFinRecepcion"    => $this->fmtIso($e->fecha_hora_fin,    'Y-m-d\TH:i:sP'),
                    "Complemento"               => $this->extraerComplementoCFDIs($e->complemento),
                ];
            })->values();

            $mapEntregas = $entregas->map(function ($e) {
                return [
                    "VolumenDespuesEntrega"     => ["ValorNumerico" => (float) ($e->volumen_final_tanque ?? 0),  "UnidadDeMedida" => $e->volumen_movido_um ?? 'UM03'],
                    "VolumenEntregado"          => ["ValorNumerico" => (float) ($e->volumen_movido_valor ?? 0),  "UnidadDeMedida" => $e->volumen_movido_um ?? 'UM03'],
                    "Temperatura"               => (float) ($e->temperatura ?? 20.0),
                    "PresionAbsoluta"           => (float) ($e->presion_absoluta ?? 101.325),
                    "FechaYHoraInicioEntrega"   => $this->fmtIso($e->fecha_hora_inicio, 'Y-m-d\TH:i:sP'),
                    "FechaYHoraFinEntrega"      => $this->fmtIso($e->fecha_hora_fin,    'Y-m-d\TH:i:sP'),
                    "Complemento"               => $this->extraerComplementoCFDIs($e->complemento),
                ];
            })->values();

            $tanques[] = [
                "ClaveIdentificacionTanque"     => $c->clave_contenedor ?? ('FV-' . $c->id),
                "LocalizacionDescripcionTanque" => $c->tanque_descripcion ?? null,
                "VigenciaCalibracionTanque"     => $c->tanque_vigencia_calibracion ?? null,
                "CapacidadTotalTanque"          => ["ValorNumerico" => (float) ($c->tanque_cap_total_valor ?? 0), "UnidadDeMedida" => $c->tanque_cap_total_um ?? 'UM03'],
                "CapacidadOperativaTanque"      => ["ValorNumerico" => (float) ($c->tanque_cap_oper_valor ?? 0),  "UnidadDeMedida" => $c->tanque_cap_oper_um  ?? 'UM03'],
                "CapacidadUtilTanque"           => ["ValorNumerico" => (float) ($c->tanque_cap_util_valor ?? 0),  "UnidadDeMedida" => $c->tanque_cap_util_um  ?? 'UM03'],
                "CapacidadFondajeTanque"        => ["ValorNumerico" => (float) ($c->tanque_cap_fondaje_valor ?? 0),"UnidadDeMedida" => $c->tanque_cap_fondaje_um ?? 'UM03'],
                "CapacidadGasTalon"             => ["ValorNumerico" => 0.0, "UnidadDeMedida" => "UM03"],
                "VolumenMinimoOperacion"        => ["ValorNumerico" => (float) ($c->tanque_vol_min_oper_valor ?? 0), "UnidadDeMedida" => $c->tanque_vol_min_oper_um ?? 'UM03'],
                "EstadoTanque"                  => $c->tanque_estado ?? null,
                "Medidores"                     => [],
                "EXISTENCIAS"                   => $existencias,
                "RECEPCIONES"                   => $mapRecepciones,
                "ENTREGAS"                      => $mapEntregas,
            ];
        }

        // Bitácora (si tienes tabla de bitácora comercial)
        $bitacora = DB::table('bitacora_comercializacion')
            ->whereRaw('DATE("fecha_hora_evento"::timestamp) = ?', [$fecha])
            ->orderBy('fecha_hora_evento')
            ->get()
            ->map(function ($r, $i) {
                return [
                    "NumeroRegistro"                 => $i + 1,
                    "fecha_hora_evento"               => $this->fmtIso($r->fecha_hora_evento, 'Y-m-d\TH:i:sP'),
                    "UsuarioResponsable"             => $r->UsuarioResponsable ?? null,
                    "TipoEvento"                     => $r->TipoEvento ?? null,
                    "DescripcionEvento"              => $r->DescripcionEvento ?? null,
                    "IdentificacionComponenteAlarma" => $r->IdentificacionComponenteAlarma ?? null,
                ];
            })->toArray();

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
            "NumeroPozos"                        => (int) $igr->numero_pozos,
            "NumeroTanques"                      => (int) $igr->numero_tanques,
            "NumeroDuctosEntradaSalida"          => (int) $igr->numero_ductos_entrada_salida,
            "NumeroDuctosTransporteDistribucion" => (int) $igr->numero_ductos_transporte,
            "NumeroDispensarios"                 => (int) $igr->numero_dispensarios,
            "FechaYHoraCorte"                    => $this->fmtIso(Carbon::parse($fecha)->endOfDay(), 'Y-m-d\TH:i:sP'),

            "Producto" => [[
                "ClaveProducto"           => "PR12",
                "ComposDePropanoEnGasLP"  => 91.5720,
                "ComposDeButanoEnGasLP"   => 8.4279,
                "TANQUE"                  => $tanques,
            ]],

            "BITACORA" => $bitacora,
        ];
    }

    /* ======================================================
     *  Resolver Carácter desde tipo_caracter_planta (modalidad COM)
     * ====================================================== */
    private function resolverCaracterDesdeTabla($igr, string $modalidadNecesaria, $fechaRef = null): array
    {
        $ref  = $fechaRef ? ($fechaRef instanceof Carbon ? $fechaRef : Carbon::parse($fechaRef)) : Carbon::now();
        $igrId = $igr->id;

        $row = DB::table('tipo_caracter_planta')
            ->where('informacion_general_reporte_id', $igrId)
            ->whereRaw('LOWER(tipo_caracter) = ?', ['permisionario'])
            ->whereRaw("UPPER(COALESCE(modalidad_permiso, '')) = ?", [strtoupper($modalidadNecesaria)]) // 'COM'
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
                    'ModalidadPermiso' => $row->modalidad_permiso,   // COM
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
                    'InstalacionAlmacenGasNatural' => $igr->instalacion_almacen_gas,
                ];
            }
        }

        // Fallback legacy si no hay filas
        return $this->obtenerCaracterLegacy($igr);
    }

    private function obtenerCaracterLegacy($igr)
    {
        switch ($igr->tipo_caracter) {
            case 'permisionario':
                return [
                    'TipoCaracter'     => $igr->tipo_caracter,
                    'ModalidadPermiso' => $igr->modalidad_permiso,
                    'NumPermiso'       => $igr->numero_permiso,
                ];
            case 'asignatario':
            case 'contratista':
                return [
                    'TipoCaracter'           => $igr->tipo_caracter,
                    'NumContratoOAsignacion' => $igr->numero_contrato_asignacion,
                ];
            case 'usuario':
                return [
                    'TipoCaracter'                 => $igr->tipo_caracter,
                    'InstalacionAlmacenGasNatural' => $igr->instalacion_almacen_gas,
                ];
            default:
                return [
                    'TipoCaracter' => $igr->tipo_caracter,
                    'Mensaje'      => 'Tipo de caracter no reconocido',
                ];
        }
    }

    /* =====================
     *  Helpers de fechas
     * ===================== */
    private function toCarbonOrNull($value): ?Carbon
    {
        if ($value instanceof Carbon) return $value;
        if ($value instanceof \DateTimeInterface) return Carbon::instance($value);
        if (is_string($value) && $value !== '') {
            try { return Carbon::parse($value); } catch (\Throwable $e) { return null; }
        }
        return null;
    }

    private function fmtIso($value, string $pattern)
    {
        $dt = $value instanceof Carbon ? $value : $this->toCarbonOrNull($value);
        return $dt ? $dt->format($pattern) : null;
    }

    /**
     * Extrae CFDIs (si existen) del JSON complemento del evento
     * esperado en la forma comp.Nacional[0].Cfdis (según tu modal).
     */
    private function extraerComplementoCFDIs($complemento)
    {
        if (empty($complemento)) return [];
        try {
            // $complemento ya viene casteado a array en el modelo; si llega string, decodifica:
            if (is_string($complemento)) $complemento = json_decode($complemento, true);

            $out = [];
            // Nacional
            if (!empty($complemento['Nacional'][0]['Cfdis'])) {
                foreach ($complemento['Nacional'][0]['Cfdis'] as $c) {
                    $out[] = [
                        "TipoComplemento"       => "CFDI",
                        "UUID"                  => $c['CfdiTransaccion'] ?? null,
                        "Fecha"                 => $c['FechaHoraTransaccion'] ?? null,
                        "MontoTotalOperacion"   => (float) ($c['PrecioVentaCompraContrap'] ?? 0),
                        "VolumenDocumentado"    => [
                            "ValorNumerico"   => (float) ($c['VolumenDocumentado']['ValorNumerico'] ?? 0),
                            "UnidadDeMedida"  => $c['VolumenDocumentado']['UnidadDeMedida'] ?? 'UM03',
                        ],
                    ];
                }
            }
            // Extranjero (si aplica)
            if (!empty($complemento['Extranjero'][0]['Pedimentos'])) {
                foreach ($complemento['Extranjero'][0]['Pedimentos'] as $p) {
                    $out[] = [
                        "TipoComplemento"       => "PEDIMENTO",
                        "PedimentoAduanal"      => $p['PedimentoAduanal'] ?? null,
                        "Fecha"                 => null,
                        "MontoTotalOperacion"   => (float) ($p['PrecioDeImportacionOExportacion'] ?? 0),
                        "VolumenDocumentado"    => [
                            "ValorNumerico"   => (float) ($p['VolumenDocumentado']['ValorNumerico'] ?? 0),
                            "UnidadDeMedida"  => $p['VolumenDocumentado']['UnidadDeMedida'] ?? 'UM03',
                        ],
                    ];
                }
            }

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
