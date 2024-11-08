<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class Taller extends Servicio{
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
        } finally{
            $conn = null;
        }
    }   

    public static function cargarAgenda($rol, $fecha){
        $conn = conectarDB($rol);

        if($conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }
        try{
            $stmt = $conn->prepare('SELECT s.fecha_inicio, s.fecha_final, s.matricula , s.estado, 
                                            t.tipo, t.descripcion, t.diagnostico 
                                        FROM servicio s 
                                        JOIN taller t ON s.id=t.id_servicio 
                                        WHERE DATE(s.fecha_inicio) = :fecha AND s.estado <> "cancelado"');
            $stmt->bindParam(':fecha', $fecha);
            $stmt->execute();

            $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $servicios;

        } catch(Exception $e){
            // Debug
            error_log("Error al cargar la agenda: ".$e->getMessage());
            throw new Exception ("Error al cargar la agenda: ".$e->getMessage());
        } finally{
            $conn = null;
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

    public function actualizar(){
        $id = $this->getId();
        $estado = $this->getEstado();

        try {
            $stmt = $this->conn->prepare('UPDATE servicio SET estado = :est WHERE id = :id');
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':est', $estado);
                
            $stmt->execute();

            return true;

        } catch (Exception $e) {
            error_log("Error al actualizar servicio: " . $e->getMessage());
            throw new Exception("Error al actualizar el servicio: " . $e->getMessage());
        }
    }

    public function actualizarDiagnostico(){
        $id = $this->getId();
        $diagnostico = $this->getDiagnostico();

        try {
            $stmt = $this->conn->prepare('UPDATE taller SET diagnostico = :diag WHERE id_servicio = :id');
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':diag', $diagnostico);
                
            $stmt->execute();
      
            return true;

        } catch (Exception $e) {
            error_log("Error al actualizar el diagnostico: " . $e->getMessage());
            throw new Exception("Error al actualizar el diagnóstico: " . $e->getMessage());
        }
    }

    public function getServicios(){
    }
}