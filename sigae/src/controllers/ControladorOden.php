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
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'cajero':
                // TODO: Implementar el código para procesar la orden de compra y generar el número de orden
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
}