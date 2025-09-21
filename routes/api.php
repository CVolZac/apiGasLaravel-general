<?php

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

use App\Http\Controllers\TipoCaracterPlantaController;


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


    //Registro Información Generar del reporte
    Route::controller(InformacionGeneralReporteController::class)->group(function () {
        Route::get('/v1/reporteVolumetrico/informacion-general/{idPlanta}', 'index');
        Route::get('/v1/reporteVolumetrico/informacion-general/{id}', 'show');
        Route::post('/v1/reporteVolumetrico/informacion-general', 'store');
        Route::post('/v1/reporteVolumetrico/informacion-general/{id}', 'update');
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
    });

    // Evento CFDI asociado a Tanque Virtual
    Route::controller(TipoCaracterPlantaController::class)->group(function () {
        Route::get('/v1/eventoCfdiTanqueVirtual/{idPlanta}', 'indexPorInfoGeneral');
        Route::get('/v1/eventoCfdiTanqueVirtual/{idPlanta}/{id}', 'show');
        Route::post('/v1/eventoCfdiTanqueVirtual', 'store');
        Route::post('/v1/eventoCfdiTanqueVirtual/{id}', 'update');
        Route::delete('/v1/eventoCfdiTanqueVirtual/{id}', 'destroy');
    });
});
