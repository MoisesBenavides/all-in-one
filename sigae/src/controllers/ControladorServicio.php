<?php

namespace Sigae\Controllers;
use Sigae\Models\Servicio;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;

class ControladorServicio extends AbstractController{

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
            throw $e;
        }
    }

}