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

            // Validar credenciales
            if (!$this->validarUsuario($usuario, 63)) {
                $response['errors'][] = "Por favor, ingrese un usuario válido.";
            } elseif (!$this->validarContrasena($contrasena, 6, 60)) {
                $response['errors'][] = "Por favor, ingrese una contraseña válida.";
            } else {
                try {
                    if (!$this->funcionario->verificarCredenciales($usuario, $contrasena)) {
                        throw new PDOException("Credenciales incorrectas.");
                    }
                    
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
        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres y contiene un dominio de correo valido
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $max. */ 
        return (preg_match("/^[a-zA-Z0-9][a-zA-Z0-9_%$@]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $str) && strlen($str) <= $max);
    }

    private function validarContrasena($str, $min, $max) {
        /* Verifica si la contraseña contiene mayusculas, minusculas y numeros
        y si la extension de la cadena se ncuentra en el rango especificado por las variables $min y $max. */ 
        return ((preg_match('/[A-Z]/', $str) && preg_match('/[a-z]/', $str) && preg_match('/[0-9]/', $str) 
                && strlen($str) <= $max && strlen($str) >= $min));

    }
}