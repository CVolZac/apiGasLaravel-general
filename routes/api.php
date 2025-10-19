<?php

use App\Http\Controllers\AjusteTotalizadorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginRegisterController;
use App\Http\Controllers\RegistroLlenadoAlmacenController;
//use App\Http\Controllers\ReporteVolumetrico;
use App\Http\Controllers\InformacionGeneralReporteController;
use App\Http\Controllers\GenReporteVolumetricoController;
use App\Http\Controllers\RolesUsuariosController;
use App\Http\Controllers\PlantaGasController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\Api\ContraparteController;
use App\Http\Controllers\Api\ContratoController;
use App\Http\Controllers\BitacoraComercializacionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BitacoraEventosController;
use App\Http\Controllers\EventoAlmacenController;
use App\Http\Controllers\ExistenciaAlmacenController;
use App\Http\Controllers\CfdiController;

use App\Http\Controllers\ComplementoNacionalController;
use App\Http\Controllers\ComplementoExtranjeroController;
use App\Http\Controllers\ComplementoTransporteController;


use App\Http\Controllers\ComercializadorInstalacionController;
use App\Http\Controllers\TanqueVirtualController;
use App\Http\Controllers\EventoTanqueVirtualController;
use App\Http\Controllers\EventoCfdiTanqueVirutalController;
use App\Http\Controllers\BitacoraComercializadorController;
use App\Http\Controllers\BitacoraDispensarioController;
use App\Http\Controllers\ContraparteController as ControllersContraparteController;
use App\Http\Controllers\ContratoController as ControllersContratoController;
use App\Http\Controllers\CortesExpendioController;
use App\Http\Controllers\DispensarioController;
use App\Http\Controllers\EventoComercializacionController;
use App\Http\Controllers\ExpendioPreviewController;
use App\Http\Controllers\FlotaVirtualController;
use App\Http\Controllers\GenReporteExpendioController;
use App\Http\Controllers\MangueraController;
use App\Http\Controllers\MedidorDispensarioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\SubproductoController;
use App\Http\Controllers\TipoCaracterPlantaController;
use App\Models\Dispensario;
use App\Models\MedidorDispensario;

// Rutas publicas para acceder o registrar una cuenta
Route::controller(LoginRegisterController::class)->group(function () {
    Route::post('/local/auth/register', 'register');
    Route::post('/local/auth/login', 'login');
});

