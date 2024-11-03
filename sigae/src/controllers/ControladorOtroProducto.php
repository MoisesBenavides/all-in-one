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
        $this->otroProducto = new OtroProducto();
        try{
            $this->otroProducto->setDBConnection($rol);
            
            if($rol == 'cliente'){
                $otrosProd = $this->otroProducto->getProductosDisp();
            } elseif($rol == 'gerente' || 'cajero') {
                // Si es funcionario, accede a más datos
                $otrosProd = $this->otroProducto->getProductosDetallados();
            }
        } catch(Exception $e){
            throw $e;
        } finally {
            $this->otroProducto->cerrarDBConnection();
            return $otrosProd;
        }
    }

}