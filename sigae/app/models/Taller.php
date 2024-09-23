<?php

require_once '../app/db_config/db_conn.php';
require_once 'Servicio.php';

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

    public function reservarServicio($matricula){
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            
            if ($conn === false) {
                throw new Exception("No se pudo conectar a la base de datos.");
            }
    
            $stmt = $conn->prepare('INSERT INTO servicio (matricula, precio, fecha_inicio, fecha_final, estado) 
                                    VALUES (:mat, :precio, :fecha_ini, :fecha_fin, :estado)');
            
            $stmt->bindParam(':mat', $matricula);
            $stmt->bindParam(':precio', $this->getPrecio());
            $stmt->bindParam(':fecha_ini', $this->getFecha_inicio());
            $stmt->bindParam(':fecha_fin', $this->getFecha_final());
            $stmt->bindParam(':estado', $this->getEstado());
                
            $stmt->execute();
            return true; // Reserva exitosa;

        } catch (Exception $e) {
            error_log($e->getMessage());
            echo "Error procesar la reserva: " . $e->getMessage();
            return false;
            
        } finally {
            $conn = null;
        }
        
    }

    public function reservarTaller(){
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            
            if ($conn === false) {
                throw new Exception("No se pudo conectar a la base de datos.");
            }
    
            $stmt = $conn->prepare('INSERT INTO taller (id_servicio, tipo, descripcion, tiempo_estimado) 
                                    VALUES (:id, :tipo, :descr, :tiempo)');

            $stmt->bindParam(':id', $this->get());
            $stmt->bindParam(':tipo', $this->getTipo());
            $stmt->bindParam(':descr', $this->getDescripcion());
            $stmt->bindParam(':tiempo', $this->getTiempo_estimado());
                
            $stmt->execute();
            return true; // Reserva exitosa;

        } catch (Exception $e) {
            error_log($e->getMessage());
            echo "Error procesar la reserva: " . $e->getMessage();
            return false;
            
        } finally {
            $conn = null;
        }
        
    }
}