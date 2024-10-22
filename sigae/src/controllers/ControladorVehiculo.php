<?php

namespace Sigae\Controllers;
use Sigae\Models\Vehiculo;
use Sigae\Models\TipoVehiculo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ControladorVehiculo extends AbstractController{
    private $vehiculo;

    function registrarYaVehiculo($matricula, $tipoVehiculo, $id_cliente){
        if (Vehiculo::existeMatricula($matricula)) {
            return false; // False si la matrícula ya existe
        } else {
            $this->vehiculo = new Vehiculo($matricula, null, null, $tipoVehiculo, null);
            return $this->vehiculo->registrarYa($id_cliente);
        }
    
    }

    function borrarVehiculo($matricula): Response {
        $response = ['success' => false, 'errors' => [], 'debug' => []];
    
        // Debug: Log all received data
        $response['debug']['received_data'] = $_POST;
    
        // Verificar si la matrícula existe en la base de datos
        if (!Vehiculo::existeMatricula($matricula)) {
            $response['errors'][] = "La matrícula ingresada no existe.";
        } elseif (empty($matricula)) {
            $response['errors'][] = "Debe ingresar una matrícula válida.";
        } else {
            // Crear una instancia del vehículo con la matrícula
            $this->vehiculo = new Vehiculo($matricula, null, null, null, null);
    
            // Intentar borrar el vehículo
            if (!$this->vehiculo->borrar()) {
                $response['errors'][] = "Ocurrió un error al desvincular el vehículo.";
            } else {
                // Si todo sale bien, indicar éxito
                $response['success'] = true;
                return $this->render('client/vistaDeMisVehiculos.html.twig', [
                    'response' => $response
                ]); // TODO: Ajustar path vista si es necesario
            }
        }
    
        // Si hubo errores, renderizar la vista con los mensajes de error
        return $this->render('client/vistaDeMisVehiculos.html.twig', [
            'response' => $response
        ]);
    }

    function validarTipoVehiculo($tipoVehiculo){
        // Valida si el tipo de vehiculo a partir del enum TipoVehiculo.php
        return TipoVehiculo::tryFrom($tipoVehiculo) !== null;
    }

    function validarMatricula($str){
        return preg_match("/^[a-zA-Z0-9]{4,8}$/", $str);
    }
}