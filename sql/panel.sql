-- Tablas del panel de administración. No borra datos y se puede ejecutar más de una vez.

-- Cotizaciones: fecha de recepción y marca de "ya atendida".
-- La fecha se agrega primero sin valor para que las solicitudes anteriores no queden con la de hoy.
ALTER TABLE cotizaciones
    ADD COLUMN IF NOT EXISTS recibida_en DATETIME NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS atendida    TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE cotizaciones
    MODIFY recibida_en DATETIME NULL DEFAULT CURRENT_TIMESTAMP;

-- Trabajos aceptados.
-- entregado_en vacío = pendiente de entrega. Con fecha y garantia_hasta = aparece en garantías.
CREATE TABLE IF NOT EXISTS trabajos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente         VARCHAR(80)  NOT NULL,
    trabajo         VARCHAR(150) NOT NULL,
    fecha_entrega   DATE         NOT NULL,
    entregado_en    DATE         NULL,
    garantia_hasta  DATE         NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS materiales (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(100)  NOT NULL UNIQUE,
    cantidad  DECIMAL(10,2) NOT NULL DEFAULT 0,
    minimo    DECIMAL(10,2) NOT NULL DEFAULT 0,
    unidad    VARCHAR(20)   NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- El panel solo necesita leer
GRANT SELECT ON cotizaciones TO 'herreria_app'@'localhost';
GRANT SELECT ON trabajos     TO 'herreria_app'@'localhost';
GRANT SELECT ON materiales   TO 'herreria_app'@'localhost';
