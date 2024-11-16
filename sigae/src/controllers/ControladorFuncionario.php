<?php

namespace Sigae\Controllers;
use Sigae\Models\Funcionario;
use Sigae\Controllers\ControladorProducto;
use Sigae\Controllers\ControladorTaller;
use Sigae\Controllers\ControladorOrden;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use PDOException;
use Exception;

class ControladorFuncionario extends AbstractController {
    private $funcionario;
    private $controladorProducto;
    private $controladorTaller;
    private $controladorOrden;

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
            case 'admin_rol':
                return $this->render('administrator/dashboardAdmin.html.twig');
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

    function showWorkshopAvailability(): Response{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                return $this->render('employee/manager/reports/horariosDispTaller.html.twig');
            case 'jefe_taller':
                return $this->render('employee/workshopChief/reports/horariosDispTaller.html.twig');
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showWorkshopServices(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                try{                    
                    $this->controladorTaller = new ControladorTaller();
                    $serviciosDisp = $this->controladorTaller->getServiciosDisp();

                } catch(Exception $e){
                    $response['errors'][] = "Error obteniendo los servicios disponibles: ".$e->getMessage();
                }
                return $this->render('employee/manager/reports/serviciosDispTaller.html.twig', [
                    'response' => $response,
                    'serviciosDisp' => $serviciosDisp
                ]);
            case 'jefe_taller':
                try{                    
                    $this->controladorTaller = new ControladorTaller();
                    $serviciosDisp = $this->controladorTaller->getServiciosDisp();
    
                } catch(Exception $e){
                    $response['errors'][] = "Error obteniendo los servicios disponibles: ".$e->getMessage();
                }
                return $this->render('employee/workshopChief/reports/serviciosDispTaller.html.twig', [
                    'response' => $response,
                    'serviciosDisp' => $serviciosDisp
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showParkingAvailability(): Response{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                // TODO: implementar
                return $this->render('employee/manager/reports/horariosDispTaller.html.twig');
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showProductSalesReport(): Response{
        $response=['success' => false, 'errors' => []];
        
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                try{
                    $this->controladorOrden = new ControladorOrden();

                    // Obtener información de ventas de productos, por predeterminado, mensual
                    $infoPorProducto = $this->controladorOrden->obtenerIngresosBrutosProd($rol, 'mensual');

                    // Obtener información adicional para el reporte
                    $ingresosBrutosTotal = 0;
                    $cantidadVendidos = 0;

                    foreach($infoPorProducto as $producto){
                        $ingresosBrutosTotal += $producto['ingreso_bruto'];
                        $cantidadVendidos += $producto['cant_vendidos'];
                    }

                    return $this->render('employee/manager/reports/ventasProducto.html.twig', [
                        'response' => $response,
                        'ingresosBrutosTotal' => $ingresosBrutosTotal,
                        'cantidadVendidos' => $cantidadVendidos,
                        'productos' => $infoPorProducto
                    ]);

                } catch(Exception $e){
                    $response['errors'][] = "Error obteniendo reporte de ventas de productos: ".$e->getMessage();
                }
                return $this->render('employee/manager/reportes.html.twig', [
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showParkingSalesReport(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                try{
                    $this->controladorOrden = new ControladorOrden();

                    // Obtener información de ventas de productos, por predeterminado, mensual
                    $infoPorReserva = $this->controladorOrden->obtenerIngresosBrutosReserva($rol, 'mensual');

                    // Obtener total de ingresos brutos
                    $ingresosBrutosTotal = 0;
                    foreach($infoPorReserva as $reserva){
                        $ingresosBrutosTotal += $reserva['ingreso_bruto'];
                    }

                    return $this->render('employee/manager/reports/ventasParking.html.twig', [
                        'response' => $response,
                        'ingresosBrutosTotal' => $ingresosBrutosTotal,
                        'reservas' => $infoPorReserva
                    ]);

                } catch(Exception $e){
                    $response['errors'][] = "Error obteniendo reporte de estacionamiento: ".$e->getMessage();
                }
                return $this->render('employee/manager/reportes.html.twig', [
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showWorkshopServicesSalesReport(): Response{
        $rol=$_SESSION['rol'];
        switch($rol){
            case 'gerente':
                return $this->render('employee/manager/reports/horariosDispTaller.html.twig');
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
                    $this->controladorProducto = new ControladorProducto();
                    $productos = $this->controladorProducto->getProductosTodos('gerente');
                } catch(Exception $e){
                    $response['errors'][] = $e->getMessage();
                }
                return $this->render('employee/manager/inventario.html.twig', [
                    'productos' => $productos,
                    'response' => $response
                ]);
            case 'cajero':
                try{
                    $this->controladorProducto = new ControladorProducto();
                    $productos = $this->controladorProducto->getProductosTodos('cajero');
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
            case 'admin_rol':
                return $this->render('administrator/rolesFuncionarios.html.twig');
            case 'gerente':
                return $this->render('employee/manager/rolesFuncionarios.html.twig');
            case 'jefe_taller':
                return $this->redirectToRoute('showServiceExecutives');
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showManagers(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];

        $gerentes = [];

        switch($rol){
            case 'admin_rol':
                // Obtener arreglo con usuarios con rol gerente
                try{
                    $gerentes = Funcionario::getFuncionariosPorRol($rol, 'gerente');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaGerentes.html.twig', [
                    'funcionarios' => $gerentes,
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function showDiagnoseChiefs(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];

        $jefesDiagnostico = [];

        switch($rol){
            case 'admin_rol':
                // Obtener arreglo con usuarios con rol jefe_diagnostico
                try{
                    $jefesDiagnostico = Funcionario::getFuncionariosPorRol($rol, 'jefe_diagnostico');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaJefesDiagnostico.html.twig', [
                    'funcionarios' => $jefesDiagnostico,
                    'response' => $response
                ]);
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
            case 'admin_rol':
                // Obtener arreglo con usuarios con rol jefe_taller
                try{
                    $jefesTaller = Funcionario::getFuncionariosPorRol($rol, 'jefe_taller');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaJefesTaller.html.twig', [
                    'funcionarios' => $jefesTaller,
                    'response' => $response
                ]);
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
            case 'admin_rol':
                // Obtener arreglo con usuarios con rol cajero
                try{
                    $cajeros = Funcionario::getFuncionariosPorRol($rol, 'cajero');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaCajeros.html.twig', [
                    'funcionarios' => $cajeros,
                    'response' => $response
                ]);
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
            case 'admin_rol':
                // Obtener arreglo con usuarios con rol valet_parking
                try{
                    $valets = Funcionario::getFuncionariosPorRol($rol, 'valet_parking');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaValets.html.twig', [
                    'funcionarios' => $valets,
                    'response' => $response
                ]);
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
            case 'admin_rol':
                // Obtener arreglo con usuarios con rol ejecutivo
                try{
                    $ejecutivos = Funcionario::getFuncionariosPorRol($rol, 'ejecutivo');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaEjecutivos.html.twig', [
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
                    'funcionarios' => $ejecutivos,
                    'response' => $response
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function addManager(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $gerentes = [];

        $rolUsuarioNuevo = 'gerente';

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);
                if ($validacion['exito'] === true) {
                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("Este usuario ya existe.");
                        } elseif (!$this->funcionario->altaGerente($contrasena)) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showManagers');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol gerente
                try{
                    $gerentes = Funcionario::getFuncionariosPorRol($rol, 'gerente');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaGerentes.html.twig', [
                    'funcionarios' => $gerentes,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function addDiagnoseChief(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $jefesDiagnostico = [];

        $rolUsuarioNuevo = 'jefe_diagnostico';

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);
                if ($validacion['exito'] === true) {
                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("Este usuario ya existe.");
                        } elseif (!$this->funcionario->altaJefeDiagnostico($contrasena)) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showDiagnoseChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

                return $this->render('administrator/listaJefesDiagnostico.html.twig', [
                    'funcionarios' => $jefesDiagnostico,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {
                    error_log("If ".$rol);

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("Este usuario ya existe.");
                        } elseif (!$this->funcionario->altaJefeDiagnostico($contrasena)) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showDiagnoseChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

    function addWorkshopChief(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $jefesTaller = [];

        $rolUsuarioNuevo = 'jefe_taller';

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('admin_rol');
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

                return $this->render('administrator/listaJefesTaller.html.twig', [
                    'funcionarios' => $jefesTaller,
                    'response' => $response
                ]);
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
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('admin_rol');
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

                return $this->render('administrator/listaCajeros.html.twig', [
                    'funcionarios' => $cajeros,
                    'response' => $response
                ]);

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
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('admin_rol');
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

                return $this->render('administrator/listaValets.html.twig', [
                    'funcionarios' => $valets,
                    'response' => $response
                ]);
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
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormAltaFuncionario($_POST["usuario"], $_POST["host"], $_POST["contrasena"]);

                if($validacion['exito'] == true){

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contrasena = $_POST["contrasena"];

                    $this->funcionario = new Funcionario($usuario, $host, $rolUsuarioNuevo);
                    $this->funcionario->setDBConnection('admin_rol');
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

                return $this->render('administrator/listaEjecutivos.html.twig', [
                    'funcionarios' => $ejecutivos,
                    'response' => $response
                ]);
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

    function deleteManager(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $gerentes = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        }elseif (!$this->funcionario->bajaGerente()) {
                            throw new Exception("No se pudo registrar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showManagers');
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

                // Obtener arreglo con usuarios con rol gerente
                try{
                    $gerentes = Funcionario::getFuncionariosPorRol($rol, 'gerente');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaGerentes.html.twig', [
                    'funcionarios' => $gerentes,
                    'response' => $response  // Aquí pasa la respuesta a la vista
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
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
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

                return $this->render('administrator/listaJefesDiagnostico.html.twig', [
                    'funcionarios' => $jefesDiagnostico,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

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
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
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

                return $this->render('administrator/listaJefesTaller.html.twig', [
                    'funcionarios' => $jefesTaller,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
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
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
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

                return $this->render('administrator/listaCajeros.html.twig', [
                    'funcionarios' => $cajeros,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
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
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
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

                return $this->render('administrator/listaValets.html.twig', [
                    'funcionarios' => $valets,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
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
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                $validacion = $this->validarFormBajaFuncionario($_POST["usuario"], $_POST["host"]);

                if ($validacion['exito'] == true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
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

                return $this->render('administrator/listaEjecutivos.html.twig', [
                    'funcionarios' => $ejecutivos,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
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
                    $this->funcionario->setDBConnection('jefe_taller');
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

    function editManager(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $gerentes = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"], $_POST["hostActual"], 
                    $_POST["usuarioNuevo"], $_POST["hostNuevo"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modGerente($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showManagers');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol gerente
                try{
                    $gerentes = Funcionario::getFuncionariosPorRol($rol, 'gerente');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaGerentes.html.twig', [
                    'funcionarios' => $gerentes,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function editDiagnoseChief(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $jefesDiagnostico = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"], $_POST["hostActual"], 
                    $_POST["usuarioNuevo"], $_POST["hostNuevo"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modJefeDiagnostico($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showDiagnoseChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

                return $this->render('administrator/listaJefesDiagnostico.html.twig', [
                    'funcionarios' => $jefesDiagnostico,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"], $_POST["hostActual"], 
                    $_POST["usuarioNuevo"], $_POST["hostNuevo"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modJefeDiagnostico($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showDiagnoseChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

    function editWorkshopChief(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $jefesTaller = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"], $_POST["hostActual"], 
                    $_POST["usuarioNuevo"], $_POST["hostNuevo"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modJefeTaller($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showWorkshopChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

                return $this->render('administrator/listaJefesTaller.html.twig', [
                    'funcionarios' => $jefesTaller,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"], $_POST["hostActual"], 
                    $_POST["usuarioNuevo"], $_POST["hostNuevo"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modJefeTaller($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showWorkshopChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

    function editCashier(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $cajeros = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"], $_POST["hostActual"], 
                    $_POST["usuarioNuevo"], $_POST["hostNuevo"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modCajero($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showCashiers');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol cajero
                try{
                    $cajeros = Funcionario::getFuncionariosPorRol($rol, 'ccajero');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaJefesTaller.html.twig', [
                    'funcionarios' => $cajeros,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"], $_POST["hostActual"], 
                    $_POST["usuarioNuevo"], $_POST["hostNuevo"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modCajero($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showCashiers');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol cajero
                try{
                    $cajeros = Funcionario::getFuncionariosPorRol($rol, 'ccajero');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('employee/manager/listaJefesTaller.html.twig', [
                    'funcionarios' => $cajeros,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function editValet(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $valets = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"], $_POST["hostActual"], 
                    $_POST["usuarioNuevo"], $_POST["hostNuevo"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modValetParking($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showValets');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol valet
                try{
                    $valets = Funcionario::getFuncionariosPorRol($rol, 'valet_parking');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaValets.html.twig', [
                    'funcionarios' => $valets,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"], $_POST["hostActual"], 
                    $_POST["usuarioNuevo"], $_POST["hostNuevo"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modValetParking($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showValets');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol valet
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

    function editServiceExecutive(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $ejecutivos = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"], $_POST["hostActual"], 
                    $_POST["usuarioNuevo"], $_POST["hostNuevo"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modEjecutivo($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showServiceExecutives');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

                return $this->render('administrator/listaEjecutivos.html.twig', [
                    'funcionarios' => $ejecutivos,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"], $_POST["hostActual"], 
                    $_POST["usuarioNuevo"], $_POST["hostNuevo"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modEjecutivo($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showServiceExecutives');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

                return $this->render('employee/manager/listaEjecutivos.html.twig', [
                    'funcionarios' => $ejecutivos,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            case 'jefe_taller':
                $validacion = ['exito' => false, 'msj_error' => ""];

                $validacion = $this->validarFormModificacionFuncionario(
                    $_POST["usuarioActual"],
                    $_POST["hostActual"],
                    $_POST["usuarioNuevo"],
                    $_POST["hostNuevo"]
                );
                error_log("Debug validacion " . $validacion['exito'] . $validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuarioActual = $_POST["usuarioActual"];
                    $hostActual = $_POST["hostActual"];
                    $usuarioNuevo = $_POST["usuarioNuevo"];
                    $hostNuevo = $_POST["hostNuevo"];

                    $this->funcionario = new Funcionario($usuarioActual, $hostActual, null);
                    $this->funcionario->setDBConnection('jefe_taller');
                    try {
                        if (!Funcionario::existe($rol, $usuarioActual, $hostActual)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modEjecutivo($usuarioNuevo, $hostNuevo)) {
                            throw new Exception("No se pudo modificar el usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showServiceExecutives');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else {
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol ejecutivo
                try {
                    $ejecutivos = Funcionario::getFuncionariosPorRol($rol, 'ejecutivo');
                } catch (Exception $e) {
                    $response['errors'][] = "Error al cargar funcionarios: " . $e->getMessage();
                }

                return $this->render('employee/workshopChief/listaEjecutivos.html.twig', [
                    'ejecutivos' => $ejecutivos,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function modPswdManager(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $gerentes = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"], $_POST["host"], $_POST["contraNueva"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraGerente($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showManagers');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else{
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol gerente
                try{
                    $gerentes = Funcionario::getFuncionariosPorRol($rol, 'gerente');
                } catch(Exception $e){
                    $response['errors'][] = "Error al cargar funcionarios: ".$e->getMessage();
                }

                return $this->render('administrator/listaGerentes.html.twig', [
                    'funcionarios' => $gerentes,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function modPswdDiagnoseChief(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $jefesDiagnostico = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"], $_POST["host"], $_POST["contraNueva"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraJefeDiagnostico($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showDiagnoseChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

                return $this->render('administrator/listaJefesDiagnostico.html.twig', [
                    'funcionarios' => $jefesDiagnostico,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"], $_POST["host"], $_POST["contraNueva"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraJefeDiagnostico($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showDiagnoseChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

    function modPswdWorkshopChief(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $jefesTaller = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"], $_POST["host"], $_POST["contraNueva"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraJefeTaller($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showWorkshopChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

                return $this->render('administrator/listaJefesTaller.html.twig', [
                    'funcionarios' => $jefesTaller,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"], $_POST["host"], $_POST["contraNueva"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraJefeTaller($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showWorkshopChiefs');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

    function modPswdCashier(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $cajeros = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"], $_POST["host"], $_POST["contraNueva"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraCajero($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showCashiers');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

                return $this->render('administrator/listaCajeros.html.twig', [
                    'funcionarios' => $cajeros,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"], $_POST["host"], $_POST["contraNueva"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraCajero($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showCashiers');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function modPswdValet(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $valets = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"], $_POST["host"], $_POST["contraNueva"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraValetParking($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showValets');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

                return $this->render('administrator/listaValets.html.twig', [
                    'funcionarios' => $valets,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"], $_POST["host"], $_POST["contraNueva"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraValetParking($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showValets');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
        
    }

    function modPswdServiceExecutive(){
        $response = ['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $ejecutivos = [];

        switch($rol){
            case 'admin_rol':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"], $_POST["host"], $_POST["contraNueva"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('admin_rol');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraEjecutivo($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showServiceExecutives');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

                return $this->render('administrator/listaEjecutivos.html.twig', [
                    'funcionarios' => $ejecutivos,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
            case 'gerente':
                $validacion = ['exito' => false, 'msj_error' => "" ];
                
                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"], $_POST["host"], $_POST["contraNueva"]
                );
                error_log("Debug validacion ".$validacion['exito'].$validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('gerente');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraEjecutivo($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showServiceExecutives');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
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

                return $this->render('employee/manager/listaEjecutivos.html.twig', [
                    'funcionarios' => $ejecutivos,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

            case 'jefe_taller':
                $validacion = ['exito' => false, 'msj_error' => ""];

                $validacion = $this->validarFormCambioContraFuncionario(
                    $_POST["usuario"],
                    $_POST["host"],
                    $_POST["contraNueva"]
                );
                error_log("Debug validacion " . $validacion['exito'] . $validacion['msj_error']);
                if ($validacion['exito'] === true) {

                    $usuario = $_POST["usuario"];
                    $host = $_POST["host"];
                    $contraNueva = $_POST["contraNueva"];

                    $this->funcionario = new Funcionario($usuario, $host, null);
                    $this->funcionario->setDBConnection('jefe_taller');
                    try {
                        if (!Funcionario::existe($rol, $usuario, $host)) {
                            throw new Exception("No se encontró un usuario registrado con los datos ingresados.");
                        } elseif (!$this->funcionario->modContraEjecutivo($contraNueva)) {
                            throw new Exception("No se pudo cambiar la clave del usuario.");
                        } else {
                            // Redirigir a la lista actualizada
                            return $this->redirectToRoute('showServiceExecutives');
                        }
                    } catch (Exception $e) {
                        // Añade el mensaje de error al array de errores
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->funcionario->cerrarDBConnection();
                    }
                } else {
                    $response['errors'][] = $validacion['msj_error'];
                }

                // Obtener arreglo con usuarios con rol ejecutivo
                try {
                    $ejecutivos = Funcionario::getFuncionariosPorRol($rol, 'ejecutivo');
                } catch (Exception $e) {
                    $response['errors'][] = "Error al cargar funcionarios: " . $e->getMessage();
                }

                return $this->render('employee/workshopChief/listaEjecutivos.html.twig', [
                    'ejecutivos' => $ejecutivos,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);

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
        } else{
            $resultado['exito'] = true;
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
        } else{
            $resultado['exito'] = true;
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
        } else{
            $resultado['exito'] = true;
        }
        return $resultado;

    }

    private function validarFormCambioContraFuncionario($usuario, $host, $contraNueva){
        $resultado = ['exito' => false, 'msj_error' => ""];

        // Validacion de campos vacios
        if (!isset($usuario, $host, $contraNueva) 
            || empty($usuario) || empty($host) || empty($contraNueva)){
            $resultado['msj_error'] = "Debe llenar todos los campos.";
        }elseif(!$this->validarUsuario($usuario, 50)){
            $resultado['msj_error'] = "Por favor, ingrese un usuario válido.";
        } elseif(!$this->validarHost($host, 50)){
            $resultado['msj_error'] = "Por favor, ingrese un nombre de host válido.";
        } elseif(!$this->validarContrasena($contraNueva, 6, 60)){
            $resultado['msj_error'] = "Por favor, ingrese una contraseña válida.";
        } else{
            $resultado['exito'] = true;
        }
        return $resultado;


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
