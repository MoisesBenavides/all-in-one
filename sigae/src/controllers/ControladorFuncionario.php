<?php

namespace Sigae\Controllers;
use Sigae\Models\Funcionario;
use Sigae\Controllers\ControladorNeumatico;
use Sigae\Controllers\ControladorOtroProducto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use PDOException;
use Exception;

class ControladorFuncionario extends AbstractController {
    private $funcionario;
    private $controladorNeumatico;
    private $controladorOtroProducto;

    function loginAioEmployee(): Response{
        return $this->render('employee/loginEmpleado.html.twig');
    }

    function doLoginAioEmployee(): Response|RedirectResponse{
        $response=['success' => false, 'errors' => [], 'debug' => []];

        // Validacion de campos vacios
        if (isset($_POST["usuario"], $_POST["contrasena"]) && 
            !empty($_POST["usuario"]) && !empty($_POST["contrasena"])) {

            $usuarioHost = $_POST["usuario"];

            // Verifica si el input de usuario contiene un arroba
            if (substr_count($usuarioHost, "@") !== 1){
                // Extrae usuario y host de input $usuarioHost
                $partes= explode("@", $usuarioHost);
                $usuario=$partes[0];
                $host=$partes[1];
                $contrasena = $_POST["contrasena"];

                // Validar credenciales
                if (!$this->validarUsuario($usuario, 50)) {
                    $response['errors'][] = "Por favor, ingrese un usuario válido.";
                } elseif (!$this->validarHost($host, 50)) {
                    $response['errors'][] = "Por favor, ingrese un nombre de host válido.";
                } elseif (!$this->validarContrasena($contrasena, 6, 60)){
                    $response['errors'][] = "Por favor, ingrese una contraseña válida.";
                } else {
                    $this->funcionario=new Funcionario($usuario, $host, null);
                    try {
                        if (!$this->funcionario->verificarCredenciales($contrasena)) {
                            throw new Exception("Usuario o contraseña incorrectos.");
                        } elseif(!$this->funcionario->iniciarFuncionario($usuario)) {
                            throw new Exception("No se pudo iniciar el usuario.");
                        } else{
                            // Configuración y manejo de la sesión segura
                            session_set_cookie_params([
                                'lifetime' => 0,
                                'path' => '/',
                                'secure' => true,
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]);
                            session_start();
                            session_regenerate_id(true);

                            // Guarda los datos de la sesión
                            $_SESSION['ultima_solicitud'] = time();
                            $_SESSION['usuario'] = $this->funcionario->getUsuario();
                            $_SESSION['rol'] = $this->funcionario->getRol();

                            // Redirigir al dashboard
                            return $this->redirectToRoute('showDashboard');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    }
                }
            } else {
                $response['errors'][] = "El usuario debe contener un arroba (@).";
            }
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
        }
        return $this->render('employee/loginEmpleado.html.twig', [
            'response' => $response  // Aquí pasa la respuesta a la vista
        ]);
    }

    function showDashboard(): Response{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                return $this->render('employee/manager/dashboardGerente.html.twig');
            case 'ejecutivo':
                return $this->render('employee/serviceExecutive/dashboardEjecutivoServicios.html.twig');
            case 'cajero':
                return $this->render('employee/cashier/dashboardCajero.html.twig');
            case 'jefe_diagnostico':
                return $this->render('employee/diagnoseChief/dashboardJefeDiagnostico.html.twig');
            case 'jefe_taller':
                return $this->render('employee/workshopChief/dashboardJefeTaller.html.twig');
        }
    }

