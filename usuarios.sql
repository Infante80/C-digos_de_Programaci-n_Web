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

-- Intentos fallidos de inicio de sesión (bloqueo de 45 minutos)
CREATE TABLE IF NOT EXISTS intentos_login (
    usuario          VARCHAR(30)  NOT NULL,
    ip               VARCHAR(45)  NOT NULL,
    fallos           INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_fallo     DATETIME     NULL,
    bloqueado_hasta  DATETIME     NULL,
    PRIMARY KEY (usuario, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Para desbloquear a alguien antes de tiempo:
-- DELETE FROM intentos_login WHERE usuario = 'nombre_de_usuario';
