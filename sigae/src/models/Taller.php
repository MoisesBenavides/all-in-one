<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class Taller extends Servicio{
    private ?PDO $conn=null;
    private $tipo;
    private $descripcion;
    private $diagnostico;
    private $tiempo_estimado;

    public function __construct($tipo, $descripcion, $diagnostico, $tiempo_estimado, $id, $precio, $fecha_inicio, $fecha_final){
        parent::__construct($id, $precio, $fecha_inicio, $fecha_final);
        $this->tipo = $tipo;
        $this->descripcion = $descripcion;
        $this->diagnostico = $diagnostico;
        $this->tiempo_estimado = $tiempo_estimado;
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

    public function getTipo(){
        return $this->tipo;
    }

    public function setTipo($tipo){
        $this->tipo = $tipo;
    }

    public function getDescripcion(){
        return $this->descripcion;
    }

    public function setDescripcion($descripcion){
        $this->descripcion = $descripcion;
    }

    public function getDiagnostico(){
        return $this->diagnostico;
    }

    public function setDiagnostico($diagnostico){
        $this->diagnostico = $diagnostico;
    }

    public function getTiempo_estimado(){
        return $this->tiempo_estimado;
    }

    public function setTiempo_estimado($tiempo_estimado){
        $this->tiempo_estimado = $tiempo_estimado;
    }

    public function comenzarTransaccion() {
        if ($this->conn) {
            $this->conn->beginTransaction();
        }
    }

    // Método para confirmar una transacción
    public function confirmarTransaccion() {
        if ($this->conn) {
            $this->conn->commit();
        }
    }

    // Método para revertir una transacción
    public function deshacerTransaccion() {
        if ($this->conn) {
            $this->conn->rollback();
        }
    }

    public function cerrarDBConnection(){
        $this->conn = null;
    }

    public function reservar($matricula) {
        $precio = $this->getPrecio();
        $fecha_inicio = $this->getFecha_inicio();
        $fecha_final = $this->getFecha_final();
        $estado = $this->getEstado();
    
        try {
            $stmt = $this->conn->prepare('INSERT INTO servicio (matricula, precio, fecha_inicio, fecha_final, estado) 
                                    VALUES (:mat, :precio, :fecha_ini, :fecha_fin, :estado)');
    
            $stmt->bindParam(':mat', $matricula);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':fecha_ini', $fecha_inicio);
            $stmt->bindParam(':fecha_fin', $fecha_final);
            $stmt->bindParam(':estado', $estado);
                
            $stmt->execute();
            $this->setId($this->conn->lastInsertId());
    
            return $this->reservarTaller() !== false;
    
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
            
        }
    }

    public static function obtenerLapsosOcupados($rol, $dia){
        $conn = conectarDB($rol);
        // Obtener fecha en formato Y-m-d
        $fecha = $dia->format('Y-m-d');

        if($conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }
        try{
            $stmt = $conn->prepare('SELECT s.fecha_inicio, s.fecha_final 
                                            FROM servicio s 
                                            JOIN taller t ON s.id=t.id_servicio
                                            WHERE s.estado="pendiente" AND DATE(s.fecha_inicio) = :fecha');
            $stmt->bindParam(':fecha', $fecha);
            $stmt->execute();

            $lapsosOcupados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $lapsosOcupados;

        } catch(Exception $e){
            error_log("Error al obtener los lapsos ocupados: ".$e->getMessage());
            throw $e;
            return;
        }
    }   

    private function reservarTaller(){
        $id=$this->getId();
        $tipo=$this->getTipo();
        $descripcion=$this->getDescripcion();
        $tiempo_estimado=$this->getTiempo_estimado();

        try {
            $stmt = $this->conn->prepare('INSERT INTO taller (id_servicio, tipo, descripcion, tiempo_estimado) 
                                    VALUES (:id, :tipo, :descr, :tiempo)');

            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':descr', $descripcion);
            $stmt->bindParam(':tiempo', $tiempo_estimado);
                
            $stmt->execute();

        } catch (Exception $e) {
            error_log("Error procesar la reserva: " . $e->getMessage());
        }
        
    }

    public function modificar(){
    }

    public function getServicios(){
    }
}