    function showReports(): Response{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                return $this->render('employee/manager/reportes.html.twig');
            case 'jefe_diagnostico':
                return $this->render('employee/diagnoseChief/reportesJefeDiagnostico.html.twig');
            case 'jefe_taller':
                return $this->render('employee/workshopChief/reportesJefeTaller.html.twig');
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function stockManagement(): Response{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                return $this->render('employee/manager/transaccionStock.html.twig');
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function prepareOrder(): Response{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'cajero':
                return $this->render('employee/cashier/preparacionOrden.html.twig');
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function inventory(): Response{
        $response=['success' => false, 'errors' => [], 'debug' => []];

        $rol=$_SESSION['rol'];

        $productos = [];
        
        switch($rol){
            case 'gerente':
                try{
                    $this->controladorNeumatico = new ControladorNeumatico();
                    $this->controladorOtroProducto = new ControladorOtroProducto();
                    // Obtiene neumaticos
                    $neumaticos = $this->controladorNeumatico->getNeumaticos('gerente');
                    // Obtiene otros
                    $otrosProductos = $this->controladorOtroProducto->getOtrosProductos('gerente');
                    // Combina productos de ambas categorías
                    $productos = array_merge($otrosProductos, $neumaticos);
                } catch(Exception $e){
                    $response['errors'][] = $e->getMessage();
                }
                return $this->render('employee/manager/inventario.html.twig', [
                    'productos' => $productos,
                    'response' => $response
                ]);
            case 'cajero':
                try{
                    $this->controladorNeumatico = new ControladorNeumatico();
                    $this->controladorOtroProducto = new ControladorOtroProducto();
                    // Obtiene neumaticos
                    $neumaticos = $this->controladorNeumatico->getNeumaticos('cajero');
                    // Obtiene otros
                    $otrosProductos = $this->controladorOtroProducto->getOtrosProductos('cajero');
                    // Combina productos de ambas categorías
                    $productos = array_merge($otrosProductos, $neumaticos);
                } catch(Exception $e){
                    $response['errors'][] = $e->getMessage();
                }
                return $this->render('employee/cashier/inventarioCajero.html.twig', [
                    'productos' => $productos,
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function employeeManagement(): Response|RedirectResponse{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                return $this->render('employee/manager/rolesFuncionarios.html.twig');
            case 'jefe_taller':
                return $this->redirectToRoute('showServiceExecutives');
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showDiagnoseChiefs(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];

        $jefesDiagnostico = [];

        switch($rol){
            case 'gerente':
                try{
                    $jefesDiagnostico = Funcionario::getFuncionariosPorRol($rol, 'jefe_diagnostico');
                    
                    // Debug: Resultado funcionarios arreglo
                    error_log(print_r($jefesDiagnostico, true));
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaFuncionarios.html.twig', [
                    'funcionarios' => $jefesDiagnostico,
                    'rolBuscado' => "Jefes de Diagnóstico",
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showWorkshopChiefs(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];

        $jefesTaller = [];

        switch($rol){
            case 'gerente':
                try{
                    $jefesTaller = Funcionario::getFuncionariosPorRol($rol, 'jefe_taller');
                    
                    // Debug: Resultado funcionarios arreglo
                    error_log(print_r($jefesTaller, true));
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaFuncionarios.html.twig', [
                    'funcionarios' => $jefesTaller,
                    'rolBuscado' => "Jefes de Taller de Alineación y Balanceo",
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showCashiers(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];

        $cajeros = [];

        switch($rol){
            case 'gerente':
                try{
                    $cajeros = Funcionario::getFuncionariosPorRol($rol, 'cajero');
                    
                    // Debug: Resultado funcionarios arreglo
                    error_log(print_r($cajeros, true));
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaFuncionarios.html.twig', [
                    'funcionarios' => $cajeros,
                    'rolBuscado' => "Cajeros",
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }
    function showValets(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];

        $valets = [];
        
        switch($rol){
            case 'gerente':
                try{
                    $valets = Funcionario::getFuncionariosPorRol($rol, 'valet_parking');
                    
                    // Debug: Resultado funcionarios arreglo
                    error_log(print_r($valets, true));
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaFuncionarios.html.twig', [
                    'funcionarios' => $valets,
                    'rolBuscado' => "Valets Parking",
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showServiceExecutives(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];

        $ejecutivos = [];

        switch($rol){
            case 'gerente':
                try{
                    $ejecutivos = Funcionario::getFuncionariosPorRol($rol, 'ejecutivo');
                    
                    // Debug: Resultado funcionarios arreglo
                    error_log(print_r($ejecutivos, true));
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaFuncionarios.html.twig', [
                    'funcionarios' => $ejecutivos,
                    'rolBuscado' => "Ejecutivos de Servicio",
                    'response' => $response
                ]);
            case 'jefe_taller':
                try{
                    $ejecutivos = Funcionario::getFuncionariosPorRol($rol, 'ejecutivo');

                    // Debug: Resultado funcionarios arreglo
                    error_log(print_r($ejecutivos, true));
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }
                return $this->render('employee/workshopChief/listaEjecutivos.html.twig', [
                    'ejecutivos' => $ejecutivos,
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showServiceAgenda(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        
        switch($rol){
            case 'gerente':
                return $this->render('employee/manager/listaFuncionarios.html.twig');
            case 'jefe_taller':
                return $this->render('employee/workshopChief/listaEjecutivos.html.twig');
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showServiceDiagnosisRecords(): Response{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                return $this->render('employee/manager/listaFuncionarios.html.twig');
            case 'jefe_taller':
                return $this->render('employee/workshopChief/listaEjecutivos.html.twig');
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    private function validarUsuario($str, $max) {
        /* Verifica si el nombre de usuario $str cumple con ciertos criterios como:
            - El primer carácter debe ser una letra o un número.
            - Permite guiones bajos o guiones opcionales entre letras o números, sin comenzar ni terminar con ellos.
            - Máximo por el especificado
        */ 
        return (preg_match("/^[a-zA-Z0-9]+(?:[-_]?[a-zA-Z0-9]+)*$/", $str) && strlen($str) <= $max);
    }

    private function validarHost($host, $max) {
        /* Verifica si el $host cumple con ciertos criterios como:
            - Contiene un dominio válido o es igual a "localhost"
            - Un máximo específicado de $max caracteres
        */
        return (preg_match("/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $host) || $host == "localhost" && strlen($host) <= $max);
    }

    private function validarContrasena($str, $min, $max) {
        /* Verifica si la contraseña:
            - Tiene una longitud en el rango especificado por $min y $max.
        */
        return strlen($str) >= $min && strlen($str) <= $max;
    }
}