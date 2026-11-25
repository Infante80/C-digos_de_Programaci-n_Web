-- Tabla de usuarios para el login de Herrería Hernández
-- Ejecutar una sola vez en la misma base de datos que "cotizaciones".

CREATE TABLE IF NOT EXISTS usuarios (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario        VARCHAR(30)  NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    rol            ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    creado_en      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Para volver administrador a un usuario ya registrado:
-- UPDATE usuarios SET rol = 'admin' WHERE usuario = 'nombre_de_usuario';
