<?php

namespace Sigae\Controllers;
use Sigae\Models\Orden;
use Sigae\Models\Producto;
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

                        if(!$this->validarListaIds($productosId) || !$this->validarListaIds($serviciosId)){
                        $response['errors'][] = "Lista de IDs contiene datos inválidos";
                        } else{
                            try{
                                $idsNoExistentes = [];
                                foreach($productosId as $id_producto){
                                    if (!Producto::existeId($rol, $id_producto)){
                                        $idsNoExistentes = " ".$id_producto;
                                    }
                                    if (!empty($idsNoExistentes)){
                                        throw new Exception("Los ids: ".$idsNoExistentes." no se encuentran registrados");
                                    }
                                    // TODO: implementar
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

    private function validarListaIds($idsRecibidos){
        // Verifica si los elementos del arreglo son numéricos enteros mayores a cero
        $idsValidos = array_filter($idsRecibidos, fn($id) => ctype_digit($id) && (int)$id > 0);
        // Verifica si los arrays validados contienen la misma cantidad de elementos originales
        return count($idsRecibidos) == count($idsValidos);
    }

}