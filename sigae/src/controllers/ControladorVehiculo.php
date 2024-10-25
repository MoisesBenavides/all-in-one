<?php

namespace Sigae\Controllers;
use Sigae\Models\Vehiculo;
use Sigae\Models\TipoVehiculo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ControladorVehiculo extends AbstractController{
    private $vehiculo;

    public function addVehicle(): Response|RedirectResponse{
        session_start();

        $response=['success' => false, 'errors' => []];

        $cliente = [
            'id' => $_SESSION['id'],
            'email' => $_SESSION['email'],
            'nombre' => $_SESSION['nombre'],
            'apellido' => isset($_SESSION['apellido']) ? $_SESSION['apellido'] : null,
            'telefono' => isset($_SESSION['telefono']) ? $_SESSION['telefono'] : null,
            'fotoPerfil' => isset($_SESSION['fotoPerfil']) ? $_SESSION['fotoPerfil'] : null
        ];
        $misVehiculos = Vehiculo::cargarMisVehiculos($_SESSION['id']);
        $id_cliente = $_SESSION['id'];
        // Validacion de campos vacios
        if (isset($_POST["matricula"], $_POST["tipo"]) && 
            !empty($_POST["matricula"]) && !empty($_POST["tipo"])) {

            $matricula = $_POST["matricula"];
            $tipo = $_POST["tipo"];
            $marca = isset($_POST["marca"]) ? $_POST["marca"] : null;
            $modelo = isset($_POST["modelo"]) ? $_POST["modelo"] : null;
            $colorConHash = isset($_POST["color"]) ? $_POST["color"] : null;
            $color = substr($colorConHash, 1);

            // Validar email
            if (!$this->validarMatricula($matricula)) {
                $response['errors'][] = "Por favor, ingrese una matrícula válida.";
            } elseif (!$this->validarTipoVehiculo($tipo)) {
                $response['errors'][] = "Por favor, ingrese un tipo de vehículo válido.";
            } elseif (isset($marca) && !empty($marca) && !$this->validarMarcaModelo($marca, 32)) {
                $response['errors'][] = "Por favor, ingrese una marca válida.";
            } elseif (isset($modelo) && !empty($modelo) && !$this->validarMarcaModelo($modelo, 32)) {
                $response['errors'][] = "Por favor, ingrese un modelo válido.";
            } elseif (isset($color) && !empty($color) && !$this->validarColorHexa($color)) {
                $response['errors'][] = "Por favor, ingrese un código de color válido.";
            } else {
                // Instncia vehiculo con datos ingresados
                $this->vehiculo = new Vehiculo($matricula, $marca, $modelo, $tipo, $color);

                if (!$this->vehiculo->create()) {
                    $response['errors'][] = "Error al registrar vehículo.";
                } elseif (!$this->vehiculo->vincularCliente($id_cliente)){
                    $response['errors'][] = "Error al vincular vehículo.";
                } else {
                    // Debug: Registro y vinculación exitosa
                    $response['success'] = true;

                    // Recargar página
                    return $this->redirectToRoute('myAccount');
                }
            }
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
        }

        return $this->render('client/miCuenta.html.twig', [
            'cliente' => $cliente, // Pasa variables de sesión de cliente
            'misVehiculos' => $misVehiculos, // Pasa vehículos actualizados del cliente
            'response' => $response  // Aquí pasa la respuesta a la vista
        ]);
    }

    public function deleteVehicle($matricula): Response {
        $response = ['success' => false, 'errors' => [], 'debug' => []];
    
        // Verificar si la matrícula existe en la base de datos
        if (!Vehiculo::existeMatricula($matricula)) {
            $response['errors'][] = "La matrícula ingresada no existe.";
        } elseif (empty($matricula)) {
            $response['errors'][] = "Debe ingresar una matrícula válida.";
        } else {
            // Crear una instancia del vehículo con la matrícula
            $this->vehiculo = new Vehiculo($matricula, null, null, null, null);
    
            // Intentar borrar el vehículo
            if (!$this->vehiculo->delete()) {
                $response['errors'][] = "Ocurrió un error al desvincular el vehículo.";
            } else {
                // Si todo sale bien, indicar éxito
                $response['success'] = true;
                return $this->render('client/vistaDeMisVehiculos.html.twig', [
                    'response' => $response
                ]);
            }
        }
    
        // Si hubo errores, renderizar la vista con los mensajes de error
        return $this->render('client/vistaDeMisVehiculos.html.twig', [
            'response' => $response
        ]);
    }

    function registrarYaVehiculo($matricula, $tipoVehiculo, $id_cliente){
        if (Vehiculo::existeMatricula($matricula)) {
            return false; // False si la matrícula ya existe
        } else {
            $this->vehiculo = new Vehiculo($matricula, null, null, $tipoVehiculo, null);
            return $this->vehiculo->registrarYa($id_cliente);
        }
    
    }

    function validarTipoVehiculo($tipoVehiculo){
        // Valida si el tipo de vehículo a partir del enum TipoVehiculo.php
        return TipoVehiculo::tryFrom($tipoVehiculo) !== null;
    }

    private function validarColorHexa($hex){
        // Valida si el color de vehículo es un código hexadecimal válido
        return preg_match("/^[a-zA-Z0-9]{6}$/", $hex);
    }

    private function validarMarcaModelo($str, $max) {
        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres (contiene letras, espacios, tildes, apostrofes o guiones)
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $max. */
        return (preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ '-]+$/", $str) && strlen($str) <= $max);
    }

    function validarMatricula($str){
        return preg_match("/^[a-zA-Z0-9]{4,8}$/", $str);
    }
}