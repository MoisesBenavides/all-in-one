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
            if (substr_count($usuarioHost, "@") == 1){
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
            default:
                return $this->render('errors/errorAcceso.html.twig');
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
                // Obtener arreglo con usuarios con rol jefe_diagnostico
                try{
                    $jefesDiagnostico = Funcionario::getFuncionariosPorRol($rol, 'jefe_diagnostico');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaJefesDiagnostico.html.twig', [
                    'funcionarios' => $jefesDiagnostico,
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
                // Obtener arreglo con usuarios con rol jefe_taller
                try{
                    $jefesTaller = Funcionario::getFuncionariosPorRol($rol, 'jefe_taller');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaJefesTaller.html.twig', [
                    'funcionarios' => $jefesTaller,
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
                // Obtener arreglo con usuarios con rol cajero
                try{
                    $cajeros = Funcionario::getFuncionariosPorRol($rol, 'cajero');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaCajeros.html.twig', [
                    'funcionarios' => $cajeros,
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
                // Obtener arreglo con usuarios con rol valet_parking
                try{
                    $valets = Funcionario::getFuncionariosPorRol($rol, 'valet_parking');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaValets.html.twig', [
                    'funcionarios' => $valets,
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
                // Obtener arreglo con usuarios con rol ejecutivo
                try{
                    $ejecutivos = Funcionario::getFuncionariosPorRol($rol, 'ejecutivo');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaEjecutivos.html.twig', [
                    'funcionarios' => $ejecutivos,
                    'response' => $response
                ]);
            case 'jefe_taller':
                // Obtener arreglo con usuarios con rol ejecutivo
                try{
                    $ejecutivos = Funcionario::getFuncionariosPorRol($rol, 'ejecutivo');
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

    function addDiagnoseChief(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        error_log($rol);
        $jefesDiagnostico = [];

        $rolUsuarioNuevo = 'jefe_diagnostico';

        switch($rol){
            case 'gerente':
                error_log("Case ".$rol);
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);

                if ($validacion['exito'] === true) {
                    error_log("If ".$rol);

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    error_log("Datos procesados luego de validacion".$usuario.$host.$contrasena);

                    error_log("Esta es la validacion".print_r($validacion, true));

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (Funcionario::existe($rol, $usuario, $host)) {
                            error_log("Usuario ya existe");
                            throw new Exception("Este usuario ya existe.");
                        } elseif (!$this->funcionario->altaJefeDiagnostico($contrasena)) {
                            error_log("Error al registrar funcionario");
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showDiagnoseChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log("Este es un error del catch: ".$e->getMessage());
                        $response['errors'][] = "Envio de error catch al hacer conexion a bd".$e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    error_log($validacion['msj_error']);
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol jefe_diagnostico
                try{
                    $jefesDiagnostico = Funcionario::getFuncionariosPorRol($rol, 'jefe_diagnostico');
                } catch(Exception $e){
                    error_log("Error al cargar funcionarios".$e->getMessage());
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }
                error_log("Error de response: ".print_r($response), true);

                return $this->render('employee/manager/listaJefesDiagnostico.html.twig', [
                    'funcionarios' => $jefesDiagnostico,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function addWorkshopChief(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $jefesTaller = [];

        $rolUsuarioNuevo = 'jefe_taller';

        switch($rol){
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("Este usuario ya existe.");
                        }elseif (!$this->funcionario->altaJefeTaller($contrasena)) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showWorkshopChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol jefe_taller
                try{
                    $jefesTaller = Funcionario::getFuncionariosPorRol($rol, 'jefe_taller');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaJefesTaller.html.twig', [
                    'funcionarios' => $jefesTaller,
                    'response' => $response
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function addCashier(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $cajeros = [];

        $rolUsuarioNuevo = 'cajero';

        switch($rol){
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("Este usuario ya existe.");
                        }elseif (!$this->funcionario->altaCajero($contrasena)) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showCashiers');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol cajero
                try{
                    $cajeros = Funcionario::getFuncionariosPorRol($rol, 'cajero');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaCajeros.html.twig', [
                    'funcionarios' => $cajeros,
                    'response' => $response
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function addValet(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $valets = [];

        $rolUsuarioNuevo = 'valet_parking';

        switch($rol){
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("Este usuario ya existe.");
                        }elseif (!$this->funcionario->altaValetParking($contrasena)) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showValets');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol valet_parking
                try{
                    $valets = Funcionario::getFuncionariosPorRol($rol, 'valet_parking');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaValets.html.twig', [
                    'funcionarios' => $valets,
                    'response' => $response
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function addServiceExecutive(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $ejecutivos = [];

        $rolUsuarioNuevo = 'ejecutivo';

        switch($rol){
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);

                if($validacion['exito'] == true){

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("Este usuario ya existe.");
                        }elseif(!$this->funcionario->altaEjecutivo($contrasena)){
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showServiceExecutives');
                        }
                            
                    } catch (Exception $e){
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else {
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol ejecutivo
                try{
                    $ejecutivos = Funcionario::getFuncionariosPorRol($rol, 'ejecutivo');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaEjecutivos.html.twig', [
                    'funcionarios' => $ejecutivos,
                    'response' => $response
                ]);

            case 'jefe_taller':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('jefe_taller');
                    try {
                        if (!$this->funcionario->altaEjecutivo($contrasena)) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showServiceExecutives');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol ejecutivo
                try{
                    $ejecutivos = Funcionario::getFuncionariosPorRol($rol, 'ejecutivo');
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

    function deleteDiagnoseChief(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $jefesDiagnostico = [];

        switch($rol){
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        }elseif (!$this->funcionario->bajaJefeDiagnostico()) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showDiagnoseChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol jefe_diagnostico
                try{
                    $jefesDiagnostico = Funcionario::getFuncionariosPorRol($rol, 'jefe_diagnostico');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaJefesDiagnostico.html.twig', [
                    'funcionarios' => $jefesDiagnostico,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function deleteWorkshopChief(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $jefesTaller = [];

        switch($rol){
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        }elseif (!$this->funcionario->bajaJefeTaller()) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showWorkshopChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol jefe_taller
                try{
                    $jefesTaller = Funcionario::getFuncionariosPorRol($rol, 'jefe_taller');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaJefesTaller.html.twig', [
                    'funcionarios' => $jefesTaller,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function deleteCashier(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $cajeros = [];

        switch($rol){
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        }elseif (!$this->funcionario->bajaCajero()) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showCashiers');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol jefe_taller
                try{
                    $cajeros = Funcionario::getFuncionariosPorRol($rol, 'cajero');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaCajeros.html.twig', [
                    'funcionarios' => $cajeros,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function deleteValet(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $valets = [];

        switch($rol){
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        }elseif (!$this->funcionario->bajaValetParking()) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showValets');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol jefe_taller
                try{
                    $valets = Funcionario::getFuncionariosPorRol($rol, 'valet_parking');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaValets.html.twig', [
                    'funcionarios' => $valets,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function deleteServiceExecutive(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $ejecutivos = [];

        switch($rol){
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        }elseif (!$this->funcionario->bajaEjecutivo()) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showServiceExecutives');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol jefe_taller
                try{
                    $ejecutivos = Funcionario::getFuncionariosPorRol($rol, 'ejecutivo');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaEjecutivos.html.twig', [
                    'funcionarios' => $ejecutivos,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
            case 'jefe_taller':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!$this->funcionario->bajaEjecutivo()) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showServiceExecutives');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        error_log($e->getMessage(), true);
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol jefe_taller
                try{
                    $ejecutivos = Funcionario::getFuncionariosPorRol($rol, 'ejecutivo');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/workshopChief/listaEjecutivos.html.twig', [
                    'ejecutivos' => $ejecutivos,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

/*    function doTransaction(): Response|RedirectResponse{
        $response=['success' => false, 'errors' => []];
    }
*/

    function showServiceAgenda(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        
        switch($rol){
            case 'gerente':
            case 'jefe_taller':
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showServiceDiagnosisRecords(): Response{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
            case 'jefe_taller':
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    private function validarFormAltaFuncionario($usuario, $host, $contrasena){
        $resultado = ['exito' => false, 'msj_error' => ""];

        // Validacion de campos vacios
        if (!isset($usuario, $host, $contrasena) || empty($usuario) || empty($host) || empty($contrasena)){
            $resultado['msj_error'] = "Debe llenar todos los campos.";
        }elseif(!$this->validarUsuario($usuario, 50)){
            $resultado['msj_error'] = "Por favor, ingrese un usuario válido.";
        } elseif(!$this->validarHost($host, 50)){
            $resultado['msj_error'] = "Por favor, ingrese un nombre de host válido.";
        } elseif(!$this->validarContrasena($contrasena, 6, 60)){
            $resultado['msj_error'] = "Por favor, ingrese una contraseña válida.";
        }
        return $resultado;
    }

    private function validarFormBajaFuncionario($usuario, $host){
        $resultado = ['exito' => false, 'msj_error' => ""];

        // Validacion de campos vacios
        if (!isset($usuario, $host) || empty($usuario) || empty($host)){
            $resultado['msj_error'] = "Debe llenar todos los campos.";
        }elseif(!$this->validarUsuario($usuario, 50)){
            $resultado['msj_error'] = "Por favor, ingrese un usuario válido.";
        } elseif(!$this->validarHost($host, 50)){
            $resultado['msj_error'] = "Por favor, ingrese un nombre de host válido.";
        }
        return $resultado;

    }

    private function validarFormModificacionFuncionario($usuarioActual, $hostActual, $usuarioNuevo, $hostNuevo){
        $resultado = ['exito' => false, 'msj_error' => ""];

        // Validacion de campos vacios
        if (!isset($usuarioActual, $hostActual, $usuarioNuevo, $hostNuevo) 
            || empty($usuarioActual) || empty($hostActual) || empty($usuarioNuevo)|| empty($hostNuevo)){
            $resultado['msj_error'] = "Debe llenar todos los campos.";
        }elseif(!$this->validarUsuario($usuarioActual, 50)){
            $resultado['msj_error'] = "Por favor, ingrese un usuario válido.";
        } elseif(!$this->validarHost($hostActual, 50)){
            $resultado['msj_error'] = "Por favor, ingrese un nombre de host válido.";
        }elseif(!$this->validarUsuario($usuarioNuevo, 50)){
            $resultado['msj_error'] = "Por favor, ingrese un nuevo usuario válido.";
        } elseif(!$this->validarHost($hostNuevo, 50)){
            $resultado['msj_error'] = "Por favor, ingrese un nuevo nombre de host válido.";
        }
        return $resultado;

    }

    private function validarFormCambioContraFuncionario($usuario, $host, $contraNueva){

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
            - Contiene un dominio válido, es igual a "localhost", o es una direccion ip valida
            - Un máximo específicado de $max caracteres
        */
        return preg_match("/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $host) || $host == "localhost" || filter_var($host, FILTER_VALIDATE_IP) && strlen($host) <= $max;
    }

    private function validarContrasena($str, $min, $max) {
        /* Verifica si la contraseña:
            - Tiene una longitud en el rango especificado por $min y $max.
        */
        return strlen($str) >= $min && strlen($str) <= $max;
    }
}
