<?php

namespace Sigae\Controllers;
use Sigae\Models\Vehiculo;
use Sigae\Models\TipoVehiculo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class ControladorVehiculo extends AbstractController{
    private $vehiculo;

    public function addVehicle(): Response|RedirectResponse{
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

            $matricula = strtoupper($_POST["matricula"]);
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
                // Instancia vehiculo con datos ingresados
                $this->vehiculo = new Vehiculo($matricula, $marca, $modelo, $tipo, $color);

                // Inicializar una conexión PDO como cliente
                $this->vehiculo->setDBConnection("def_cliente", "password_cliente", "localhost");
                $this->vehiculo->comenzarTransaccion();

                try{
                    if (!$this->vehiculo->create()) {
                        throw new Exception("Error al registrar vehículo.");
                    } elseif (!$this->vehiculo->vincularCliente($id_cliente)){
                        throw new Exception("Error al vincular vehículo.");
                    }

                    // Debug: Registro y vinculación exitosa
                    $response['success'] = true;

                    // Recargar página
                    return $this->redirectToRoute('myAccount');

                } catch(Exception $e){
                    $this->vehiculo->deshacerTransaccion();
                    $response['errors'][] = "Error procesando el vehículo: ". $e->getMessage();
                } finally{
                    $this->vehiculo->cerrarDBConnection();
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

    public function deleteVehicle($matricula): Response|RedirectResponse{
        $response = ['success' => false, 'errors' => [], 'debug' => []];

        $cliente = [
            'id' => $_SESSION['id'],
            'email' => $_SESSION['email'],
            'nombre' => $_SESSION['nombre'],
            'apellido' => isset($_SESSION['apellido']) ? $_SESSION['apellido'] : null,
            'telefono' => isset($_SESSION['telefono']) ? $_SESSION['telefono'] : null,
            'fotoPerfil' => isset($_SESSION['fotoPerfil']) ? $_SESSION['fotoPerfil'] : null
        ];

        $misVehiculos = Vehiculo::cargarMisVehiculos($_SESSION['id']);
    
        if (isset($_POST["matricula"]) && !empty($_POST["matricula"])) {

            $matricula = strtoupper($_POST["matricula"]);

            // Validar email
            if (!$this->validarMatricula($matricula)) {
                $response['errors'][] = "Por favor, ingrese una matrícula válida.";
            } else {
                // Crear una instancia del vehículo con la matrícula
                $this->vehiculo = new Vehiculo($matricula, null, null, null, null);

                // Inicializar una conexión PDO como cliente
                $this->vehiculo->setDBConnection("def_cliente", "password_cliente", "localhost");
                $this->vehiculo->comenzarTransaccion();

                try{
                    if (!$this->vehiculo->existeMatricula($matricula)) {
                        throw new Exception("La matrícula ingresada no existe.");
                    }
                    if (!$this->vehiculo->delete()) {
                        throw new Exception("Ocurrió un error al desvincular el vehículo");
                    }
    
                    // Debug: Registro y desvinculación exitosa
                    $response['success'] = true;
    
                    // Recargar página
                    return $this->redirectToRoute('myAccount');
    
                } catch (Exception $e) {
                    $this->vehiculo->deshacerTransaccion();
                    $response['errors'][] = "Error procesando el vehículo: " . $e->getMessage();
                } finally{
                    // Desconectar de la base de datos
                    $this->vehiculo->cerrarDBConnection();
                }                
            }
        } else {
            $response['errors'][] = "Debe ingresar una matrícula.";
        }
    
        // Si hubo errores, renderizar la vista con los mensajes de error
        return $this->render('client/myAccount.html.twig', [
            'cliente' => $cliente, // Pasa variables de sesión de cliente
            'misVehiculos' => $misVehiculos, // Pasa vehículos actualizados del cliente
            'response' => $response  // Aquí pasa la respuesta a la vista
        ]);
    }

    public function registrarYaVehiculo($matricula, $tipoVehiculo, $id_cliente){
        $this->vehiculo = new Vehiculo($matricula, null, null, $tipoVehiculo, null);
        $this->vehiculo->setDBConnection("def_cliente", "password_cliente", "localhost");
        $this->vehiculo->comenzarTransaccion();
        try{
            if ($this->vehiculo->existeMatricula($matricula)) {
                throw new Exception("La matrícula ingresada ya existe.");
            }elseif (!$this->vehiculo->registrarYa()) {
                throw new Exception("Error al registrar vehículo aaaaaa.");
            } elseif (!$this->vehiculo->vincularCliente($id_cliente)){
                throw new Exception("Error al vincular vehículo.");
            }

            $this->vehiculo->confirmarTransaccion();
            return true; // True si la matrícula no existe y se registra correctamente
        } catch (Exception $e){
            $this->vehiculo->deshacerTransaccion();
            throw $e;
        } finally{
            // Desconectar de la base de datos
            $this->vehiculo->cerrarDBConnection();   
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