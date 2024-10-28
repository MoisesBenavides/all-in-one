<?php

namespace Sigae\Controllers;
use Sigae\Models\Funcionario;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use PDOException;
use Exception;

class ControladorFuncionario extends AbstractController {
    private $funcionario;

    function loginAioEmployee(): Response{
        return $this->render('employee/loginEmpleado.html.twig');
    }

    function showDashboard(): Response{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                return $this->render('employee/manager/homeGerente.html.twig');
            case 'ejecutivo':
                return $this->render('employee/serviceExecutive/ejecutivoServiciosHome.html.twig');
            case 'cajero':
                return $this->render('employee/cashier/cajeroHome.html.twig');
            case 'jefe_diagnostico':
                return $this->render('employee/diagnoseChief/jefeDiagnosticoHome.html.twig');
            case 'jefe_taller':
                return $this->render('employee/workshopChief/jefeTallerHome.html.twig');
        }
    }

    function doLoginAioEmployee(): Response|RedirectResponse{
        $response=['success' => false, 'errors' => [], 'debug' => []];

        // Validacion de campos vacios
        if (isset($_POST["usuario"], $_POST["contrasena"]) && 
            !empty($_POST["usuario"]) && !empty($_POST["contrasena"])) {

            $usuario = $_POST["usuario"];
            $contrasena = $_POST["contrasena"];

            //Debug
            error_log("Datos recibidos: ".$usuario." ".$contrasena);

            // Validar credenciales
            if (!$this->validarUsuario($usuario, 63)) {
                error_log("Error con el usuario: ".$usuario." ".$contrasena);
                $response['errors'][] = "Por favor, ingrese un usuario válido.";
            } elseif (!$this->validarContrasena($contrasena, 6, 60)) {
                error_log("Error con la constasena de: ".$usuario." ".$contrasena);
                $response['errors'][] = "Por favor, ingrese una contraseña válida.";
            } else {
                $this->funcionario=new Funcionario($usuario, null);
                try {
                    if (!$this->funcionario->verificarCredenciales($contrasena)) {
                        error_log("Error con las credenciales: ".$usuario." ".$contrasena);
                        throw new PDOException("Credenciales incorrectas.");
                    }
                    error_log("Iniciando sesión...");
                    
                    // Intenta iniciar el funcionario y obtener el rol
                    $this->funcionario->iniciarFuncionario($usuario);
                
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
                
                } catch (PDOException $e) {
                    // Añade el mensaje de error al array de errores
                    error_log($e->getMessage(), true);
                    $response['errors'][] = $e->getMessage();
                }
                
            }
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
        }
        return $this->render('employee/loginEmpleado.html.twig', [
            'response' => $response  // Aquí pasa la respuesta a la vista
        ]);
    }

    private function validarUsuario($str, $max) {
        /* Verifica si el nombre de usuario $str cumple con ciertos criterios como:
            - El primer carácter debe ser una letra o un número.
            - Permite guiones bajos o guiones opcionales entre letras o números, sin comenzar ni terminar con ellos.
            - Máximo por el especificado
        */ 
        return (preg_match("/^[a-zA-Z0-9]+(?:[-_]?[a-zA-Z0-9]+)*$/", $str) && strlen($str) <= $max);
    }

    private function validarContrasena($str, $min, $max) {
        /* Verifica si la contraseña:
            - ...
            - Su longitud está en el rango especificado por $min y $max.
        */
        return strlen($str) >= $min && strlen($str) <= $max;
    }
}