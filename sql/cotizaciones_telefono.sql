-- Agrega el teléfono a las cotizaciones. No borra datos:
-- las solicitudes anteriores quedan con el teléfono vacío (NULL).
ALTER TABLE cotizaciones
    ADD COLUMN IF NOT EXISTS telefono VARCHAR(10) NULL AFTER correo;
