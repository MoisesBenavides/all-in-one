<?php

namespace Sigae\Controllers;
use Sigae\Models\Orden;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class ControladorOrden extends AbstractController{
    private $orden;

    function submitOrder(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];

        $productos = [];
        $services = [];

        switch($rol){
            case 'cajero':
                if(isset($_POST["product_ids"]) || isset($_POST["reservation_ids"]) && 
                !empty($_POST["product_ids"]) || !empty($_POST["reservation_ids"])){

                    $productos = $_POST["product_ids"];
                    $servicios = $_POST["reservation_ids"];

                    if(!$this->validarListaIds($productos) || !$this->validarListaIds($servicios)){
                       $response['errors'][] = "Lista de IDs contiene datos inválidos";
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

    private function validarListaIds($array){
        return is_array($array);
    }

}