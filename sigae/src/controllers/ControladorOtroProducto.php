<?php

namespace Sigae\Controllers;
use Sigae\Models\OtroProducto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class ControladorOtroProducto extends AbstractController{
    private $otroProducto;

    function getOtrosProductos($rol){
        $otrosProd=[];
        try{
            if($rol == 'cliente'){
                $otrosProd = OtroProducto::getProductosCategoriaDisp($rol);
            } elseif($rol == 'gerente' || 'cajero') {
                // Si es funcionario, accede a más datos
                $otrosProd = OtroProducto::getProductosCategoriaDetallados($rol);
            }
        } catch(Exception $e){
            throw $e;
        } finally {
            return $otrosProd;
        }
    }

}