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
            if($rol == 'cliente'){
                $this->neumatico->setDBConnection("cliente");
                $neumaticos = $this->neumatico->getProductosDisp();
            } elseif ($rol == 'gerente' || 'cajero') {
                // Si es funcionario, usar credenciales por rol y accede a más datos
                $this->neumatico->setDBConnection($rol);
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