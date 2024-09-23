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

    public function reservarServicio($matricula) {
        $precio = $this->getPrecio();
        $fecha_inicio = $this->getFecha_inicio();
        $fecha_final = $this->getFecha_final();
        $estado = $this->getEstado();
    
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
    
            if ($conn === false) {
                throw new Exception("No se pudo conectar a la base de datos.");
            }
    
            // Log de datos a insertar
            error_log("Datos a insertar en servicio: " . json_encode(compact('matricula', 'precio', 'fecha_inicio', 'fecha_final', 'estado')));
    
            $stmt = $conn->prepare('INSERT INTO servicio (matricula, precio, fecha_inicio, fecha_final, estado) 
                                    VALUES (:mat, :precio, :fecha_ini, :fecha_fin, :estado)');
    
            $stmt->bindParam(':mat', $matricula);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':fecha_ini', $fecha_inicio);
            $stmt->bindParam(':fecha_fin', $fecha_final);
            $stmt->bindParam(':estado', $estado);
                
            $stmt->execute();
            $this->setId($conn->lastInsertId());
    
            return $this->reservarTaller() !== false;
    
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo "Error procesar la reserva: " . $e->getMessage();
            return false;
            
        } finally {
            $conn = null;
        }
    }
    

    private function reservarTaller(){
        $id=$this->getId();
        $tipo=$this->getTipo();
        $descripcion=$this->getDescripcion();
        $tiempo_estimado=$this->getTiempo_estimado();

        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            
            if ($conn === false) {
                throw new Exception("No se pudo conectar a la base de datos.");
            }

            // Log de datos a insertar
            error_log("Datos a insertar en taller: " . json_encode(compact('id', 'tipo', 'descripcion', 'tiempo_estimado')));
    
            $stmt = $conn->prepare('INSERT INTO taller (id_servicio, tipo, descripcion, tiempo_estimado) 
                                    VALUES (:id, :tipo, :descr, :tiempo)');

            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':descr', $descripcion);
            $stmt->bindParam(':tiempo', $tiempo_estimado);
                
            $stmt->execute();

        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log("Error procesar la reserva: " . $e->getMessage());
        } finally {
            $conn = null;
        }
        
    }

    public function cambiarServicio(){
    }

    public function cancelarServicio(){
    }
}