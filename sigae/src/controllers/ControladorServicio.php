<?php

namespace Sigae\Controllers;
use Sigae\Models\Servicio;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use DateTime;
use DateTimeZone;

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
                // Obtiene la fecha y hora de comienzo del servicio
                $fechaInicio = Servicio::obtenerFechaInicio($rol, $id);
                $dtServicio = new DateTime($fechaInicio);

                // Calcula y asigna una hora antes del comienzo
                $dtServicio->modify('-1 hour');

                // Obtiene la hora actual en Montevideo
                $uruguayTimezone = new DateTimeZone('America/Montevideo');
                $dtActual = new DateTime('now', $uruguayTimezone);

                // Debug: Formatear las fechas para que se impriman correctamente en los logs
                error_log('Fecha y hora actual: ' . $dtActual->format('Y-m-d H:i:s'));
                error_log('Fecha y hora de inicio (1 hora antes): ' . $dtServicio->format('Y-m-d H:i:s'));

                // Comparar si la fecha y hora actual es posterior a una hora antes del comienzo del servicio
                if ($dtActual > $dtServicio) {
                    throw new Exception("Sólo se admite cancelar hasta una hora antes del comienzo del servicio.");
                } else{
                    Servicio::cancelar($rol, $id);
                }
            }    
        } catch(Exception $e){
            throw $e;
        }
    }

}