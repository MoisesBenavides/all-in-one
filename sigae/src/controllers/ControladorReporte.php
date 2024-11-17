<?php

namespace Sigae\Controllers;
use Sigae\Models\TipoVehiculo;
use Sigae\Controllers\ControladorTaller;
use Sigae\Controllers\ControladorOrden;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use DateTimeZone;
use DateTime;
use PDOException;
use Exception;

class ControladorReporte extends AbstractController {
    private $controladorTaller;
    private $controladorOrden;
    
    function showWorkshopAvailability(): Response{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                return $this->render('employee/manager/reports/horariosDispTaller.html.twig');
            case 'jefe_taller':
                return $this->render('employee/workshopChief/reports/horariosDispTaller.html.twig');
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showWorkshopServices(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                try{                    
                    $this->controladorTaller = new ControladorTaller();
                    $serviciosDisp = $this->controladorTaller->getServiciosDisp();

                } catch(Exception $e){
                    $response['errors'][] = "Error obteniendo los servicios disponibles: ".$e->getMessage();
                }
                return $this->render('employee/manager/reports/serviciosDispTaller.html.twig', [
                    'response' => $response,
                    'serviciosDisp' => $serviciosDisp
                ]);
            case 'jefe_taller':
                try{                    
                    $this->controladorTaller = new ControladorTaller();
                    $serviciosDisp = $this->controladorTaller->getServiciosDisp();
    
                } catch(Exception $e){
                    $response['errors'][] = "Error obteniendo los servicios disponibles: ".$e->getMessage();
                }
                return $this->render('employee/workshopChief/reports/serviciosDispTaller.html.twig', [
                    'response' => $response,
                    'serviciosDisp' => $serviciosDisp
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showProductSalesReport(): Response{
        $response=['success' => false, 'errors' => []];
        
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                try{
                    $this->controladorOrden = new ControladorOrden();
                    $fechaActual = $this->obtenerFechaHoraActual();

                    // Obtener información de ventas de productos, por predeterminado, mensual
                    $infoPorProducto = $this->controladorOrden->obtenerIngresosBrutosProd($rol, 'ultimo_mes', $fechaActual);

                    // Obtener información adicional para el reporte
                    $ingresosBrutosTotal = 0;
                    $cantidadVendidos = 0;

                    foreach($infoPorProducto as $producto){
                        $ingresosBrutosTotal += $producto['ingreso_bruto'];
                        $cantidadVendidos += $producto['cant_vendidos'];
                    }

                    $detallesReporte = [
                        'ingresosBrutosTotal' => $ingresosBrutosTotal,
                        'cantidadVendidos' => $cantidadVendidos,
                        'infoProductos' => $infoPorProducto
                    ];

                    return $this->render('employee/manager/reports/ventasProducto.html.twig', [
                        'response' => $response,
                        'detallesReporte' => $detallesReporte
                    ]);

                } catch(Exception $e){
                    $response['errors'][] = "Error obteniendo reporte de ventas de productos: ".$e->getMessage();
                }
                return $this->render('employee/manager/reportes.html.twig', [
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showParkingSalesReport(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                try{
                    $this->controladorOrden = new ControladorOrden();
                    $fechaActual = $this->obtenerFechaHoraActual();

                    // Obtener información de ventas de productos, por predeterminado, mensual
                    $infoPorReserva = $this->controladorOrden->obtenerIngresosBrutosReserva($rol, 'ultimo_mes', $fechaActual);

                    // Obtener información adicional para el reporte
                    $ingresosBrutosTotal = 0;
                    $cantidadReservas = 0;
                    
                    // Cantidad de reservas e ingresos de parking por tipo
                    $reservasRegulares = 0;
                    $ingresosReservasRegulares = 0;
                    $reservasLargoPlazo = 0;
                    $ingresosReservasLargoPlazo = 0;


                    // Datos filtrando el tipo de vehículo asociado a las reservas
                    // Inicializar arreglos usando el enum
                    $cantidadPorTipoVehiculo = [];
                    $ingresosPorTipoVehiculo = [];

                    // Poblar los arreglos con los casos del enum
                    foreach (TipoVehiculo::cases() as $tipo) {
                        $cantidadPorTipoVehiculo[$tipo->value] = 0;
                        $ingresosPorTipoVehiculo[$tipo->value] = 0;
                    }

                    // Iterar sobre los datos de las reservas y actualizar los contadores
                    foreach ($infoPorReserva as $reserva) {
                        $ingresosBrutosTotal += $reserva['ingreso_bruto'];
                        $cantidadReservas++;

                        // Parsear el tipo de vehículo a un caso del enum si es válido
                        $tipoVehiculo = $reserva['tipo_vehiculo'];
                        if (TipoVehiculo::tryFrom($tipoVehiculo) !== null) {
                            $cantidadPorTipoVehiculo[$tipoVehiculo]++;
                            $ingresosPorTipoVehiculo[$tipoVehiculo] += $reserva['ingreso_bruto'];
                        }
                        // Verificar si la reserva es regular o de largo plazo
                        if ($reserva['largo_plazo'] === 0) {
                            $reservasRegulares++;
                            $ingresosReservasRegulares += $reserva['ingreso_bruto'];
                        } else {
                            $reservasLargoPlazo++;
                            $ingresosReservasLargoPlazo += $reserva['ingreso_bruto'];
                        }
                    }

                    $detallesReporte = [
                        'ingresosBrutosTotal' => $ingresosBrutosTotal,
                        'cantidadReservas' => $cantidadReservas,
                        'cantidadPorTipoVehiculo' => $cantidadPorTipoVehiculo,
                        'ingresosPorTipoVehiculo' => $ingresosPorTipoVehiculo,
                        'reservasRegulares' => $reservasRegulares,
                        'ingresosReservasRegulares' => $ingresosReservasRegulares,
                        'reservasLargoPlazo' => $reservasLargoPlazo,
                        'ingresosReservasLargoPlazo' => $ingresosReservasLargoPlazo,
                        'infoReservas' => $infoPorReserva
                    ];

                    return $this->render('employee/manager/reports/ventasParking.html.twig', [
                        'response' => $response,
                        'detallesReporte' => $detallesReporte
                    ]);

                } catch(Exception $e){
                    $response['errors'][] = "Error obteniendo reporte de estacionamiento: ".$e->getMessage();
                }
                return $this->render('employee/manager/reportes.html.twig', [
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showWorkshopServicesSalesReport(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                try{
                    $this->controladorOrden = new ControladorOrden();
                    $fechaActual = $this->obtenerFechaHoraActual();

                    // Obtener información de ventas de productos, por predeterminado, mensual
                    $infoPorServicio = $this->controladorOrden->obtenerIngresosBrutosTaller($rol, 'ultimo_mes', $fechaActual);

                    // Obtener información adicional para el reporte
                    $ingresosBrutosTotal = 0;
                    $cantidadServicios = 0;

                    // Obtener tipos o códigos de servicio
                    $this->controladorTaller = new ControladorTaller();
                    $serviciosDisp = $this->controladorTaller->getServiciosDisp();

                    // Datos filtrando el tipo de servicio asociado a la reserva de servicio de taller                    
                    $cantidadPorTipoServicio = [];
                    $ingresosPorTipoServicio = [];

                    // Inicializar arreglos con los tipos de servicios disponibles
                    foreach ($serviciosDisp as $codigo => $servicio){
                        $cantidadPorTipoServicio[$codigo] = [
                            'descripcion' => $servicio['descripcion'],
                            'cantidad' => 0
                        ];
                        $ingresosPorTipoServicio[$codigo] = [
                            'descripcion' => $servicio['descripcion'],
                            'ingresos_brutos' => 0
                        ];
                    }

                    // Datos filtrando el tipo de vehículo asociado a los servicios
                    // Inicializar arreglos usando el enum
                    $cantidadPorTipoVehiculo = [];
                    $ingresosPorTipoVehiculo = [];

                    // Poblar los arreglos con los casos del enum
                    foreach (TipoVehiculo::cases() as $tipo) {
                        $cantidadPorTipoVehiculo[$tipo->value] = 0;
                        $ingresosPorTipoVehiculo[$tipo->value] = 0;
                    }

                    // Itera sobre datos de los servicios y actualiza los contadores
                    foreach ($infoPorServicio as $servicio){
                        $ingresosBrutosTotal += $servicio['ingreso_bruto'];
                        $cantidadServicios++;

                        // Parsear el tipo de vehículo a un caso del enum, si es valido
                        $tipoVehiculo = $servicio['tipo_vehiculo'];
                        if (TipoVehiculo::tryFrom($tipoVehiculo) !== null) {
                            $cantidadPorTipoVehiculo[$tipoVehiculo]++;
                            $ingresosPorTipoVehiculo[$tipoVehiculo] += $servicio['ingreso_bruto'];
                        }

                        $tipo = $servicio['tipo'];
                        if (array_key_exists($tipo, $cantidadPorTipoServicio)) {
                            $cantidadPorTipoServicio[$tipo]['cantidad']++;
                            $ingresosPorTipoServicio[$tipo]['ingresos_brutos'] += $servicio['ingreso_bruto'];
                        }
                    }

                    $detallesReporte = [
                        'ingresosBrutosTotal' => $ingresosBrutosTotal,
                        'cantidadServicios' => $cantidadServicios,
                        'cantidadPorTipoServicio' => $cantidadPorTipoServicio,
                        'ingresosPorTipoServicio' => $ingresosPorTipoServicio,
                        'cantidadPorTipoVehiculo' => $cantidadPorTipoVehiculo,
                        'ingresosPorTipoVehiculo' => $ingresosPorTipoVehiculo,
                        'infoServicios' => $infoPorServicio
                    ];

                    return $this->render('employee/manager/reports/ventasTaller.html.twig', [
                        'response' => $response,
                        'detallesReporte' => $detallesReporte
                    ]);

                } catch(Exception $e){
                    $response['errors'][] = "Error obteniendo reporte de estacionamiento: ".$e->getMessage();
                }
                return $this->render('employee/manager/reportes.html.twig', [
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    private function obtenerFechaHoraActual(): String{
        $uruguayTimezone = new DateTimeZone('America/Montevideo');
        $fechaActual = new DateTime('now', $uruguayTimezone);

        // Formatear la fecha
        return $fechaActual->format('Y-m-d H:i:s');
    }
}