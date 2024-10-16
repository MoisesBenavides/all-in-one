<?php

namespace Sigae\Controllers;
use Sigae\Models\Cliente;
use Google_Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ControladorCliente extends AbstractController {
    private $cliente;
    private const INACTIVIDAD_MAX_SESION = 60;

    public function __construct(){
        $this->cliente=new Cliente();
    }
    function showLandingPage(): Response{
        return $this->render('landingPage.html.twig');
    }
    function login(): Response{
        return $this->render('account/login.html.twig');
    }
    function loginAioEmployee(): Response{
        return $this->render('employee/loginEmpleado.html.twig');
    }
    function doLogin(): Response|RedirectResponse{
        $response=['success' => false, 'errors' => [], 'debug' => []];

        // Debug: Log all received data
        $response['debug']['received_data']=$_POST;

        // Validacion de campos vacios
        if (isset($_POST["email"], $_POST["contrasena"]) && 
            !empty($_POST["email"]) && !empty($_POST["contrasena"])) {

            $email = $_POST["email"];
            $contrasena = $_POST["contrasena"];

            // Debug: Log processed data
            $response['debug']['processed_data'] = [
                'email' => $email,
                'contrasena' => 'REDACTED',
            ];

            // Validar email
            if (!$this->validarEmail($email, 63)) {
                $response['errors'][] = "Por favor, ingrese un correo electrónico válido.";
            } elseif (!$this->validarContrasena($contrasena, 6, 60)) {
                $response['errors'][] = "La contraseña debe tener entre 6 y 60 caracteres.";
            } else {
                if ($this->cliente->iniciarCliente($email, $contrasena)) {
                    // Debug: Login exitoso
                    $response['success'] = true;
                    
                    // Parámetros de cookie de sesión
                    session_set_cookie_params([
                        'lifetime' => 0,          // La cookie se elimina al salir del navegador
                        'path' => '/',            // Accesible en toda la aplicación
                        'secure' => true,         // Solo se envía por HTTPS
                        'httponly' => true,       // Protege que no sea accesible desde JavaScript
                        'samesite' => 'Lax'       // Se envía en la mayoría de las solicitudes siempre que sean "seguras", evitando ataques CSRF
                    ]);

                    // Iniciar sesión del cliente
                    session_start();

                    // Regenerar el ID de sesión para prevenir fijación de sesión
                    session_regenerate_id(true);

                    $_SESSION['ultima_solicitud']= time();
                    $_SESSION['id']=$this->cliente->getId();
                    $_SESSION['ci']=$this->cliente->getCi();
                    $_SESSION['email']=$this->cliente->getEmail();
                    $_SESSION['nombre']=$this->cliente->getNombre();
                    $_SESSION['apellido']=$this->cliente->getApellido();
                    $_SESSION['telefono']=$this->cliente->getTelefono();

                    // Redirigir a la home page
                    return $this->redirectToRoute('home');
                } else {
                    $response['errors'][] = "Error al iniciar sesión.";
                }
            }
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
            // Debug: Log which fields are missing
            $response['debug']['missing_fields'] = array_diff(
                ['email', 'contrasena'],
                array_keys($_POST)
            );
        }
        return $this->render('account/login.html.twig', [
            'response' => $response  // Aquí pasa la respuesta a la vista
        ]);
    }
    function doLoginAioEmployee(){
    }
    function doLoginOAuth(): Response|RedirectResponse{
        // Configurar cliente Google
        $client= new Google_Client();
        $client->setAuthConfig('/var/www/html/private/credentials.json');
        $client->setRedirectUri('https://ngrok-url.com/doLoginOAuth');
        $client->addScope('email');
        $client->addScope('profile');
        $client->addScope('nombre');

        if (!isset($_GET['code'])) {
            $auth_url = $client->createAuthUrl();
            header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
            exit();
        } else {
            // Procesa la respuesta de Google
            $client->authenticate($_GET['code']);
            $_SESSION['access_token'] = $client->getAccessToken();

            // Obtén la información del perfil del usuario
            $oauth2 = new Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();

            $perfil = $userInfo->profile;
            $email = $userInfo->email;

            if ($this->cliente->iniciarCliente($email, null)) {
                $response['success'] = true;
                
                // Iniciar sesión del cliente
                session_start();
                $_SESSION['ultima_solicitud']= time();
                $_SESSION['id']=$this->cliente->getId();
                $_SESSION['ci']=$this->cliente->getCi();
                $_SESSION['email']=$this->cliente->getEmail();
                $_SESSION['nombre']=$this->cliente->getNombre();
                $_SESSION['apellido']=$this->cliente->getApellido();
                $_SESSION['telefono']=$this->cliente->getTelefono();
                $_SESSION['perfil']=$perfil; //TODO: ¿Cookie o sesion?

                // Redirigir a la home page
                return $this->redirectToRoute('home');
            } else {
                $response['errors'][] = "Error al iniciar sesión.";
            }
        }
        return new JsonResponse($response); // Devuelve un JSON en caso de error
    }
    function doSignUpOAuth(){
    }
    function signup(): Response{
        return $this->render('account/signUp.html.twig');
    }
    function doSignup(): Response|RedirectResponse{
        $response=['success' => false, 'errors' => [], 'debug' => []];

        // Debug: Log all received data
        $response['debug']['received_data']=$_POST;

        // Validacion de campos vacios
        if (isset($_POST["email"], $_POST["nombre"], $_POST["apellido"], $_POST["contrasena"], $_POST["repContrasena"]) && 
            !empty($_POST["email"]) && !empty($_POST["nombre"]) && !empty($_POST["apellido"]) && !empty($_POST["contrasena"]) && !empty($_POST["repContrasena"])) {

            $email = $_POST["email"];
            $nombre = $_POST["nombre"];
            $apellido = $_POST["apellido"];
            $contrasena = $_POST["contrasena"];
            $repContrasena = $_POST["repContrasena"];

            // Debug: Log processed data
            $response['debug']['processed_data'] = [
                'email' => $email,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'contrasena' => 'REDACTED',
                'repContrasena' => 'REDACTED'
            ];

            if (!$this->validarEmail($email, 63)) {
                $response['errors'][] = "Por favor, ingrese un correo electrónico válido.";

            } elseif (!$this->validarNombreApellido($nombre, 23) || !$this->validarNombreApellido($apellido, 23)) {
                $response['errors'][] = "Por favor, ingrese un nombre o apellido válido.";

            } elseif (!$this->validarContrasena($contrasena, 6, 60)) {
                $response['errors'][] = "Use un mínimo de 6 caracteres con mayúsculas, minúsculas y números.";

            } elseif ($contrasena !== $repContrasena) {
                $response['errors'][] = "Las contraseñas no coinciden.";

            } elseif(Cliente::existeEmail($email)) {
                error_log('Email ya existe: ' . $email);
                $response['errors'][]= "Ya existe un usuario con el correo ingresado.";

            } elseif(!$this->cliente->guardarCliente(null, $email, $contrasena, $nombre, $apellido, null)){
                $response['errors'][] = "Error al registrarse.";

            } else {
                $response['success'] = true;
                // Redirigir al login
                return $this->redirectToRoute('login');
            }
            
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
            // Debug: Log which fields are missing
            $response['debug']['missing_fields'] = array_diff(
                ['email', 'nombre', 'apellido', 'contrasena', 'repContrasena'],
                array_keys($_POST)
            );
        }
        return $this->render('account/signUp.html.twig', [
            'response' => $response
        ]);
    }
    function logout(): RedirectResponse{
        session_start();
        session_unset();
        $_SESSION=[];

        session_destroy();

        // Borrar cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, 
            $params["lifetime"], $params["path"], 
            $params["secure"], $params["httponly"], $params["samesite"]);
        }

        return $this->redirectToRoute('showLandingPage');
    }
    function forgotPassword(): Response{
        return $this->render('account/forgotPassword.html.twig');
    }
    function services(): Response{
        $redireccion = $this->verificarSesion();
        if ($redireccion) {
            return $redireccion;
        }
        return $this->render('client/serviciosMecanicos.html.twig');
    }
    function bookService(): Response{
        $redireccion = $this->verificarSesion();
        if ($redireccion) {
            return $redireccion;
        }

        $misVehiculos = $this->cliente->cargarMisVehiculos($_SESSION['id']);

        return $this->render('client/reservarServicio.html.twig', [
           'misVehiculos' => $misVehiculos
        ]);
    }
    
    function parking(): Response{
        $redireccion = $this->verificarSesion();
        if ($redireccion) {
            return $redireccion;
        }
        return $this->render('client/aioParking.html.twig');
    }

    function parkingSimple(): Response{
        $redireccion = $this->verificarSesion();
        if ($redireccion) {
            return $redireccion;
        }

        $misVehiculos = $this->cliente->cargarMisVehiculos($_SESSION['id']);
        
        return $this->render('client/reservarParkingSimple.html.twig', [
            'misVehiculos' => $misVehiculos
         ]);
    }

    function parkingLongTerm(): Response{
        $redireccion = $this->verificarSesion();
        if ($redireccion) {
            return $redireccion;
        }

        $misVehiculos = $this->cliente->cargarMisVehiculos($_SESSION['id']);
        
        return $this->render('client/reservarParkingLargoPlazo.html.twig', [
            'misVehiculos' => $misVehiculos
        ]);
    }

    function products(): Response{
        $redireccion = $this->verificarSesion();
        if ($redireccion) {
            return $redireccion;
        }
        return $this->render('client/catalogo.html.twig');
    }

    function myAccount(): Response{
        $redireccion = $this->verificarSesion();
        if ($redireccion) {
            return $redireccion;
        }
        $misVehiculos = $this->cliente->cargarMisVehiculos($_SESSION['id']);
        return $this->render('client/miCuenta.html.twig');
    }
    function faq(): Response{
        $redireccion = $this->verificarSesion();
        if ($redireccion) {
            return $redireccion;
        }
        return $this->render('client/FAQ.html.twig');
    }
    function home(): Response{
        $redireccion = $this->verificarSesion();
        if ($redireccion) {
            return $redireccion;
        }
        return $this->render('client/homeCliente.html.twig');
    }

    function verificarSesion(): RedirectResponse{
        session_start();
        if (isset($_SESSION["ultima_solicitud"])){
            // Obtiene el tiempo desde la última solicitud
            $inactividad = time() - $_SESSION["ultima_solicitud"];

            // Verificación de inactividad de la sesión
            if ($inactividad <= $this::INACTIVIDAD_MAX_SESION) {
                // Genera un nuevo ID de sesión y actualiza la ultima solicitud
                session_regenerate_id(true);
                $_SESSION["ultima_solicitud"] = time();
                return null;
            } else {
                // Cierra la sesión
                $this->logout();
                return null;
            } 
        } else {
            // Redirige al inicio si no tiene una sesión activa
            return $this->redirectToRoute('showLandingPage');
        }
    }
    
    private function validarContrasena($str, $min, $max) {
        /* Verifica si la contraseña contiene mayusculas, minusculas y numeros
        y si la extension de la cadena se ncuentra en el rango especificado por las variables $min y $max. */ 
        return ((preg_match('/[A-Z]/', $str) && preg_match('/[a-z]/', $str) && preg_match('/[0-9]/', $str) 
                && strlen($str) <= $max && strlen($str) >= $min));

    }
    
    private function validarEmail($str, $max) {
        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres y contiene un dominio de correo valido
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $max. */ 
        return (preg_match("/^[a-zA-Z0-9][a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $str) && strlen($str) <= $max);
    }

    private function validarNombreApellido($str, $max) {
        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres (contiene letras, espacios, tildes, apostrofes o guiones)
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $max. */
        return (preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ '-]+$/", $str) && strlen($str) <= $max);
    }

    private function validarCi($ci){
        $digitosDeCedula = str_split($ci);  // Convierte la cédula en un array de caracteres

        $numerosParaMultiplicar = array(2, 9, 8, 7, 6, 3, 4);

        $acum = 0;
        for ($i = 0; $i < count($digitosDeCedula) - 1; $i++) {
            $acum += $digitosDeCedula[$i] * $numerosParaMultiplicar[$i];
        }
        $aux = $acum % 10;
        $verif = 10 - $aux;

        return $verif == $digitosDeCedula[count($digitosDeCedula) - 1];
    }

}
