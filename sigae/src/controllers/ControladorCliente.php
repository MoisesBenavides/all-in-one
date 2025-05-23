<?php

namespace Sigae\Controllers;
use Sigae\Models\Cliente;
use Sigae\Controllers\ControladorServicio;
use Sigae\Controllers\ControladorProducto;
use Sigae\Controllers\ControladorNeumatico;
use Sigae\Controllers\ControladorOtroProducto;
use Google\Client;
use Google\Service\Oauth2;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class ControladorCliente extends AbstractController {
    private $cliente;
    private $controladorServicio;
    private $controladorProducto;
    private $controladorNeumatico;
    private $controladorOtroProducto;

    public function __construct(){
        $this->cliente=new Cliente();
    }
    function showLandingPage(): Response{
        return $this->render('landingPage.html.twig');
    }
    function login(): Response{
        return $this->render('client/account/login.html.twig');
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

            // Debug: Datos procesados
            $response['debug']['processed_data'] = [
                'email' => $email,
                'contrasena' => 'REDACTED',
            ];

            // Validar email
            if (!$this->validarEmail($email, 63)) {
                $response['errors'][] = "Por favor, ingrese un correo electrónico válido.";
            } elseif (!$this->validarContrasenaLogin($contrasena, 6, 60)) {
                $response['errors'][] = "Por favor, ingrese una contraseña válida.";
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
                    $response['errors'][] = "Correo o contraseña incorrectos.";
                }
            }
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
        }
        return $this->render('client/account/login.html.twig', [
            'response' => $response  // Aquí pasa la respuesta a la vista
        ]);
    }
    function doLoginOAuth(): Response|RedirectResponse {
        $response = ['success' => false, 'errors' => [], 'debug' => []];
    
        // Configurar cliente Google
        $client = new Client();
        $client->setAuthConfig('/var/www/private/credencialesOAuth.json');
        $client->setRedirectUri('https://simply-tough-bear.ngrok-free.app/doLoginOAuth');
        $client->addScope(['email', 'profile']);

        if (!isset($_GET['code'])) {
            return new RedirectResponse(filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
        }
    
        // Obtiene el token de acceso y maneja errores de autenticación
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            $response['errors'][] = "Error de autenticación: " . $token['error'];
            return $this->render('client/account/login.html.twig', ['response' => $response]);
        }
    
        // Guarda el token en la sesión
        $_SESSION['access_token'] = $token;
        $client->setAccessToken($token);
    
        // Obtener la información del perfil del usuario
        $oauth2 = new Oauth2($client);
        $userInfo = $oauth2->userinfo->get();
        $email = $userInfo->email;
        $fotoPerfil = $userInfo->picture;
    
        if (!Cliente::existeEmail($email)) {
            // Si el email no está registrado, intenta crear una cuenta nueva

            // Obtener datos adicionales de registro
            $nombreCompletoOAuth = $userInfo->name;
            $nombreOAuth = $userInfo->given_name;
            $apellidoOAuth = $userInfo->family_name;
    
            $nombre = $this->definirNombre($nombreOAuth, $nombreCompletoOAuth, $email);
            $apellido = $this->validarNombreApellido($apellidoOAuth, 23) ? $apellidoOAuth : null;
    
            // Intenta guardar el nuevo cliente y redirige al login si hay error
            if (!$this->cliente->guardarCliente(null, $email, null, $nombre, $apellido, null)) {
                $response['errors'][] = "Error al registrarse.";
                return $this->render('client/account/login.html.twig', [
                    'response' => $response
                ]);
            }
        }
    
        // Iniciar sesión del cliente
        $this->cliente->iniciarClienteOAuth($email);
    
        // Configuración de la sesión y parámetros de cookies
        session_set_cookie_params([
            'lifetime' => 0, 
            'path' => '/', 
            'secure' => true, 
            'httponly' => true, 
            'samesite' => 'Lax'
        ]);
        
        session_start();
        session_regenerate_id(true);
    
        // Almacena los datos del cliente en la sesión
        $_SESSION['ultima_solicitud'] = time();
        $_SESSION['id'] = $this->cliente->getId();
        $_SESSION['ci'] = $this->cliente->getCi();
        $_SESSION['email'] = $this->cliente->getEmail();
        $_SESSION['nombre'] = $this->cliente->getNombre();
        $_SESSION['apellido'] = $this->cliente->getApellido();
        $_SESSION['telefono'] = $this->cliente->getTelefono();
        $_SESSION['fotoPerfil'] = $fotoPerfil;
    
        // Si el registro es correcto, redirige al home
        $response['success'] = true;
        return $this->redirectToRoute('home');
    }

    function signup(): Response{
        return $this->render('client/account/signUp.html.twig');
    }
    function doSignup(): Response|RedirectResponse{
        $response=['success' => false, 'errors' => [], 'debug' => []];

        // Validacion de campos vacios
        if (isset($_POST["email"], $_POST["nombre"], $_POST["apellido"], $_POST["contrasena"], $_POST["repContrasena"]) && 
            !empty($_POST["email"]) && !empty($_POST["nombre"]) && !empty($_POST["apellido"]) && !empty($_POST["contrasena"]) && !empty($_POST["repContrasena"])) {

            $email = $_POST["email"];
            $nombre = $_POST["nombre"];
            $apellido = $_POST["apellido"];
            $contrasena = $_POST["contrasena"];
            $repContrasena = $_POST["repContrasena"];

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
        }
        return $this->render('client/account/signUp.html.twig', [
            'response' => $response
        ]);
    }
    function logout(): RedirectResponse{
        if (session_status() === PHP_SESSION_NONE){
            session_start();
        }
        // Limpia variables de sesión
        session_unset();
        $_SESSION=[];

        // Destruye las variables en el servidor
        session_destroy();

        /// Borra la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            // Fomatea el header de la cookie para eliminarla
            $cookieHeader = sprintf(
                '%s=; expires=%s; Max-Age=0; path=%s; domain=%s; secure; httponly; samesite=%s',
                session_name(),
                gmdate('D, d M Y H:i:s T', time() - 42000),// Formatea la fecha para que sea validada por el navegador
                $params["path"], $params["domain"],
                $params["samesite"] ?? 'Lax'// Usa 'Lax' como se especificó 'samesite'
            );
            // Envía el header con los parámetros formateados, sin reemplazar otros headers
            header('Set-Cookie: ' . $cookieHeader, false); 
        }

        return $this->redirectToRoute('showLandingPage');
    }
    function forgotPassword(): Response{
        return $this->render('client/account/forgotPassword.html.twig');
    }
    function services(): Response{
        return $this->render('client/serviciosMecanicos.html.twig');
    }
    
    function parking(): Response{
        return $this->render('client/aioParking.html.twig');
    }

    function parkingSimple(): Response{
        $misVehiculos = Cliente::cargarMisVehiculos($_SESSION['id']);
        
        return $this->render('client/reservarParkingSimple.html.twig', [
            'misVehiculos' => $misVehiculos
         ]);
    }

    function parkingLongTerm(): Response{
        $misVehiculos = Cliente::cargarMisVehiculos($_SESSION['id']);
        
        return $this->render('client/reservarParkingLargoPlazo.html.twig', [
            'misVehiculos' => $misVehiculos
        ]);
    }

    function products(): Response {
        $response=['success' => false, 'errors' => [], 'debug' => []];

        $filtro = $_SESSION['filtro'] ?? 'todos';
        $productos = [];
        
        try {
            // Selección de productos según el filtro
            switch ($filtro) {
                case 'neumaticos':
                    $this->controladorNeumatico = new ControladorNeumatico();
                    $productos = $this->controladorNeumatico->getNeumaticos('cliente');
                    break;
                case 'otros':
                    $this->controladorOtroProducto = new ControladorOtroProducto();
                    $productos = $this->controladorOtroProducto->getOtrosProductos('cliente');
                    break;
                case 'todos':
                default:
                    $this->controladorProducto = new ControladorProducto();
                    $productos = $this->controladorProducto->getProductosTodos('cliente');
                    break;
            }

            if (isset($productos) && !empty($productos)){
                shuffle($productos);
            }

        } catch (Exception $e) {
            error_log("Error al obtener productos: " . $e->getMessage());
            $response['errors'][] = $e->getMessage();
        }
    
        return $this->render('client/catalogo.html.twig', [
            'filtro' => $filtro,
            'productos' => $productos,
            'response' => $response
        ]);
    }
    
    function filterProducts(): RedirectResponse {
        $filtroSelec = $_GET['filtro'] ?? 'todos';
        $_SESSION['filtro'] = $filtroSelec;
        return $this->redirectToRoute('products');
    }

    function myAccount(): Response{
        $response=['success' => false, 'errors' => [], 'debug' => []];

        try{
            if(!$this->cliente->cargarCliente($_SESSION['id'])){
                throw new Exception("Error actualizando sesión.");
            } else {
                $misVehiculos = Cliente::cargarMisVehiculos($_SESSION['id']);

                $_SESSION['id']=$this->cliente->getId();
                $_SESSION['ci']=$this->cliente->getCi();
                $_SESSION['email']=$this->cliente->getEmail();
                $_SESSION['nombre']=$this->cliente->getNombre();
                $_SESSION['apellido']=$this->cliente->getApellido();
                $_SESSION['telefono']=$this->cliente->getTelefono();

                $cliente = [
                    'id' => $_SESSION['id'],
                    'email' => $_SESSION['email'],
                    'nombre' => $_SESSION['nombre'],
                    'apellido' => isset($_SESSION['apellido']) ? $_SESSION['apellido'] : null,
                    'telefono' => isset($_SESSION['telefono']) ? $_SESSION['telefono'] : null,
                    'fotoPerfil' => isset($_SESSION['fotoPerfil']) ? $_SESSION['fotoPerfil'] : null
                ];

            }
        } catch (Exception $e){
            $response['errors'][] = $e->getMessage();
        }
        
        return $this->render('client/miCuenta.html.twig', [
            'cliente' => $cliente,
            'misVehiculos' => $misVehiculos,
            'response' => $response
        ]);
    }

    function editMyAccount(): Response{
        $response=['success' => false, 'errors' => [], 'debug' => []];
        // Validacion de campos vacios
        if (isset($_POST["nombre"]) && !empty($_POST["nombre"])) {

            $id = $_SESSION["id"];
            $email = $_SESSION["email"];
            $nombre = $_POST["nombre"];
            $apellido = isset($_POST['apellido']) && !empty($_POST['apellido']) ? $_POST['apellido'] : null;
            $telefono = isset($_POST['telefono']) && !empty($_POST['telefono']) ? $_POST['telefono'] : null;

            error_log(print_r([$id, $email, $nombre, $apellido, $telefono], true));

            if (!$this->validarNombreApellido($nombre, 23)) {
                $response['errors'][] = "Por favor, ingrese un nombre válido.";
            } elseif ($apellido != null && !$this->validarNombreApellido($apellido, 23)) {
                $response['errors'][] = "Por favor, ingrese un apellido válido.";
            } elseif ($telefono != null && !$this->validarTelefono($telefono, 8, 20)) {
                $response['errors'][] = "Por favor, ingrese un teléfono válido. Evite usar caracteres especiales (guiones, paréntesis).";
            }elseif(!Cliente::existeEmail($email)) {
                $response['errors'][]= "No existe un usuario con el correo ingresado.";
            } elseif(!$this->cliente->modificarCliente($id, $nombre, $apellido, $telefono)){
                $response['errors'][] = "Error al hacer los cambios.";
            } else {
                $response['success'] = true;
                // Recargar la página
                return $this->redirectToRoute('myAccount');
            }
        } else {
            $response['errors'][] = "Debe llenar los campos obligatorios (*).";
        }
        $cliente = [
            'id' => $_SESSION['id'],
            'email' => $_SESSION['email'],
            'nombre' => $_SESSION['nombre'],
            'apellido' => isset($_SESSION['apellido']) ? $_SESSION['apellido'] : null,
            'telefono' => isset($_SESSION['telefono']) ? $_SESSION['telefono'] : null,
            'fotoPerfil' => isset($_SESSION['fotoPerfil']) ? $_SESSION['fotoPerfil'] : null
        ];
        $misVehiculos = Cliente::cargarMisVehiculos($_SESSION['id']);
        return $this->render('client/miCuenta.html.twig', [
            'cliente' => $cliente,
            'misVehiculos' => $misVehiculos,
            'response' => $response
        ]);
    }

    function myBookedServices(){
        $response=['success' => false, 'errors' => []];

        try{
            $misReservasParking = Cliente::cargarMisReservasParking($_SESSION['id']);
            // Debug
            error_log(print_r($misReservasParking, true));
            // Debug
            $misReservasTaller = Cliente::cargarMisReservasTaller($_SESSION['id']);
            error_log(print_r($misReservasTaller, true));

        } catch (Exception $e){
            $response['errors'][] = $e->getMessage();
        }
        
        $cliente = [
            'id' => $_SESSION['id'],
            'email' => $_SESSION['email'],
            'nombre' => $_SESSION['nombre'],
            'apellido' => isset($_SESSION['apellido']) ? $_SESSION['apellido'] : null,
            'telefono' => isset($_SESSION['telefono']) ? $_SESSION['telefono'] : null,
            'fotoPerfil' => isset($_SESSION['fotoPerfil']) ? $_SESSION['fotoPerfil'] : null
        ];

        return $this->render('client/misReservas.html.twig', [
            'cliente' => $cliente,
            'misReservasParking' => $misReservasParking,
            'misReservasTaller' => $misReservasTaller,
            'response' => $response
        ]);
    }

    
    function cancelService(){
        $response=['success' => false, 'errors' => []];

        // Validacion de campos vacios
        if (isset($_POST['idServicio']) && !empty($_POST["idServicio"])) {
            $id_servicio = $_POST['idServicio'];

            try{
                $this->controladorServicio = new ControladorServicio();
                $this->controladorServicio->cancelarServicio('cliente', $id_servicio);
                $response['success'] = true;
            }catch(Exception $e){
                $response['errors'][] = "Error cancelando la reserva: ".$e->getMessage();
            }
        } else {
            $response['errors'][] = "Debe ingresar el ID del servicio.";
        }

        // Obtener datos para recargar la página de reservas
        try{
            $misReservasParking = Cliente::cargarMisReservasParking($_SESSION['id']);
            // Debug
            error_log(print_r($misReservasParking, true));
            // Debug
            $misReservasTaller = Cliente::cargarMisReservasTaller($_SESSION['id']);
            error_log(print_r($misReservasTaller, true));

        } catch (Exception $e){
            $response['errors'][] = $e->getMessage();
        }

        $cliente = [
            'id' => $_SESSION['id'],
            'email' => $_SESSION['email'],
            'nombre' => $_SESSION['nombre'],
            'apellido' => isset($_SESSION['apellido']) ? $_SESSION['apellido'] : null,
            'telefono' => isset($_SESSION['telefono']) ? $_SESSION['telefono'] : null,
            'fotoPerfil' => isset($_SESSION['fotoPerfil']) ? $_SESSION['fotoPerfil'] : null
        ];

        return $this->render('client/misReservas.html.twig', [
            'cliente' => $cliente,
            'misReservasParking' => $misReservasParking,
            'misReservasTaller' => $misReservasTaller,
            'response' => $response
        ]);

    }

    function faq(): Response{
        return $this->render('client/FAQ.html.twig');
    }
    function home(): Response{
        return $this->render('client/homeCliente.html.twig');
    }

    public function getClientSession(): ?JsonResponse {
        session_start();
        return new JsonResponse([
            'ultima_solicitud' => $_SESSION['ultima_solicitud'],
            'id' => $_SESSION['id'],
            'ci' => $_SESSION['ci'] ?? null,
            'email' => $_SESSION['email'],
            'nombre' => $_SESSION['nombre'] ?? null,
            'apellido' => $_SESSION['apellido'] ?? null,
            'telefono' => $_SESSION['telefono'] ?? null,
            'fotoPerfil' => $_SESSION['fotoPerfil'] ?? null]);
    }

    public function getClientProfilePhoto(): ?JsonResponse {
        session_start();
        $fotoPerfil = $_SESSION['fotoPerfil'] ?? null;
        return new JsonResponse(['fotoPerfil' => $fotoPerfil]);
    }

    public function getClientVehicles(): ?JsonResponse {
        session_start();
        $id = $_SESSION['id'];
        $misVehiculos = Cliente::cargarMisVehiculos($id) ?? [];
        
        //Debug
        error_log(print_r($misVehiculos, true));

        return new JsonResponse([
            'misVehiculos' => $misVehiculos]);
    }

    public function getClientEmail(): JsonResponse {
        session_start();
        $email = $_SESSION['email'];
        return new JsonResponse(['email' => $email]);
    }
    
    private function validarContrasena($str, $min, $max) {
        /* Verifica si la contraseña contiene mayusculas, minusculas y numeros
        y si la extension de la cadena se ncuentra en el rango especificado por las variables $min y $max. */ 
        return ((preg_match('/[A-Z]/', $str) && preg_match('/[a-z]/', $str) && preg_match('/[0-9]/', $str) 
                && strlen($str) <= $max && strlen($str) >= $min));

    }

    private function validarContrasenaLogin($str, $min, $max) {
        /* Verifica si la contraseña contiene letras y numeros
        y si la extension de la cadena se encuentra en el rango especificado por las variables $min y $max. */ 
        return (preg_match('/[a-z]/', $str) && preg_match('/[0-9]/', $str) && strlen($str) <= $max && strlen($str) >= $min);
    }

    private function definirNombre($nombreOAuth, $nombreCompletoOAuth, $email) {
        // Por defecto, convierte el email a un nombre válido
        $nombre = $this->convertirAFormatoNombre($email, 23);
    
        // Asignar nombre de Google si es válido, si no, intenta asignar nombre completo
        if ($this->validarNombreApellido($nombreOAuth, 23)) {
            return $nombreOAuth;
        } elseif ($this->validarNombreApellido($nombreCompletoOAuth, 23)) {
            return $nombreCompletoOAuth;
        }
    
        return $nombre;
    }

    private function convertirAFormatoNombre($str, $max) {
    // Divide el string, cuando es email, en la parte local y el dominio
    $nombreParte = explode('@', $str)[0];

    // Remueve caracteres no válidos
    $nombreFiltrado = preg_replace("/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ]/", '', $nombreParte);

    // Recorta el nombre al máximo de caracteres a $max
    return mb_substr($nombreFiltrado, 0, $max);
}
    
    private function validarEmail($str, $max) {
        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres y contiene un dominio de correo valido
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $max. */ 
        return (preg_match("/^[a-zA-Z0-9][a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $str) && strlen($str) <= $max);
    }

    private function validarTelefono($str, $min, $max) {
        /* Verifica si el número contiene minimo de $min sólo dígitos y máximo $max caracteres
        puede contener el símbolo de más (+) al principio, y espacios luego del primer número, debe terminar en número */ 
        if (preg_match("/^\+?\d(?:[0-9 ]*\d)?$/", $str) && strlen($str) <= $max) {
            // Verifica que haya al menos $min dígitos
            $digitos = preg_replace('/\D/', '', $str);
            return strlen($digitos) >= $min;
        }
        return false;
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