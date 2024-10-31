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
            if($rol == 'cliente'){
                $this->otroProducto->setDBConnection("cliente");
                $otrosProd = $this->otroProducto->getProductosDisp();
            } else {
                // TODO: Si es funcionario, usar credenciales por rol y accede a más datos
                $this->otroProducto->setDBConnection("cliente");
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