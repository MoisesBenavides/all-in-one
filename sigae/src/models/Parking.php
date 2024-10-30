<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class Parking extends Servicio {
    private ?PDO $conn =null;
    private $largo_plazo;
    private TipoPlazaParking $tipo_plaza;

    public function __construct($largo_plazo, TipoPlazaParking $tipo_plaza, $id, $precio, $fecha_inicio, $fecha_final){
        parent::__construct($id, $precio, $fecha_inicio, $fecha_final);
        $this->largo_plazo = $largo_plazo;
        $this->tipo_plaza = $tipo_plaza;
    }

    public function setDBConnection($user, $password , $hostname){
        $this->conn = conectarDB($user, $password, $hostname);
        if($this->conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }
        return $this;
    }

    public function getDBConnection(){
        return $this->conn;
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


    public function apartarPlaza($numero_plaza){
        //Debug numero plaza
        error_log("Plaza recibida en modelo ".$numero_plaza);

        $id_servicio=$this->getId();
        try {
            $stmt = $this->conn->prepare('INSERT INTO numero_plaza (numero_plaza, id_servicio) VALUES (:num_plaza, :id_serv)');
            $stmt->bindParam(':num_plaza', $numero_plaza);
            $stmt->bindParam(':id_serv', $id_servicio);

            $stmt->execute();

            return true;

        } catch(Exception $e) {
            error_log($e->getMessage()); // Registro del error en el log
            return false; // False si hubo un error de base de datos
        }
    }

    public function obtenerPlazasOcupadas(){
        $tipo_plaza=$this->getTipo_plaza();
        $largo_plazo=$this->getLargo_plazo();
        $largo_plazo = !empty($largo_plazo) ? (int)$largo_plazo : 0;
        $fecha_inicio = $this->getFecha_inicio();
        $fecha_final = $this->getFecha_final();
        
        $ocupadas=[];

        try {
            $stmt = $this->conn->prepare('SELECT numero_plaza FROM numero_plaza WHERE id_servicio IN (
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

            $ocupadas=$stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            //Debug
            (!empty($ocupadas)) ? error_log(print_r($ocupadas, true)) : error_log("No se encontraron plazas ocupadas");
            
            return $ocupadas;

        } catch(Exception $e){
            error_log($e->getMessage()); // Registro del error en el log
            return false; // False si hubo un error de base de datos
        }
    }


    public function reservarServicio($matricula) {
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
    
            return $this->reservarParking() !== false;
    
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false; 
        }
    }

    private function reservarParking(){
        $id=$this->getId();
        $largo_plazo=$this->getLargo_plazo();
        $largo_plazo = !empty($largo_plazo) ? (int)$largo_plazo : 0;
        $tipo_plaza=$this->getTipo_plaza();

        try {
            $stmt = $this->conn->prepare('INSERT INTO parking (id_servicio, largo_plazo, tipo_plaza) 
                                    VALUES (:id, :lrg_plazo, :tip_plaza)');

            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':lrg_plazo', $largo_plazo);
            $stmt->bindParam(':tip_plaza', $tipo_plaza);
                
            $stmt->execute();

        } catch (Exception $e) {
            error_log("Error procesando la reserva: " . $e->getMessage());
        }
        
    }

    public function cambiarServicio(){
    }

    public function cancelarServicio(){
    }

    public function getServicios(){
    }

}