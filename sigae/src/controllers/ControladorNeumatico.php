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
                $this->neumatico->setDBConnection("def_cliente", "password_cliente", "localhost");
                $neumaticos = $this->neumatico->getProductosDisp();
            } else {
                // TODO: Si es funcionario, usar credenciales por rol y accede a más datos
                $this->neumatico->setDBConnection("def_cliente", "password_cliente", "localhost");
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