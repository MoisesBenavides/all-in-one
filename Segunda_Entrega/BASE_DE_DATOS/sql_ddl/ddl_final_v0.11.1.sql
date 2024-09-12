-- CREACION DE TABLAS
-- Cliente
CREATE TABLE cliente (
    id INT NOT NULL,
    ci INT NOT NULL UNIQUE,
    email VARCHAR(63) NOT NULL UNIQUE,
    nombre VARCHAR(23) NOT NULL,
    apellido VARCHAR(23) NOT NULL,
    telefono INT NOT NULL,
    PRIMARY KEY (id)
);

-- Vehiculo
CREATE TABLE vehiculo (
    matricula VARCHAR(8) NOT NULL,
    marca VARCHAR(32) NULL,
    modelo VARCHAR(32) NULL,
    tipo ENUM('auto', 'moto', 'camioneta', 'camion', 'utilitario') NOT NULL,
    color VARCHAR(6) NULL,
    PRIMARY KEY (matricula)
);

-- tiene
CREATE TABLE tiene (
    id_cliente INT NOT NULL,
    matricula VARCHAR(8) NOT NULL,
    PRIMARY KEY (id_cliente, matricula),
    FOREIGN KEY (id_cliente) REFERENCES cliente(id),
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula)
);

-- Producto
CREATE TABLE producto (
    id INT NOT NULL,
    upc VARCHAR(13) NOT NULL UNIQUE,
    precio DECIMAL(10,2) NOT NULL,
    marca VARCHAR(23) NOT NULL,
    fecha_creacion DATETIME NOT NULL,
    stock INT NOT NULL,
    PRIMARY KEY (id)
);

-- Neumatico
CREATE TABLE neumatico (
    id_producto INT NOT NULL,
    tamano VARCHAR(16) NOT NULL,
    modelo VARCHAR(23) NOT NULL,
    tipo CHAR(2) NOT NULL,
    PRIMARY KEY (id_producto),
    FOREIGN KEY (id_producto) REFERENCES producto(id)
);

-- Otro_producto
CREATE TABLE otro_producto (
    id_producto INT NOT NULL,
    nombre VARCHAR(63),
    PRIMARY KEY (id_producto),
    FOREIGN KEY (id_producto) REFERENCES producto(id)
);

-- Transaccion
CREATE TABLE transaccion (
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    tipo ENUM('ingreso', 'egreso'),
    fecha DATETIME NOT NULL,
    PRIMARY KEY (id_producto),
    FOREIGN KEY (id_producto) REFERENCES producto(id)
);

-- Servicio
CREATE TABLE servicio (
    id INT NOT NULL,
    matricula VARCHAR(8) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_final DATETIME NOT NULL,
    estado ENUM('pendiente', 'realizado', 'cancelado'),
    PRIMARY KEY (id),
    FOREIGN KEY (matricula) REFERENCES vehiculo(matricula)
);

-- Taller
CREATE TABLE taller (
    id_servicio INT NOT NULL,
    tipo VARCHAR(3) NOT NULL,
    descripcion TEXT NOT NULL,
    tiempo_estimado INT NOT NULL,
    diagnostico TEXT NULL,
    PRIMARY KEY (id_servicio),
    FOREIGN KEY (id_servicio) REFERENCES servicio(id)
);

-- Parking
CREATE TABLE parking (
    id_servicio INT NOT NULL,
    largo_plazo BOOLEAN NOT NULL,
    tipo_plaza ENUM('auto', 'moto'),
    PRIMARY KEY (id_servicio),
    FOREIGN KEY (id_servicio) REFERENCES servicio(id)
);

-- Numero de plaza
CREATE TABLE numero_plaza (
    numero_plaza INT NOT NULL,
    id_servicio INT NOT NULL,
    PRIMARY KEY (numero_plaza, id_servicio),
    FOREIGN KEY (id_servicio) REFERENCES parking(id_servicio)
);

-- Ejecutivo
CREATE TABLE ejecutivo (
    id_empleado INT NOT NULL,
    PRIMARY KEY (id_empleado)
);

-- realiza
CREATE TABLE realiza (
    id_empleado INT NOT NULL,
    id_servicio INT NOT NULL,
    PRIMARY KEY (id_empleado, id_servicio),
    FOREIGN KEY (id_empleado) REFERENCES ejecutivo(id_empleado),
    FOREIGN KEY (id_servicio) REFERENCES taller(id_servicio)
);

-- Orden
CREATE TABLE orden (
    id INT NOT NULL,
    id_cliente INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    fecha_orden DATETIME NOT NULL,
    estado_pago ENUM('no pago', 'pago', 'cancelado') NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id_cliente) REFERENCES cliente(id)
);

-- Detalle de orden de servicio
CREATE TABLE detalle_orden_servicio (
    id_servicio INT NOT NULL,
    id_orden INT NOT NULL,
    PRIMARY KEY (id_servicio),
    FOREIGN KEY (id_servicio) REFERENCES servicio(id),
    FOREIGN KEY (id_orden) REFERENCES orden(id)
);

-- Detalle de orden de producto
CREATE TABLE detalle_orden_producto (
    id_producto INT NOT NULL,
    id_orden INT NOT NULL,
    cantidad INT NOT NULL,
    PRIMARY KEY (id_producto, id_orden),
    FOREIGN KEY (id_producto) REFERENCES producto(id),
    FOREIGN KEY (id_orden) REFERENCES orden(id)
);