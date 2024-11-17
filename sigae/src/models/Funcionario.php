<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use PDOException;
use Exception;

class Funcionario{
    private ?PDO $conn =null;
    private $usuario;
    private $host;
    private $rol;

    public function __construct($usuario, $host, $rol){
        $this->usuario = $usuario;
        $this->host = $host;
        $this->rol = $rol;
    }

    public function setDBConnection($rol){
        $this->conn = conectarDB($rol);
        if($this->conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }
        return $this;
    }

    public function getDBConnection(){
        return $this->conn;
    }

    public function getUsuario(){
        return $this->usuario;
    }

    public function setUsuario($usuario){
        $this->usuario = $usuario;

        return $this;
    }

    public function getHost(){
        return $this->host;
    }

    public function setHost($host){
        $this->host = $host;

        return $this;
    }

    public function getRol(){
        return $this->rol;
    }

    public function setRol($rol){
        $this->rol = $rol;

        return $this;
    }

    public function cerrarDBConnection(){
        $this->conn = null;
    }

    public function verificarCredenciales($contrasena) {
        try {
            $usuario = $this->getUsuario();
            $host = $this->getHost();

            // Conectar sin especificar base de datos
            $dsn = 'mysql:host='. $host;

            $pdo = new PDO($dsn, $usuario, $contrasena, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            return true;
    
        } catch (Exception $e) {
            // Debug error
            error_log($e->getMessage());
            return false;
        } finally {
            // Desconectar de la base de datos
            $pdo = null;
        }
    }

    public function iniciarFuncionario($usuario){
        try {
            $conn = conectarDB("app_user");
            $conn->exec("SET ROLE 'verificador_credenciales'");
            $stmt = $conn->prepare('SELECT from_user AS rol FROM mysql.role_edges WHERE to_user = :usuario');
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            
            // Obtiene un solo resultado
            $rolData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$rolData) {
                throw new PDOException("No se encontró un rol asignado a este usuario.");
            }
    
            // Setea el usuario y el rol si se encuentra
            $this->setUsuario($usuario);
            $this->setRol($rolData['rol']);
    
            return true;
    
        } catch (PDOException $e) {
            // Debug error
            throw $e;
        } finally {
            $conn = null; // Cierra la conexión
        }
    }

