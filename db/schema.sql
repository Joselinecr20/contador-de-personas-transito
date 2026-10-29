-- Esquema de base de datos: traffic-people-counter
-- Una unica tabla que lleva el conteo de vehiculos y personas detectados.

CREATE DATABASE IF NOT EXISTS traffic_people_counter
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE traffic_people_counter;

CREATE TABLE IF NOT EXISTS conteo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('vehiculo', 'persona') NOT NULL,
  clase VARCHAR(30) NOT NULL,
  fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tipo_fecha (tipo, fecha_hora)
);

-- Usuarios que pueden entrar al panel.
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
