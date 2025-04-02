START TRANSACTION;

-- Datos mínimos para funcionamiento básico
INSERT INTO `ofertas` (`id`, `objeto`, `estado`) VALUES
(1, 'Oferta de ejemplo', 'ACTIVO');

INSERT INTO `documentos` (`id`, `oferta_id`, `titulo`, `archivo`) VALUES
(1, 1, 'Documento ejemplo', 'doc_ejemplo.pdf');

INSERT INTO `actividades` (`id`, `codigo`, `nombre`) VALUES
(1, '10101501', 'Ejemplo actividad');

COMMIT;