    public function altaGerente($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL alta_gerente(:usr, :host, :pswd);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':pswd', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function altaJefeDiagnostico($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL alta_jefe_diagnostico(:usr, :host, :pswd);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':pswd', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function altaJefeTaller($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL alta_jefe_taller(:usr, :host, :pswd);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':pswd', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function altaCajero($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL alta_cajero(:usr, :host, :pswd);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':pswd', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function altaValetParking($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL alta_valet_parking(:usr, :host, :pswd);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':pswd', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function altaEjecutivo($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL alta_ejecutivo(:usr, :host, :pswd);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':pswd', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function bajaGerente(){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL baja_gerente(:usr, :host);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function bajaJefeDiagnostico(){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL baja_jefe_diagnostico(:usr, :host);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function bajaJefeTaller(){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL baja_jefe_taller(:usr, :host);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function bajaCajero(){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL baja_cajero(:usr, :host);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function bajaValetParking(){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL baja_valet_parking(:usr, :host);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function bajaEjecutivo(){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL baja_ejecutivo(:usr, :host);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function modGerente($usuarioNuevo, $hostNuevo){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL modificar_gerente(:usr, :host, :usrNuevo, :hostNuevo);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':usrNuevo', $usuarioNuevo);
            $stmt->bindParam(':hostNuevo', $hostNuevo);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function modJefeDiagnostico($usuarioNuevo, $hostNuevo){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL modificar_jefe_diagnostico(:usr, :host, :usrNuevo, :hostNuevo);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':usrNuevo', $usuarioNuevo);
            $stmt->bindParam(':hostNuevo', $hostNuevo);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function modJefeTaller($usuarioNuevo, $hostNuevo){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL modificar_jefe_taller(:usr, :host, :usrNuevo, :hostNuevo);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':usrNuevo', $usuarioNuevo);
            $stmt->bindParam(':hostNuevo', $hostNuevo);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function modCajero($usuarioNuevo, $hostNuevo){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL modificar_cajero(:usr, :host, :usrNuevo, :hostNuevo);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':usrNuevo', $usuarioNuevo);
            $stmt->bindParam(':hostNuevo', $hostNuevo);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function modValetParking($usuarioNuevo, $hostNuevo){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL modificar_valet_parking(:usr, :host, :usrNuevo, :hostNuevo);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':usrNuevo', $usuarioNuevo);
            $stmt->bindParam(':hostNuevo', $hostNuevo);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function modEjecutivo($usuarioNuevo, $hostNuevo){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL modificar_ejecutivo(:usr, :host, :usrNuevo, :hostNuevo);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':usrNuevo', $usuarioNuevo);
            $stmt->bindParam(':hostNuevo', $hostNuevo);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function modContraGerente($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL mod_contra_gerente(:usr, :host, :contraNueva);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':contraNueva', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            error_log("Error cambiando la contraseña".$e->getMessage());
            throw $e;
        }

    }

    public function modContraJefeDiagnostico($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL mod_contra_jefe_diagnostico(:usr, :host, :contraNueva);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':contraNueva', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            error_log("Error cambiando la contraseña".$e->getMessage());
            throw $e;
        }

    }

    public function modContraJefeTaller($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL mod_contra_jefe_taller(:usr, :host, :contraNueva);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':contraNueva', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function modContraCajero($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL mod_contra_cajero(:usr, :host, :contraNueva);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':contraNueva', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function modContraValetParking($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL mod_contra_valet_parking(:usr, :host, :contraNueva);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':contraNueva', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public function modContraEjecutivo($contrasena){
        $usuario=$this->getUsuario();
        $host=$this->getHost();
        try{
            $stmt = $this->conn->prepare('CALL mod_contra_ejecutivo(:usr, :host, :contraNueva);');

            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);
            $stmt->bindParam(':contraNueva', $contrasena);

            $stmt->execute();

            return true;

        } catch(PDOException $e){
            throw $e;
        }

    }

    public static function existe($rol_loggeado, $usuario, $host){
        try{
            $conn = conectarDB($rol_loggeado);
            $stmt = $conn->prepare('SELECT COUNT(*) FROM mysql.user
                                    WHERE user = :usr
                                    AND host = :host;');
            $stmt->bindParam(':usr', $usuario);
            $stmt->bindParam(':host', $host);

            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            return $count !=0;
        } catch (PDOException $e){
            throw $e;
        } finally {
            $conn = null; // Cierra la conexión
        }
    }

    public static function getFuncionariosPorRol($rol_loggeado, $rol_a_buscar){
        try{
            $conn = conectarDB($rol_loggeado);

            // Ruta al archivo JSON con usuarios predeterminados
            $jsonFilePath = '/var/www/private/db/credencialesDB.json';
            $jsonContent = file_get_contents($jsonFilePath);
            $usuarios_json = json_decode($jsonContent, true);

            if ($usuarios_json === null) {
                throw new Exception("Error al decodificar el archivo JSON de usuarios predeteminados.");
            }

            // Obtener la lista de usuarios a excluir
            $usuarios_excluir = [];
            foreach ($usuarios_json as $rol => $datos) {
                if (isset($datos['user'])) {
                    $usuarios_excluir[] = $datos['user'];
                }
            }

            // Construir el placeholder para la cláusula NOT IN
            $placeholders = implode(',', array_fill(0, count($usuarios_excluir), '?'));

            $stmt = $conn->prepare('SELECT to_user AS usuario, to_host AS host
                                    FROM mysql.role_edges
                                    WHERE from_user = :rolBuscado
                                    AND to_user NOT IN (' . $placeholders . ') 
                                    ORDER BY to_user;');

            $stmt->bindParam(':rolBuscado', $rol_a_buscar);

            // Vincula los valores de usuarios a excluir
            foreach ($usuarios_excluir as $index => $usuario) {
                $stmt->bindValue($index + 1, $usuario);
            }

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e){
            //Debug
            error_log("Error al cargar funcionarios: ".$e);
            throw $e;
        } finally {
            $conn = null; // Cierra la conexión
        }
    }
}