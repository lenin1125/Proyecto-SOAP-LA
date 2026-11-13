CREATE DATABASE IF NOT EXISTS hotel_db;
USE hotel_db;

DROP TABLE IF EXISTS reserva;
DROP TABLE IF EXISTS habitacion;
DROP TABLE IF EXISTS huesped;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE habitacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(10) UNIQUE NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    estado VARCHAR(20) DEFAULT 'DISPONIBLE'
);

CREATE TABLE huesped (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    documento VARCHAR(20) DEFAULT '123456789'
);

CREATE TABLE reserva (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habitacion_id INT NOT NULL,
    huesped_id INT NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    estado VARCHAR(20) DEFAULT 'CONFIRMADA',
    FOREIGN KEY (habitacion_id) REFERENCES habitacion(id) ON DELETE CASCADE
);

-- Usuarios que el servidor SOAP valida con WS-Security.
-- El rol decide que operaciones puede ejecutar cada usuario.
-- token_expira_en guarda en UTC el vencimiento del token; debe coincidir
-- con SANCTUM_EXPIRATION de la API (480 minutos por defecto).
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    api_token VARCHAR(80) DEFAULT NULL UNIQUE,
    role VARCHAR(30) DEFAULT NULL,
    token_expira_en DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Registro base necesario para la ejecución de pruebas
INSERT INTO huesped (id, nombre, email, documento) 
VALUES (1, 'Huésped de Prueba', 'prueba@hotel.com', '123456789');