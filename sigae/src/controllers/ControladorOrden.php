<?php

namespace Sigae\Controllers;
use Sigae\Models\Orden;
use Sigae\Models\Producto;
use Sigae\Models\Servicio;
use Sigae\Models\EstadoPagoOrden;
use Sigae\Controllers\ControladorProducto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use DateTimeZone;
use DateTime;
use Exception;

class ControladorOrden extends AbstractController{
    private $orden;
    private $controladorProducto;

    function submitOrder(): Response{
        $response=['success' => false, 'errors' => []];
        $rol=$_SESSION['rol'];

        $productosId = [];
        $serviciosId = [];

        switch($rol){
            case 'cajero':
                $incluyeProductos = isset($_POST["product_ids"]) && !empty($_POST["product_ids"]);
                $contieneServicios = isset($_POST["reservation_ids"]) && !empty($_POST["reservation_ids"]);

                if($incluyeProductos || $contieneServicios) {

                    if(isset($_POST["id_cliente"]) && !empty($_POST["id_cliente"])){
                        $productosId = $_POST["product_ids"] ?? null;
                        $serviciosId = $_POST["reservation_ids"] ?? null;
                        $idCliente = $_POST["id_cliente"];
                        $fecha = $this->obtenerFechaHoraActual();

                        

                        if ($incluyeProductos && !$this->validarFormatoIds($productosId) || 
                            $contieneServicios && !$this->validarFormatoIds($serviciosId)) {
                            $response['errors'][] = "Lista de IDs contiene datos inválidos.";
                        } elseif ($contieneServicios && !$this->contieneRepeticionesIds($serviciosId)){
                            $response['errors'][] = "Lista de servicios contiene IDs repetidos.";
                        } else {
                            try {
                                $productosDetalle = [];
                                $serviciosDetalle = [];
                                $idsNoExistentes = [];

                                if ($incluyeProductos){
                                    $conteoProductos = array_count_values($productosId);
                                    foreach ($conteoProductos as $id_producto => $cantidad) {
                                        $productosDetalle[] = ['id' => $id_producto, 'cantidad' => $cantidad];
                                    }
                                    foreach ($productosDetalle as $producto){
                                        // Verificar existencia de IDs de productos
                                        if (!Producto::existeId($rol, $producto['id'])) {
                                            $idsNoExistentes[] = $producto['id'];
                                        }
                                    }
                                }
                                
                                if ($contieneServicios){
                                    foreach ($serviciosId as $id_servicio) {
                                        $serviciosDetalle[] = ['id' => $id_servicio];
                                    }
                                    foreach ($serviciosDetalle as $servicio){
                                        // Verificar existencia de IDs de servicios
                                        if (!Servicio::existeId($rol, $servicio['id'])){
                                            $idsNoExistentes[]= $servicio['id'];
                                        }
                                    }
                                }

                                if (!empty($idsNoExistentes)) {
                                    throw new Exception("Los IDs: " . var_dump($idsNoExistentes) . " no se encuentran registrados");
                                } else{
                                    // Calcular el total por cada producto y servicio
                                    $total = 0.00;

                                    if ($incluyeProductos){
                                        foreach ($productosDetalle as $producto) {
                                            $total += Producto::obtenerPrecio($rol, $producto['id']) * $producto['cantidad'];
                                        }
                                    }
                                    if ($contieneServicios){
                                        foreach ($serviciosDetalle as $servicio) {
                                            $total += Servicio::obtenerPrecio($rol, $servicio['id']);
                                        }
                                    }

                                    $this->orden = new Orden(null, $total, $fecha, EstadoPagoOrden::tryFrom('pago'));
                                    $this->orden->setDBConnection($rol);
                                    $this->orden->comenzarTransaccion();
                                    try {
                                        $this->orden->preparar($idCliente);

                                        // Agregar detalles de la orden y modificar stock si se incluyen productos
                                        if ($incluyeProductos){
                                            $this->controladorProducto=new ControladorProducto();
                                            $this->controladorProducto->setDBConnection($this->orden->getDBConnection());
                                            foreach ($productosDetalle as $producto){
                                                $this->orden->agregarDetalleProducto($producto['id'], $producto['cantidad']);
                                                // Hacer baja de stock de productos
                                                $this->controladorProducto->restarStock($producto['id'], $producto['cantidad']);
    
                                            }
                                        }
                                        
                                        if ($contieneServicios){
                                            foreach ($serviciosDetalle as $servicio){
                                                $this->orden->agregarDetalleServicio($servicio['id']);
                                            }
                                        }                

                                        $this->orden->confirmarTransaccion();
                                        $response['success'] = true;

                                        $ordenConfirm = [
                                            'id' => $this->orden->getId(),
                                            'id_cliente' => $idCliente,
                                            'total' => $this->orden->getTotal(),
                                            'fecha_orden' => $this->orden->getFecha_orden(),
                                            'productos' => ($incluyeProductos) ? $productosDetalle : null,
                                            'servicios' => ($contieneServicios) ? $serviciosDetalle : null,
                                        ];

                                        return $this->render('employee/cashier/confirmacionOrden.html.twig', [
                                            'orden' => $ordenConfirm
                                        ]);

                                    } catch(Exception $e){
                                        $this->orden->deshacerTransaccion();
                                        $response['errors'][] = "Error al procesar la orden: " . $e->getMessage();
                                    }
                                }
                            } catch (Exception $e) {
                                $response['errors'][] = $e->getMessage();
                            }
                        }
                    } else {
                        $response['errors'][] = "Debe ingresar el ID del cliente.";
                    }
                } else {
                    $response['errors'][] = "La orden debe contener al menos un ID de producto o reserva.";
                }

                return $this->render('employee/cashier/preparacionOrden.html.twig', [
                    'response' => $response
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function obtenerIngresosBrutosProd($rol, $tipPeriodo){
        try{
            $fechaActual = $this->obtenerFechaHoraActual();
            $dtActual = new DateTime($fechaActual);
            switch($tipPeriodo){
                case'mensual':
                    // Obtiene el inicio del mes anterior
                    // $principioMes = $dtActual->modify('-1 month');
                    $principioMes = $dtActual;
                    $principioMes->modify('first day of this month');
                    $fechaIni = $principioMes->format('Y-m-d H:i:s');

                    // Obtiene el último segundo del mes anterior
                    $finMes = clone $principioMes;
                    $finMes->modify('+1 month -1 second');
                    $fechaFin = $finMes->format('Y-m-d H:i:s');

                    return Orden::obtenerIngresosBrutosProd($rol, $fechaIni, $fechaFin);
                default:
                    throw new Exception("Tipo de período no válido.");
            }
        } catch(Exception $e){
            throw $e->getMessage();
        }
    }

    function obtenerIngresosBrutosReserva($rol, $tipPeriodo){
        $infoPorReserva = [];
        return $infoPorReserva;
    }

    private function obtenerFechaHoraActual(): String{
        $uruguayTimezone = new DateTimeZone('America/Montevideo');
        $fechaActual = new DateTime('now', $uruguayTimezone);

        // Formatear la fecha
        return $fechaActual->format('Y-m-d H:i:s');
    }

    private function validarFormatoIds($idsRecibidos){
        // Verifica si los elementos del arreglo son numéricos enteros mayores a cero
        $idsValidos = array_filter($idsRecibidos, fn($id) => ctype_digit($id) && (int)$id > 0);
        // Verifica si el arreglo de ids validados contienen la misma cantidad de elementos originales
        return count($idsRecibidos) == count($idsValidos);
    }

    private function contieneRepeticionesIds($idsRecibidos){
        // Obtiene array con ids que no se repiten
        $idsUnicos = array_unique($idsRecibidos);
        // Verifica si el arreglo de ids unicos contienen la misma cantidad de elementos originales
        return count($idsUnicos) == count($idsRecibidos);
    }

}