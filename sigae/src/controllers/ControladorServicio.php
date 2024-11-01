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
            if (!Servicio::existeId($rol, $id)){
                throw new Exception("No existe un servicio registrado con el ID: " . $id);
            }

            $estadoServicio = Servicio::obtenerEstadoActual($rol, $id);

            if ($estadoServicio == 'realizado'){
               throw new Exception("El servicio ya fue realizado.");
            } elseif($estadoServicio == 'cancelado'){
                throw new Exception("El servicio ya fue cancelado.");
            } elseif($estadoServicio == 'pendiente'){
                Servicio::cancelar($rol, $id);
            }
            
        } catch(Exception $e){
            error_log("Error cancelando el servicio: ". $e->getMessage());
            throw $e;
        }
    }

}