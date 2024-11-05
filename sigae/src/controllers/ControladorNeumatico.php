<?php

namespace Sigae\Controllers;
use Sigae\Models\Neumatico;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class ControladorNeumatico extends AbstractController{
    private $neumatico;

    function getNeumaticos($rol){
        $neumaticos=[];
        try{
            if($rol == 'cliente'){
                $neumaticos = Neumatico::getProductosCategoriaDisp($rol);
            } elseif ($rol == 'gerente' || 'cajero') {
                // Si es funcionario, accede a más datos
                $neumaticos = Neumatico::getProductosCategoriaDetallados($rol);
            }
        } catch(Exception $e){
            throw $e;
        } finally {
            return $neumaticos;
        }
    }

}