// Protected routes of product and logout
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/local/auth/logout', [LoginRegisterController::class, 'logout']);

    // Complemento NACIONAL (1:1 por evento)
    Route::get('/v1/eventoAlmacen/{eventoId}/nacional',        [ComplementoNacionalController::class, 'show']);
    Route::post('/v1/eventoAlmacen/{eventoId}/nacional',        [ComplementoNacionalController::class, 'store']);
    Route::post('/v1/eventoAlmacen/{eventoId}/nacional/update', [ComplementoNacionalController::class, 'update']);
    Route::delete('/v1/eventoAlmacen/{eventoId}/nacional',        [ComplementoNacionalController::class, 'destroy']);

    // Complemento EXTRANJERO (1:1 por evento)
    Route::get('/v1/eventoAlmacen/{eventoId}/extranjero',        [ComplementoExtranjeroController::class, 'show']);
    Route::post('/v1/eventoAlmacen/{eventoId}/extranjero',        [ComplementoExtranjeroController::class, 'store']);
    Route::post('/v1/eventoAlmacen/{eventoId}/extranjero/update', [ComplementoExtranjeroController::class, 'update']);
    Route::delete('/v1/eventoAlmacen/{eventoId}/extranjero',        [ComplementoExtranjeroController::class, 'destroy']);

    // Complemento TRANSPORTE (1:1 por evento)
    Route::get('/v1/eventoAlmacen/{eventoId}/transporte',        [ComplementoTransporteController::class, 'show']);
    Route::post('/v1/eventoAlmacen/{eventoId}/transporte',        [ComplementoTransporteController::class, 'store']);
    Route::post('/v1/eventoAlmacen/{eventoId}/transporte/update', [ComplementoTransporteController::class, 'update']);
    Route::delete('/v1/eventoAlmacen/{eventoId}/transporte',        [ComplementoTransporteController::class, 'destroy']);

    // INFORMACIÓN DE ROLES Y PLANTA

    Route::controller(RolesUsuariosController::class)->group(function () {
        Route::get('/v1/rol', 'index');
        Route::get('/v1/rol/{id}', 'show');
        Route::post('/v1/rol', 'store');
        Route::post('/v1/rol/{id}', 'update');
        Route::delete('/v1/rol/{id}', 'destroy');
    });

    Route::controller(PlantaGasController::class)->group(function () {
        Route::get('/v1/planta', 'index');
        Route::get('/v1/planta/{id}', 'show');
        Route::post('/v1/planta', 'store');
        Route::post('/v1/planta/{id}', 'update');
        Route::delete('/v1/planta/{id}', 'destroy');
    });


    //Registro llenado del almacen
    Route::controller(RegistroLlenadoAlmacenController::class)->group(function () {
        Route::get('/v1/almacen-registro/{idPlanta}', 'index');
        Route::get('/v1/almacen-registro/{id}', 'show');
        Route::post('/v1/almacen-registro', 'store');
        Route::post('/v1/almacen-registro/{id}', 'update');
        Route::delete('/v1/almacen-registro/{id}', 'destroy');
    });


    Route::controller(InformacionGeneralReporteController::class)->group(function () {
        Route::get('/v1/reporteVolumetrico/informacion-general/planta/{idPlanta}', 'index');
        Route::get('/v1/reporteVolumetrico/informacion-general/{id}', 'show');
        Route::post('/v1/reporteVolumetrico/informacion-general', 'store');          // upsert por id_planta
        Route::post('/v1/reporteVolumetrico/informacion-general/{id}', 'update');    // update por id
        Route::delete('/v1/reporteVolumetrico/informacion-general/{id}', 'destroy');
    });


    Route::controller(AlmacenController::class)->group(function () {
        Route::get('/v1/almacen/{idPlanta}', 'index');
        Route::get('/v1/almacen/{id}', 'show');
        Route::post('/v1/almacen', 'store');
        Route::post('/v1/almacen/{id}', 'update');
        Route::delete('/v1/almacen/{id}', 'destroy');
    });

    Route::controller(GenReporteVolumetricoController::class)->group(function () {
        Route::get('/v1/generar-reporte/{idPlanta}/{yearAndMonth}/{tipoDM}', 'generarReporte');
        Route::get('/v1/ConsultCDFI', 'consultarCFDI');
    });

    Route::controller(UserController::class)->group(function () {
        Route::get('/v1/usuario/{idPlanta}', 'index');
        Route::get('/v1/usuario/{idPlanta}/{idUsuario}', 'show');
        Route::post('/v1/usuario', 'store');
        Route::post('/v1/usuario/{id}', 'update');
        Route::delete('/v1/usuario/{id}', 'destroy');
    });

    Route::controller(BitacoraEventosController::class)->group(function () {
        Route::get('/v1/bitacoraEventos/{idPlanta}', 'index');
        Route::get('/v1/bitacoraEventos/{idPlanta}/{id}', 'show');
        Route::post('/v1/bitacoraEventos', 'store');
        Route::post('/v1/bitacoraEventos/{id}', 'update');
        Route::delete('/v1/bitacoraEventos/{id}', 'destroy');
    });

    Route::controller(EventoAlmacenController::class)->group(function () {
        Route::get('/v1/eventoAlmacen/{idPlanta}', 'index');
        Route::get('/v1/eventoAlmacen/{idPlanta}/{id}', 'show');
        Route::post('/v1/eventoAlmacen', 'store');
        Route::post('/v1/eventoAlmacen/{id}', 'update');
        Route::delete('/v1/eventoAlmacen/{id}', 'destroy');
    });

    Route::controller(ExistenciaAlmacenController::class)->group(function () {
        Route::get('/v1/existenciaAlmacen/{idPlanta}', 'index');
        Route::get('/v1/existenciaAlmacen/{idPlanta}/{id}', 'show');
        Route::get('/v1/existenciaAlmacen/verificar/{id}', 'verificar');
        Route::post('/v1/existenciaAlmacen', 'store');
        Route::post('/v1/existenciaAlmacen/{id}', 'update');
    });

    Route::controller(CfdiController::class)->group(function () {
        Route::get('/v1/cfdis/{idPlanta}', 'index');
        Route::post('/v1/cfdis', 'store');
    });

    // Comercializador Instalación
    Route::controller(ComercializadorInstalacionController::class)->group(function () {
        Route::get('/v1/comercializadorInstalacion/{idPlanta}', 'index');
        Route::get('/v1/comercializadorInstalacion/{idPlanta}/{id}', 'show');
        Route::post('/v1/comercializadorInstalacion', 'store');
        Route::post('/v1/comercializadorInstalacion/{id}', 'update');
    });

    // Tanque Virtual
    Route::controller(TanqueVirtualController::class)->group(function () {
        Route::get('/v1/tanqueVirtual/{idPlanta}', 'index');
        Route::get('/v1/tanqueVirtual/{idPlanta}/{id}', 'show');
        Route::post('/v1/tanqueVirtual', 'store');
        Route::post('/v1/tanqueVirtual/{id}', 'update');
        Route::post('/v1/tanqueVirtual/{id}', 'destroy');
    });

    // Evento Tanque Virtual
    Route::controller(EventoTanqueVirtualController::class)->group(function () {
        Route::get('/v1/eventoTanqueVirtual/{idPlanta}', 'index');
        Route::get('/v1/eventoTanqueVirtual/{idPlanta}/{id}', 'show');
        Route::post('/v1/eventoTanqueVirtual', 'store');
        Route::post('/v1/eventoTanqueVirtual/{id}', 'update');
    });

    // Evento CFDI asociado a Tanque Virtual
    Route::controller(EventoCfdiTanqueVirutalController::class)->group(function () {
        Route::get('/v1/eventoCfdiTanqueVirtual/{idPlanta}', 'index');
        Route::get('/v1/eventoCfdiTanqueVirtual/{idPlanta}/{id}', 'show');
        Route::post('/v1/eventoCfdiTanqueVirtual', 'store');
        Route::post('/v1/eventoCfdiTanqueVirtual/{id}', 'update');
    });

    // Bitácora Comercializador
    Route::controller(BitacoraComercializadorController::class)->group(function () {
        Route::get('/v1/bitacoraComercializador/{idPlanta}', 'index');
        Route::get('/v1/bitacoraComercializador/{idPlanta}/{id}', 'show');
        Route::post('/v1/bitacoraComercializador', 'store');
        Route::post('/v1/bitacoraComercializador/{id}', 'update');
        Route::delete('/v1/bitacoraComercializador/{id}', 'destroy');
    });

    // Evento CFDI asociado a Tanque Virtual
    Route::controller(TipoCaracterPlantaController::class)->group(function () {
        Route::get('/v1/eventoCfdiTanqueVirtual/{idPlanta}', 'indexPorInfoGeneral');
        Route::get('/v1/eventoCfdiTanqueVirtual/{idPlanta}/{id}', 'show');
        Route::post('/v1/eventoCfdiTanqueVirtual', 'store');
        Route::post('/v1/eventoCfdiTanqueVirtual/{id}', 'update');
        Route::delete('/v1/eventoCfdiTanqueVirtual/{id}', 'destroy');
    });

    // Productos
    Route::controller(ProductoController::class)->group(function () {
        Route::get('/v1/productos/{idPlanta}', 'index');
        Route::get('/v1/productos/{idPlanta}/{id}', 'show');
        Route::post('/v1/productos', 'store');
        Route::post('/v1/productos/{id}', 'update');
    });

    // Subproductos
    Route::controller(SubproductoController::class)->group(function () {
        Route::get('/v1/subproductos/{idPlanta}', 'index');
        Route::get('/v1/subproductos/{idPlanta}/{id}', 'show');
        Route::post('/v1/subproductos', 'store');
        Route::post('/v1/subproductos/{id}', 'update');
    });

    // Contrapartes
    Route::controller(ControllersContraparteController::class)->group(function () {
        Route::get('/v1/contrapartes', 'index');
        Route::get('/v1/contrapartes/{id}', 'show');
        Route::post('/v1/contrapartes', 'store');
        Route::post('/v1/contrapartes/{id}', 'update');
        Route::delete('/v1/contrapartes/{id}', 'destroy'); // si lo vas a usar
    });

    // Contratos
    Route::controller(ControllersContratoController::class)->group(function () {
        Route::get('/v1/contratos', 'index');
        Route::get('/v1/contratos/{id}', 'show');
        Route::post('/v1/contratos', 'store');
        Route::post('/v1/contratos/{id}', 'update');
        Route::delete('/v1/contratos/{id}', 'destroy'); // si lo vas a usar
    });

    Route::controller(BitacoraComercializacionController::class)->group(function () {
        Route::get('/v1/bitacora-comercializacion', 'index');
        Route::post('/v1/bitacora-comercializacion', 'store');
    });

    Route::controller(FlotaVirtualController::class)->group(function () {
        Route::get('/v1/flota-virtual', 'index');
        Route::get('/v1/flota-virtual/{id}', 'show');
        Route::post('/v1/flota-virtual', 'store');
        Route::post('/v1/flota-virtual/{id}', 'update');
        Route::delete('/v1/flota-virtual/{id}', 'destroy');
    });

    Route::controller(EventoComercializacionController::class)->group(function () {
        Route::get('/v1/eventos-comercio', 'index');
        Route::get('/v1/eventos-comercio/{id}', 'show');
        Route::post('/v1/eventos-comercio', 'store');
        Route::post('/v1/eventos-comercio/{id}', 'update');
        Route::delete('/v1/eventos-comercio/{id}', 'destroy');
    });

    Route::prefix('v1')->group(function () {

        // ===== Dispensarios =====
        Route::controller(DispensarioController::class)->group(function () {
            Route::get('dispensarios/{idPlanta}', 'index');          // lista por planta
            Route::get('dispensarios/{idPlanta}/{id}', 'show');      // ver uno por planta+id
            Route::post('dispensarios', 'store');                    // crear
            Route::post('dispensarios/{id}', 'update');              // actualizar (POST, tu patrón)
            Route::delete('dispensarios/{id}', 'destroy');           // eliminar
        });

        // ===== Medidores (por dispensario) =====
        // Nota: son medidores de dispensario; el filtro es por id_dispensario
        Route::controller(MedidorDispensarioController::class)->group(function () {
            Route::get('medidores/dispensario/{idDispensario}', 'indexByDispensario');      // lista por dispensario
            Route::get('medidores/dispensario/{idDispensario}/{id}', 'show');               // ver uno
            Route::post('medidores', 'store');                                              // crear
            Route::post('medidores/{id}', 'update');                                        // actualizar
            Route::delete('medidores/{id}', 'destroy');                                     // eliminar
        });

        // ===== Mangueras (por dispensario) =====
        // Ojo: aquí corregimos el parámetro, debe ser idDispensario (antes tenías idMedidorDispensario)
        Route::controller(MangueraController::class)->group(function () {
            Route::get('mangueras/dispensario/{idDispensario}', 'indexByDispensario');      // lista por dispensario
            Route::get('mangueras/dispensario/{idDispensario}/{id}', 'show');               // ver una
            Route::post('mangueras', 'store');                                              // crear
            Route::post('mangueras/{id}', 'update');                                        // actualizar
            Route::delete('mangueras/{id}', 'destroy');                                     // eliminar
        });

        Route::controller(BitacoraDispensarioController::class)->group(function () {
            Route::get('bitacora-dispensario/{idPlanta}', 'indexByPlanta');         // listar por planta
            Route::get('bitacora-dispensario/{idPlanta}/{id}', 'show');             // ver detalle
            Route::post('bitacora-dispensario', 'store');                           // crear
            Route::post('bitacora-dispensario/{id}', 'update');                     // actualizar (POST, tu patrón)
            Route::delete('bitacora-dispensario/{id}', 'destroy');                  // eliminar
        });

        // Cortes diarios (Eventos de expendio)
        Route::get('cortes-expendio/{idPlanta}/{fecha}', [CortesExpendioController::class, 'indexByFecha']);
        Route::post('cortes-expendio', [CortesExpendioController::class, 'store']);
        Route::post('cortes-expendio/{id}', [CortesExpendioController::class, 'update']);  // sigues tu patrón POST
        Route::delete('cortes-expendio/{id}', [CortesExpendioController::class, 'destroy']);

        // Captura masiva
        Route::get('cortes-expendio/masivo-base/{idPlanta}/{fecha}', [CortesExpendioController::class, 'masivoBase']);
        Route::post('cortes-expendio/masivo', [CortesExpendioController::class, 'storeMasivo']);

        // Ajustes de totalizador
        Route::post('ajustes-totalizador', [AjusteTotalizadorController::class, 'store']);

        // Previsualización JSON del bloque Expendio (diario)
        Route::get('expendio/preview-json/{idPlanta}/{fecha}', [ExpendioPreviewController::class, 'previewJSON']);


        Route::get('reporte-expendio/{idPlanta}/{yearMonth}/{tipoDM}', [GenReporteExpendioController::class, 'generarReporte'])
            ->name('reporte.expendio.generar');
    });
});
