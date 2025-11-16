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
            'MENSUALES' => $this->reporteMensualCom($idPlanta, $year, $month)->getData(true),
            'DIARIOS'   => $this->reportesDiariosDelMesCom($idPlanta, $year, $month)->getData(true),
        ]);
    }


    private function fkEventoCol(?string $table): ?string
    {
        if (!$table) return null;

        $schema = DB::getSchemaBuilder();

        // 0) ¿Existe la tabla? Si no, no seguimos.
        if (!$schema->hasTable($table)) {
            // No trueno: la trato como "no disponible"
            return null;
        }

        // 1) Nombres comunes primero
        $comunes = [
            'evento_comercializacion_id',
            'eventos_comercializacion_id',
            'evento_id',
            'id_evento_comercializacion',
            'id_evento',
        ];
        foreach ($comunes as $col) {
            if ($schema->hasColumn($table, $col)) return $col;
        }

        // 2) Busca en information_schema, priorizando public y el esquema actual
        $schemas = DB::select("select current_schema() as s");
        $actual  = $schemas[0]->s ?? 'public';

        $candidatas = DB::table('information_schema.columns')
            ->select('column_name', 'data_type')
            ->whereIn('table_schema', ['public', $actual])
            ->where('table_name', $table)
            ->whereIn('data_type', ['integer', 'bigint'])
            ->where('column_name', 'ILIKE', '%evento%')
            ->pluck('column_name')
            ->all();

        if (!empty($candidatas)) {
            return $candidatas[0]; // la primera que contenga "evento"
        }

        // 3) No hay FK detectable; no trueno: devuelvo null para que el caller decida
        return null;
    }


    private function pickColumn(string $table, array $candidates): ?string
    {
        $schema = DB::getSchemaBuilder();
        foreach ($candidates as $c) {
            if ($schema->hasColumn($table, $c)) return $c;
        }
        return null;
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
                "CapacidadFondajeTanque"        => ["ValorNumerico" => (float) ($c->tanque_cap_fondaje_valor ?? 0), "UnidadDeMedida" => $c->tanque_cap_fondaje_um ?? 'UM03'],
                "CapacidadGasTalon"             => ["ValorNumerico" => 0.0, "UnidadDeMedida" => "UM03"],
                "VolumenMinimoOperacion"        => ["ValorNumerico" => (float) ($c->tanque_vol_min_oper_valor ?? 0), "UnidadDeMedida" => $c->tanque_vol_min_oper_um ?? 'UM03'],
                "EstadoTanque"                  => $c->tanque_estado ?? null,
                "Medidores"                     => [],
            ];
        })->values()->toArray();

        // Bitácora comercial (opcional)
        $inicioMes = Carbon::create($year, $month, 1)->startOfMonth();
        $finMes    = Carbon::create($year, $month, 1)->endOfMonth();

        $bitacora = DB::table('bitacora_comercializacion')
            ->whereBetween(DB::raw('"fecha_hora_evento"::timestamp'), [$inicioMes->toDateTimeString(), $finMes->toDateTimeString()])
            ->orderBy('fecha_hora_evento')
            ->get()
            ->map(function ($r, $i) {
                return [
                    "NumeroRegistro"                 => $i + 1,
                    "fecha_hora_evento"              => $this->fmtIso($r->fecha_hora_evento, 'Y-m-d\TH:i:sP'),
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

            "FechaYHoraReporteMes"               => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d\T23:59:59P'),

            "Producto" => [[
                "ClaveProducto"           => "PR12",
                "ComposDePropanoEnGasLP"  => 91.5720,
                "ComposDeButanoEnGasLP"   => 8.4279,

                "TANQUE" => $tanques,

                "REPORTEDEVOLUMENMENSUAL" => [
                    "CONTROLDEEXISTENCIAS" => [
                        "VolumenExistenciasMes"     => ["ValorNumerico" => 0.0, "UnidadDeMedida" => "UM03"],
                        "FechaYHoraEstaMedicionMes" => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d\T23:59:00P'),
                    ],
                    "RECEPCIONES" => [
                        "TotalRecepcionesMes"     => $recepciones->count(),
                        "SumaVolumenRecepcionMes" => ["ValorNumerico" => $sumaRecepcion, "UnidadDeMedida" => "UM03"],
                        "PoderCalorifico"         => ["ValorNumerico" => 11500, "UnidadDeMedida" => "UM03"],
                        "TotalDocumentosMes"      => $recepciones->count(),
                        "Complemento"             => [],
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
     * ========================================== */
    private function reportesDiariosDelMesCom($idPlanta, $year, $month)
    {
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
    private function reporteDiarioPorFechaCom($idPlanta, $fecha): array
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

        // precargar complementos normalizados (tablas hijas) para todos los eventos del día
        $eventosFlat = $eventos->flatten(1);
        $cacheComplementos = $this->precargarComplementosPorEventos($eventosFlat);

        // Carácter diario con modalidad COM
        $caracter = $this->resolverCaracterDesdeTabla($igr, 'COM', $fecha);

        // Para cada contenedor, armamos bloque tipo TANQUE con existencias y movimientos
        $tanques = [];
        foreach ($eventos as $flotaId => $lista) {
            $c = $contenedores->get($flotaId);

            $recepciones = $lista->where('tipo_evento', 'RECEPCION');
            $entregas    = $lista->where('tipo_evento', 'ENTREGA');

            $primero     = $lista->sortBy('fecha_hora_inicio')->first();
            $ultimo      = $lista->sortByDesc('fecha_hora_fin')->first();

            $volInicial  = (float) ($primero->volumen_inicial_valor ?? 0);
            $volFinal    = (float) ($ultimo->volumen_final_tanque   ?? 0);

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

            $mapRecepciones = $recepciones->map(function ($e) use ($cacheComplementos) {
                return [
                    "VolumenDespuesRecepcion"   => ["ValorNumerico" => (float) ($e->volumen_final_tanque ?? 0),  "UnidadDeMedida" => $e->volumen_movido_um ?? 'UM03'],
                    "VolumenRecepcion"          => ["ValorNumerico" => (float) ($e->volumen_movido_valor ?? 0),  "UnidadDeMedida" => $e->volumen_movido_um ?? 'UM03'],
                    "Temperatura"               => (float) ($e->temperatura ?? 20.0),
                    "PresionAbsoluta"           => (float) ($e->presion_absoluta ?? 101.325),
                    "FechaYHoraInicioRecepcion" => $this->fmtIso($e->fecha_hora_inicio, 'Y-m-d\TH:i:sP'),
                    "FechaYHoraFinRecepcion"    => $this->fmtIso($e->fecha_hora_fin,    'Y-m-d\TH:i:sP'),
                    "Complemento"               => $this->complementoParaEvento($cacheComplementos, $e),
                ];
            })->values();

            $mapEntregas = $entregas->map(function ($e) use ($cacheComplementos) {
                return [
                    "VolumenDespuesEntrega"     => ["ValorNumerico" => (float) ($e->volumen_final_tanque ?? 0),  "UnidadDeMedida" => $e->volumen_movido_um ?? 'UM03'],
                    "VolumenEntregado"          => ["ValorNumerico" => (float) ($e->volumen_movido_valor ?? 0),  "UnidadDeMedida" => $e->volumen_movido_um ?? 'UM03'],
                    "Temperatura"               => (float) ($e->temperatura ?? 20.0),
                    "PresionAbsoluta"           => (float) ($e->presion_absoluta ?? 101.325),
                    "FechaYHoraInicioEntrega"   => $this->fmtIso($e->fecha_hora_inicio, 'Y-m-d\TH:i:sP'),
                    "FechaYHoraFinEntrega"      => $this->fmtIso($e->fecha_hora_fin,    'Y-m-d\TH:i:sP'),
                    "Complemento"               => $this->complementoParaEvento($cacheComplementos, $e),
                ];
            })->values();

            $tanques[] = [
                "ClaveIdentificacionTanque"     => $c->clave_contenedor ?? ('FV-' . $c->id),
                "LocalizacionDescripcionTanque" => $c->tanque_descripcion ?? null,
                "VigenciaCalibracionTanque"     => $c->tanque_vigencia_calibracion ?? null,
                "CapacidadTotalTanque"          => ["ValorNumerico" => (float) ($c->tanque_cap_total_valor ?? 0), "UnidadDeMedida" => $c->tanque_cap_total_um ?? 'UM03'],
                "CapacidadOperativaTanque"      => ["ValorNumerico" => (float) ($c->tanque_cap_oper_valor ?? 0),  "UnidadDeMedida" => $c->tanque_cap_oper_um  ?? 'UM03'],
                "CapacidadUtilTanque"           => ["ValorNumerico" => (float) ($c->tanque_cap_util_valor ?? 0),  "UnidadDeMedida" => $c->tanque_cap_util_um  ?? 'UM03'],
                "CapacidadFondajeTanque"        => ["ValorNumerico" => (float) ($c->tanque_cap_fondaje_valor ?? 0), "UnidadDeMedida" => $c->tanque_cap_fondaje_um ?? 'UM03'],
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
                    "fecha_hora_evento"              => $this->fmtIso($r->fecha_hora_evento, 'Y-m-d\TH:i:sP'),
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
     *  Resolver Carácter desde tipo_caracter_planta (COM)
     * ====================================================== */
    private function resolverCaracterDesdeTabla($igr, string $modalidadNecesaria, $fechaRef = null): array
    {
        $ref  = $fechaRef ? ($fechaRef instanceof Carbon ? $fechaRef : Carbon::parse($fechaRef)) : Carbon::now();
        $igrId = $igr->id;

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
                    'InstalacionAlmacenGasNatural' => $igr->instalacion_almacen_gas,
                ];
            }
        }

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
            try {
                return Carbon::parse($value);
            } catch (\Throwable $e) {
                return null;
            }
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
     * Se mantiene como fallback para retrocompatibilidad.
     */
    private function extraerComplementoCFDIs($complemento)
    {
        if (empty($complemento)) return [];
        try {
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
            // Extranjero
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

    /* ======================================================
     *  === NUEVOS HELPERS PARA TABLAS HIJAS ===
     * ====================================================== */

    private function obtenerCfdisPorEventoIds(array $ids): \Illuminate\Support\Collection
    {
        if (empty($ids)) return collect();

        $table = 'cfdis_comercializadores';
        $fk    = $this->fkEventoCol($table); // usa evento_id o evento_comercializacion_id según exista

        return DB::table($table)
            ->whereIn($fk, $ids)
            ->select([
                DB::raw("\"{$fk}\" as evento_id"),
                'tipo_cfdi as TipoCfdi',
                // El generador espera "PrecioVentaCompraContrap". Lo mapeamos a tus columnas reales:
                DB::raw('COALESCE(monto_total, precio, contraprestacion, 0) as "PrecioVentaCompraContrap"'),
                // Fecha del CFDI
                DB::raw('fecha_hora_cfdi::timestamp as "FechaHoraTransaccion"'),
                // Volumen documentado
                DB::raw('COALESCE(volumen_documentado_valor, 0) as "VolumenValor"'),
                DB::raw('COALESCE(volumen_documentado_um, \'UM03\') as "VolumenUM"'),
                // UUID del CFDI
                'uuid as CfdiTransaccion',
            ])
            ->get();
    }



    /** Lee Pedimentos con FK dinámica y columnas reales detectadas en runtime (pgsql). */
    private function obtenerPedimentosPorEventoIds(array $ids): \Illuminate\Support\Collection
    {
        if (empty($ids)) return collect();

        $table = 'pedimentos_comercializadores';
        $fk    = $this->fkEventoCol($table); // ya lo tienes: detecta evento_id vs evento_comercializacion_id

        // Detecta nombres reales de tus columnas (sin inventar)
        $colPunto   = $this->pickColumn($table, ['punto_internacion_o_extraccion', 'punto_internacion_extraccion']);
        $colPais    = $this->pickColumn($table, ['pais_origen_o_destino', 'pais_origen_destino']);
        $colMedio   = $this->pickColumn($table, ['medio_trans_entra_sale_aduana', 'medio_trans_aduana']);
        $colPed     = $this->pickColumn($table, ['pedimento_aduanal', 'pedimento']);
        $colIncot   = $this->pickColumn($table, ['incoterms']);
        $colPrecio  = $this->pickColumn($table, ['precio_import_export']);
        $colVolVal  = $this->pickColumn($table, ['volumen_doc_valor', 'volumen_documentado_valor']);
        $colVolUM   = $this->pickColumn($table, ['volumen_doc_um', 'volumen_documentado_um']);

        // Construye el SELECT con alias estándar esperados por el reporte
        $select = [
            DB::raw("\"{$fk}\" as evento_id"),
            DB::raw(($colPunto  ? "\"{$colPunto}\""  : "NULL") . ' as "PuntoDeInternacionOExtraccion"'),
            DB::raw(($colPais   ? "\"{$colPais}\""   : "NULL") . ' as "PaisOrigenODestino"'),
            DB::raw(($colMedio  ? "\"{$colMedio}\""  : "NULL") . ' as "MedioDeTransEntraOSaleAduana"'),
            DB::raw(($colPed    ? "\"{$colPed}\""    : "NULL") . ' as "PedimentoAduanal"'),
            DB::raw(($colIncot  ? "\"{$colIncot}\""  : "NULL") . ' as "Incoterms"'),
            DB::raw(($colPrecio ? "COALESCE(\"{$colPrecio}\",0)" : "0") . ' as "PrecioDeImportacionOExportacion"'),
            DB::raw(($colVolVal ? "COALESCE(\"{$colVolVal}\",0)" : "0") . ' as "VolumenValor"'),
            DB::raw(($colVolUM  ? "\"{$colVolUM}\""  : "'UM03'") . ' as "VolumenUM"'),
        ];

        return DB::table($table)
            ->whereIn($fk, $ids)
            ->select($select)
            ->get();
    }

    private function obtenerTrasvasesPorEventoIds(array $ids): \Illuminate\Support\Collection
    {
        if (empty($ids)) return collect();

        $table = 'trasvases_comercializadores';
        $fk    = $this->fkEventoCol($table);

        // Si no hay FK detectable o la tabla no existe, no trueno: no hay trasvases y seguimos
        if (!$fk) {
            // Opcional: Log::warning("Tabla {$table} sin FK evento detectable; se omite Complemento.Trasvase");
            return collect();
        }

        // Detecta nombres REALES (no supongo nada fuera de estas variantes)
        $colNombre = $this->pickColumn($table, ['nombre_trasvase', 'nombre', 'razon_social_trasvase']);
        $colRfc    = $this->pickColumn($table, ['rfc_trasvase', 'rfc']);
        $colPerm   = $this->pickColumn($table, ['permiso_trasvase', 'permiso']);
        $colDesc   = $this->pickColumn($table, ['descripcion_trasvase', 'descripcion']);
        $colCfdi   = $this->pickColumn($table, ['cfdi_trasvase', 'uuid']);

        $select = [
            DB::raw("\"{$fk}\" as evento_id"),
            DB::raw(($colNombre ? "\"{$colNombre}\"" : "NULL") . ' as "NombreTrasvase"'),
            DB::raw(($colRfc    ? "\"{$colRfc}\""    : "NULL") . ' as "RfcTrasvase"'),
            DB::raw(($colPerm   ? "\"{$colPerm}\""   : "NULL") . ' as "PermisoTrasvase"'),
            DB::raw(($colDesc   ? "\"{$colDesc}\""   : "NULL") . ' as "DescripcionTrasvase"'),
            DB::raw(($colCfdi   ? "\"{$colCfdi}\""   : "NULL") . ' as "CfdiTrasvase"'),
        ];

        return DB::table($table)
            ->whereIn($fk, $ids)
            ->select($select)
            ->get();
    }


    /** Precarga todas las tablas hijas con groupBy uniforme por evento_id. */
    private function precargarComplementosPorEventos($eventosLista): array
    {
        $ids = $eventosLista->pluck('id')->all();

        $cfdis = $this->obtenerCfdisPorEventoIds($ids)->groupBy('evento_id')->map->toArray()->toArray();
        $peds  = $this->obtenerPedimentosPorEventoIds($ids)->groupBy('evento_id')->map->toArray()->toArray();
        $tras  = $this->obtenerTrasvasesPorEventoIds($ids)->groupBy('evento_id')->map->toArray()->toArray();

        return [
            'cfdis'      => $cfdis,
            'pedimentos' => $peds,
            'trasvases'  => $tras,
        ];
    }



    private function complementoParaEvento(array $cache, $evento): array
    {
        $out = [];
        $eid = $evento->id;

        if (!empty($cache['cfdis'][$eid])) {
            foreach ($cache['cfdis'][$eid] as $c) {
                $out[] = [
                    "TipoComplemento"     => "CFDI",
                    "UUID"                => $c['CfdiTransaccion'] ?? null,
                    "Fecha"               => $c['FechaHoraTransaccion'] ?? null,
                    "MontoTotalOperacion" => (float) ($c['PrecioVentaCompraContrap'] ?? 0),
                    "VolumenDocumentado"  => [
                        "ValorNumerico"  => (float) ($c['VolumenValor'] ?? 0),
                        "UnidadDeMedida" => $c['VolumenUM'] ?? 'UM03',
                    ],
                ];
            }
        }
        if (!empty($cache['pedimentos'][$eid])) {
            foreach ($cache['pedimentos'][$eid] as $p) {
                $out[] = [
                    "TipoComplemento"       => "PEDIMENTO",
                    "PedimentoAduanal"      => $p['PedimentoAduanal'] ?? null,
                    "Fecha"                 => null,
                    "MontoTotalOperacion"   => (float) ($p['PrecioDeImportacionOExportacion'] ?? 0),
                    "VolumenDocumentado"    => [
                        "ValorNumerico"   => (float) ($p['VolumenValor'] ?? 0),
                        "UnidadDeMedida"  => $p['VolumenUM'] ?? 'UM03',
                    ],
                ];
            }
        }

        if (!empty($out)) return $out;

        // Fallback a JSON `complemento` del evento (retrocompatibilidad)
        return $this->extraerComplementoCFDIs($evento->complemento);
    }
}
