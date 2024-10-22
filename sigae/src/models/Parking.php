<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class Parking extends Servicio {
    private $largo_plazo;
    private TipoPlazaParking $tipo_plaza;

    public function __construct($largo_plazo, TipoPlazaParking $tipo_plaza, $id, $precio, $fecha_inicio, $fecha_final){
        parent::__construct($id, $precio, $fecha_inicio, $fecha_final);
        $this->largo_plazo = $largo_plazo;
        $this->tipo_plaza = $tipo_plaza;
    }

    public function obtenerPlazasOcupadas($largo_plazo, $tipo_plaza, $fecha_inicio, $fecha_final){
        $ocupadas=[];
        $largo_plazo = !empty($largo_plazo) ? (int)$largo_plazo : 0;

        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");

            if($conn === false){
                throw new Exception("No se puede conectar con la base de datos.");
            }

            $stmt = $conn->prepare('SELECT numero_plaza FROM numero_plaza WHERE id_servicio IN (
                                        SELECT s.id FROM servicio s INNER JOIN parking p ON s.id = p.id_servicio
                                        WHERE   p.tipo_plaza = :tip_plaza AND
                                                p.largo_plazo = :lrg_plazo AND
                                                s.estado = "pendiente" AND (
                                                    s.fecha_inicio < :fecha_fin AND s.fecha_final > :fecha_ini
                                                )
                                    )');
            $stmt->bindParam(':tip_plaza', $tipo_plaza);
            $stmt->bindParam(':lrg_plazo', $largo_plazo);
            $stmt->bindParam('fecha_fin', $fecha_final);
            $stmt->bindParam('fecha_ini', $fecha_inicio);

            $stmt->execute();
            $ocupadas=$stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $ocupadas;

        } catch(Exception $e){
            error_log($e->getMessage()); // Registro del error en el log
            return false; // False si hubo un error de base de datos
        } finally {
            $conn = null;
        }
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
        $largo_plazo = !empty($largo_plazo) ? (int)$largo_plazo : 0;
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