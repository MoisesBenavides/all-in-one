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
        $this->neumatico = new Neumatico();
        try{
            $this->neumatico->setDBConnection($rol);
            
            if($rol == 'cliente'){
                $neumaticos = $this->neumatico->getProductosDisp();
            } elseif ($rol == 'gerente' || 'cajero') {
                // Si es funcionario, accede a más datos
                $neumaticos = $this->neumatico->getProductosDetallados();
            }
        } catch(Exception $e){
            throw $e;
        } finally {
            $this->neumatico->cerrarDBConnection();
            return $neumaticos;
        }
    }

}