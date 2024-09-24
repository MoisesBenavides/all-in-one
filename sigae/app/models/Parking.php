<?php

require_once '../app/db_config/db_conn.php';
require_once 'TipoPlazaParking.php';
require_once 'Servicio.php';

class Parking extends Servicio {
    private $largo_plazo;
    private TipoPlazaParking $tipo_plaza;

    public function __construct($largo_plazo, TipoPlazaParking $tipo_plaza, $id, $precio, $fecha_inicio, $fecha_final){
        parent::__construct($id, $precio, $fecha_inicio, $fecha_final);
        $this->largo_plazo = $largo_plazo;
        $this->tipo_plaza = $tipo_plaza;
    }

    public function getTipo_pLaza(): string{
        return $this->tipo_plaza->value;
    }

    public function setTipo_plaza(TipoPlazaParking $tipo_plaza){
        $this->tipo_plaza = $tipo_plaza;
    }
    public function getLargo_plazo(){
        return $this->largo_plazo;
    }

    public function setLargo_plazo($largo_plazo){
        $this->largo_plazo = $largo_plazo;

        return $this;
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
    
            return $this->reservarParking() !== false;
    
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo "Error procesar la reserva: " . $e->getMessage();
            return false;
            
        } finally {
            $conn = null;

        }
    }

    private function reservarParking(){
        $id=$this->getId();
        $largo_plazo=$this->getLargo_plazo();
        $tipo_plaza=$this->getTipo_plaza();

        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            
            if ($conn === false) {
                throw new Exception("No se pudo conectar a la base de datos.");
            }

            // Log de datos a insertar
            error_log("Datos a insertar en parking: " . json_encode(compact('id', 'largo_plazo', 'tipo_plaza')));
    
            $stmt = $conn->prepare('INSERT INTO parking (id_servicio, largo_plazo, tipo_plaza) 
                                    VALUES (:id, :lrg_plazo, :tip_plaza)');

            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':lrg_plazo', $largo_plazo);
            $stmt->bindParam(':tip_plaza', $tipo_plaza);
                
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

    public function getServiciosParking(){
    }

}