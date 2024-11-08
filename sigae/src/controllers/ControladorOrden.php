<?php

namespace Sigae\Controllers;
use Sigae\Models\Orden;
use Sigae\Models\Producto;
use Sigae\Models\Servicio;
use Sigae\Models\EstadoPagoOrden;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use DateTimeZone;
use DateTime;
use Exception;

class ControladorOrden extends AbstractController{
    private $orden;

    function submitOrder(): Response{
        $response=['success' => false, 'errors' => []];
        $rol=$_SESSION['rol'];

        $productosId = [];
        $serviciosId = [];

        switch($rol){
            case 'cajero':
                if((isset($_POST["product_ids"]) && !empty($_POST["product_ids"])) || 
                    (isset($_POST["reservation_ids"]) && !empty($_POST["reservation_ids"]))) {

                    if(isset($_POST["id_cliente"]) && !empty($_POST["id_cliente"])){
                        $productosId = $_POST["product_ids"];
                        $serviciosId = $_POST["reservation_ids"];
                        $idCliente = $_POST["id_cliente"];
                        $fecha = $this->obtenerFechaHoraActual();

                        if (!$this->validarFormatoIds($productosId) || !$this->validarFormatoIds($serviciosId)) {
                            $response['errors'][] = "Lista de IDs contiene datos inválidos.";
                        } elseif (!$this->contieneRepeticionesIds($serviciosId)){
                            $response['errors'][] = "Lista de servicios contiene IDs repetidos.";
                        } else {
                            try {
                                // Almacenar en arreglo asociativo los IDs recibidos y sus repeticiones
                                $productosDetalle = [];
                                $conteoProductos = array_count_values($productosId);
                                foreach ($conteoProductos as $id_producto => $cantidad) {
                                    $productosDetalle[] = ['id' => $id_producto, 'cantidad' => $cantidad];
                                }
    
                                $serviciosDetalle = [];
                                foreach ($serviciosId as $id_servicio) {
                                    $serviciosDetalle[] = ['id' => $id_servicio];
                                }
    
                                // Verificar existencia de IDs de productos y servicios
                                $idsNoExistentes = [];
                                foreach ($productosDetalle as $producto){
                                    if (!Producto::existeId($rol, $producto['id'])) {
                                        $idsNoExistentes[] = $producto['id'];
                                    }
                                }
                                foreach ($serviciosDetalle as $servicio){
                                    if (!Servicio::existeId($rol, $servicio['id'])){
                                        $idsNoExistentes[]= $servicio['id'];
                                    }
                                }
    
                                if (!empty($idsNoExistentes)) {
                                    throw new Exception("Los IDs: " . var_dump($idsNoExistentes) . " no se encuentran registrados");
                                } else{
                                    // Calcular el total por cada producto y servicio
                                    $total = 0.00;
                                    foreach ($productosDetalle as $producto) {
                                        $total += Producto::obtenerPrecio($rol, $producto['id']) * $producto['cantidad'];
                                    }
                                    foreach ($serviciosDetalle as $servicio) {
                                        $total += Servicio::obtenerPrecio($rol, $servicio['id']);
                                    }
    
                                    $this->orden = new Orden(null, $total, $fecha, EstadoPagoOrden::tryFrom('pago'));
                                    $this->orden->setDBConnection($rol);
                                    $this->orden->comenzarTransaccion();
                                    try {
                                        $this->orden->preparar($idCliente);

                                        // Agregar detalles de la orden
                                        foreach ($productosDetalle as $producto){
                                            $this->orden->agregarDetalleProducto($producto['id'], $producto['cantidad']);
                                        }
                                        foreach ($serviciosDetalle as $servicio){
                                            $this->orden->agregarDetalleServicio($servicio['id']);
                                        }

                                        $this->orden->confirmarTransaccion();
                                        $response['success'] = true;

                                        return $this->render('employee/cashier/confirmacionOrden.html.twig', [
                                            'orden' => $this->orden
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

    function orderConfirmation(){
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'cajero':
                // TODO: Implementar el código para procesar la orden de compra y generar el número de orden
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    private function obtenerFechaHoraActual(){
        $uruguayTimezone = new DateTimeZone('America/Montevideo');
        $dtActual = new DateTime('now', $uruguayTimezone);

        // Formatear la fecha
        return $dtActual->format('Y-m-d H:i:s');
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