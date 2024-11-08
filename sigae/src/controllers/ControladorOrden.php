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
                if(isset($_POST["product_ids"]) || isset($_POST["reservation_ids"]) && 
                !empty($_POST["product_ids"]) || !empty($_POST["reservation_ids"])){

                    if(isset($_POST["id_cliente"]) && !empty($_POST["id_cliente"])){
                        $productosId = $_POST["product_ids"];
                        $serviciosId = $_POST["reservation_ids"];
                        $idCliente = $_POST["id_cliente"];
                        $fecha = $this->obtenerFechaHoraActual();

                        /* TODO: Valorar casos de solo ids productos recibidos, solo servicios, o ambos 
                        (implementar para futuros procesos y comunicacion con modelo orden) */

                        if(!$this->validarFormatoIds($productosId) || !$this->validarFormatoIds($serviciosId)){
                            $response['errors'][] = "Lista de IDs contiene datos inválidos.";
                        } elseif(!$this->validarFormatoIds($serviciosId)){
                            $response['errors'][] = "Lista de servicios contiene IDs repetidos.";
                        } else{
                            try{
                                /* TODO: Hacer arrays asociativos de productos con ids y cantidad, 
                                no usar más en adelante array de productos con solo ids (reemplazar cuando se implemente)*/
                                
                                $idsNoExistentes = "";
                                foreach($productosId as $id_producto){
                                    if (!Producto::existeId($rol, $id_producto)){
                                        // TODO: Agregar espaciado entre IDs
                                        $idsNoExistentes .= $id_producto;
                                    }
                                }
                                foreach($serviciosId as $id_servicio){
                                    if (!Servicio::existeId($rol, $id_servicio)){
                                        // TODO: Agregar espaciado entre IDs
                                        $idsNoExistentes .= $id_servicio;
                                    }
                                }
                                if (!empty($idsNoExistentes)){
                                    throw new Exception("Los IDs: ".$idsNoExistentes." no se encuentran registrados");
                                } else{
                                    // Calcular el total por cada producto y servicio
                                    $total = 0.00;
                                    foreach($productosId as $id_producto){
                                        $total+=Producto::obtenerPrecio($rol, $id_producto);
                                    }
                                    foreach($serviciosId as $id_servicio){
                                        $total+=Servicio::obtenerPrecio($rol, $id_servicio);
                                    }

                                    $this->orden = new Orden(null, $total, $fecha, EstadoPagoOrden::tryFrom('pago'));
                                    $this->orden->setDBConnection($rol);
                                    $this->orden->comenzarTransaccion();
                                    try{
                                        $this->orden->preparar($idCliente);
                                        foreach($productosId as $producto_id){
                                            $this->orden->agregarDetalleProducto($producto_id, $cantidad);
                                        }
                                        foreach($serviciosId as $servicio_id){
                                            $this->orden->agregarDetalleServicio($servicio_id);
                                        }
                                        $this->orden->confirmarTransaccion();
                                        
                                    } catch(Exception $e){
                                        $this->orden->deshacerTransaccion();
                                        throw $e;
                                    } finally{
                                        $this->orden->cerrarDBConnection();
                                    }
                                }

                            } catch(Exception $e){
                                $response['errors'][] = $e->getMessage();
                            }
                            
                        }
                    } else{
                        $response['errors'][] = "Debe ingresar el ID del cliente.";
                    }                    

                }else{
                    $response['errors'][] = "La orden debe contener al menos un ID de producto o reserva.";
                }
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