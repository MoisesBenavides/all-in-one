<?php

namespace Sigae\Controllers;
use Sigae\Models\Servicio;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use PDOException;
use Exception;

class ControladorServicio extends AbstractController{
    private $servicio;

    public function cancelarServicio($rol, $id){
        try{
            Servicio::cancelar($rol, $id);
        } catch(Exception $e){
            error_log("Error cancelando el servicio: ". $e->getMessage());
            throw $e;
        }
    }

}