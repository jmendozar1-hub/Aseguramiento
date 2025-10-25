-- ==================================================
-- SCRIPT FINAL SIN ERRORES - BANCA EN LÍNEA
-- Proyecto UMG - Desarrollo Web
-- ¡EJECUTA ESTO ENTERO! LIMPIA Y RECREA TODO.
-- ==================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS banca_linea;
SET FOREIGN_KEY_CHECKS = 1;

CREATE DATABASE banca_linea CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE banca_linea;

-- ==================================================
-- TABLAS
-- ==================================================

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'cajero', 'cliente') NOT NULL,
    estado ENUM('activo', 'bloqueado') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cuentas_bancarias (
    id_cuenta INT AUTO_INCREMENT PRIMARY KEY,
    numero_cuenta VARCHAR(20) UNIQUE NOT NULL,
    dpi_titular VARCHAR(20) NOT NULL,
    nombre_titular VARCHAR(100) NOT NULL,
    saldo DECIMAL(10,2) DEFAULT 0.00,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_usuario_cliente INT NULL,
    FOREIGN KEY (id_usuario_cliente) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

CREATE TABLE cuentas_terceros (
    id_tercero INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario_cliente INT NOT NULL,
    id_cuenta_destino INT NOT NULL,
    alias VARCHAR(50) NOT NULL,
    monto_maximo_por_transferencia DECIMAL(10,2) NOT NULL,
    max_transferencias_diarias INT NOT NULL DEFAULT 3,
    FOREIGN KEY (id_usuario_cliente) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_cuenta_destino) REFERENCES cuentas_bancarias(id_cuenta) ON DELETE CASCADE
);

CREATE TABLE transferencias (
    id_transferencia INT AUTO_INCREMENT PRIMARY KEY,
    id_cuenta_origen INT NOT NULL,
    id_cuenta_destino INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM('completada', 'fallida') DEFAULT 'completada',
    FOREIGN KEY (id_cuenta_origen) REFERENCES cuentas_bancarias(id_cuenta) ON DELETE CASCADE,
    FOREIGN KEY (id_cuenta_destino) REFERENCES cuentas_bancarias(id_cuenta) ON DELETE CASCADE
);

CREATE TABLE transacciones (
    id_transaccion INT AUTO_INCREMENT PRIMARY KEY,
    id_cuenta INT NOT NULL,
    tipo ENUM('deposito', 'retiro') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    realizado_por INT NOT NULL,
    FOREIGN KEY (id_cuenta) REFERENCES cuentas_bancarias(id_cuenta) ON DELETE CASCADE,
    FOREIGN KEY (realizado_por) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- ==================================================
-- STORED PROCEDURES
-- ==================================================

DELIMITER $$

-- 1. Autenticar administrador
CREATE PROCEDURE autenticar_admin(
    IN p_correo VARCHAR(100),
    IN p_contrasena VARCHAR(255)
)
BEGIN
    SELECT id_usuario, nombre_completo, correo, contrasena
    FROM usuarios
    WHERE correo = p_correo
      AND rol = 'admin'
      AND estado = 'activo'
    LIMIT 1;
END$$

-- 2. Autenticar cajero
CREATE PROCEDURE autenticar_cajero(
    IN p_correo VARCHAR(100),
    IN p_contrasena VARCHAR(255)
)
BEGIN
    SELECT id_usuario, nombre_completo, correo, contrasena
    FROM usuarios
    WHERE correo = p_correo
      AND rol = 'cajero'
      AND estado = 'activo'
    LIMIT 1;
END$$

-- 3. Autenticar cliente (CORREGIDO)
CREATE PROCEDURE autenticar_cliente(
    IN p_correo VARCHAR(100),
    IN p_contrasena VARCHAR(255)
)
BEGIN
    SELECT 
        u.id_usuario, 
        u.nombre_completo, 
        u.correo,
        u.contrasena,
        cb.id_cuenta
    FROM usuarios u
    INNER JOIN cuentas_bancarias cb ON u.id_usuario = cb.id_usuario_cliente
    WHERE u.correo = p_correo
      AND u.rol = 'cliente'
      AND u.estado = 'activo'
    LIMIT 1;
END$$

-- 4. Registrar cliente (¡¡¡CORREGIDO DEFINITIVAMENTE!!!)
CREATE PROCEDURE registrar_cliente(
    IN p_correo VARCHAR(100),
    IN p_contrasena VARCHAR(255),
    IN p_dpi VARCHAR(20),
    IN p_numero_cuenta VARCHAR(20),
    OUT p_resultado VARCHAR(100)
)
BEGIN
proc_label:
BEGIN
    DECLARE v_existe_usuario INT DEFAULT 0;
    DECLARE v_existe_cuenta INT DEFAULT 0;
    DECLARE v_coincide_dpi INT DEFAULT 0;
    DECLARE v_id_cuenta INT DEFAULT NULL;
    DECLARE v_id_usuario_nuevo INT DEFAULT 0;

    -- Verificar si ya existe usuario con ese correo
    SELECT COUNT(*) INTO v_existe_usuario FROM usuarios WHERE correo = p_correo;
    IF v_existe_usuario > 0 THEN
        SET p_resultado = 'El correo ya está registrado';
        LEAVE proc_label;
    END IF;

    -- Verificar si la cuenta existe y coincide el DPI
    SELECT COUNT(*) INTO v_existe_cuenta
    FROM cuentas_bancarias
    WHERE numero_cuenta = p_numero_cuenta AND dpi_titular = p_dpi;
    IF v_existe_cuenta = 0 THEN
        SET p_resultado = 'Cuenta no existe o DPI no coincide';
        LEAVE proc_label;
    END IF;

    -- Verificar si la cuenta YA TIENE un usuario asignado
    SET v_id_cuenta = NULL;
    SELECT id_cuenta INTO v_id_cuenta
    FROM cuentas_bancarias
    WHERE numero_cuenta = p_numero_cuenta AND id_usuario_cliente IS NOT NULL;

    IF v_id_cuenta IS NOT NULL THEN
        SET p_resultado = 'Esta cuenta ya tiene un usuario registrado';
        LEAVE proc_label;
    END IF;

    -- Comenzar transacción solo cuando pasaron las validaciones previas
    START TRANSACTION;

    -- Insertar nuevo usuario cliente
    INSERT INTO usuarios (nombre_completo, correo, contrasena, rol)
    VALUES ('Cliente Nuevo', p_correo, p_contrasena, 'cliente');

    SET v_id_usuario_nuevo = LAST_INSERT_ID();

    -- Asignar la cuenta al nuevo usuario
    UPDATE cuentas_bancarias
    SET id_usuario_cliente = v_id_usuario_nuevo
    WHERE numero_cuenta = p_numero_cuenta;

    -- Verificar que se asignó correctamente
    SET v_id_cuenta = NULL;
    SELECT id_cuenta INTO v_id_cuenta
    FROM cuentas_bancarias
    WHERE numero_cuenta = p_numero_cuenta AND id_usuario_cliente = v_id_usuario_nuevo;

    IF v_id_cuenta IS NULL THEN
        ROLLBACK;
        SET p_resultado = 'Error al vincular la cuenta al usuario';
        LEAVE proc_label;
    END IF;

    COMMIT;
    SET p_resultado = 'Registro exitoso';
END;
END$$

-- 5. Agregar cajero
CREATE PROCEDURE agregar_cajero_sp(
    IN p_nombre VARCHAR(100),
    IN p_usuario VARCHAR(100),
    IN p_contrasena VARCHAR(255)
)
BEGIN
    INSERT INTO usuarios (nombre_completo, correo, contrasena, rol)
    VALUES (p_nombre, p_usuario, p_contrasena, 'cajero');
END$$

-- 6. Crear cuenta bancaria (MEJORADO)
CREATE PROCEDURE crear_cuenta_bancaria(
    IN p_nombre_titular VARCHAR(100),
    IN p_numero_cuenta VARCHAR(20),
    IN p_dpi VARCHAR(20),
    IN p_monto_inicial DECIMAL(10,2)
)
BEGIN
    DECLARE v_existe_cuenta INT DEFAULT 0;

    IF p_monto_inicial < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El monto inicial no puede ser negativo';
    END IF;

    SELECT COUNT(*) INTO v_existe_cuenta
    FROM cuentas_bancarias
    WHERE numero_cuenta = p_numero_cuenta;

    IF v_existe_cuenta > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El número de cuenta ya existe';
    END IF;

    INSERT INTO cuentas_bancarias (nombre_titular, numero_cuenta, dpi_titular, saldo)
    VALUES (p_nombre_titular, p_numero_cuenta, p_dpi, p_monto_inicial);
END$$

-- 7. Realizar depósito
CREATE PROCEDURE realizar_deposito(
    IN p_numero_cuenta VARCHAR(20),
    IN p_monto DECIMAL(10,2),
    IN p_id_cajero INT
)
BEGIN
    DECLARE v_id_cuenta INT DEFAULT 0;

    SELECT id_cuenta INTO v_id_cuenta
    FROM cuentas_bancarias
    WHERE numero_cuenta = p_numero_cuenta;

    IF v_id_cuenta IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cuenta no encontrada';
    END IF;

    UPDATE cuentas_bancarias SET saldo = saldo + p_monto WHERE id_cuenta = v_id_cuenta;

    INSERT INTO transacciones (id_cuenta, tipo, monto, fecha, hora, realizado_por)
    VALUES (v_id_cuenta, 'deposito', p_monto, CURDATE(), CURTIME(), p_id_cajero);
END$$

-- 8. Realizar retiro
CREATE PROCEDURE realizar_retiro(
    IN p_numero_cuenta VARCHAR(20),
    IN p_monto DECIMAL(10,2),
    IN p_id_cajero INT
)
BEGIN
    DECLARE v_id_cuenta INT DEFAULT 0;
    DECLARE v_saldo_actual DECIMAL(10,2) DEFAULT 0;

    SELECT id_cuenta, saldo INTO v_id_cuenta, v_saldo_actual
    FROM cuentas_bancarias
    WHERE numero_cuenta = p_numero_cuenta;

    IF v_id_cuenta IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cuenta no encontrada';
    END IF;

    IF v_saldo_actual < p_monto THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Saldo insuficiente';
    END IF;

    UPDATE cuentas_bancarias SET saldo = saldo - p_monto WHERE id_cuenta = v_id_cuenta;

    INSERT INTO transacciones (id_cuenta, tipo, monto, fecha, hora, realizado_por)
    VALUES (v_id_cuenta, 'retiro', p_monto, CURDATE(), CURTIME(), p_id_cajero);
END$$

-- 9. Agregar cuenta de tercero
CREATE PROCEDURE agregar_cuenta_tercero(
    IN p_id_usuario INT,
    IN p_numero_cuenta_destino VARCHAR(20),
    IN p_alias VARCHAR(50),
    IN p_monto_maximo DECIMAL(10,2),
    IN p_max_diarias INT
)
BEGIN
    DECLARE v_id_cuenta_destino INT DEFAULT 0;

    SELECT id_cuenta INTO v_id_cuenta_destino
    FROM cuentas_bancarias
    WHERE numero_cuenta = p_numero_cuenta_destino;

    IF v_id_cuenta_destino IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cuenta destino no existe';
    END IF;

    INSERT INTO cuentas_terceros (id_usuario_cliente, id_cuenta_destino, alias, monto_maximo_por_transferencia, max_transferencias_diarias)
    VALUES (p_id_usuario, v_id_cuenta_destino, p_alias, p_monto_maximo, p_max_diarias);
END$$

-- 10. Realizar transferencia
CREATE PROCEDURE realizar_transferencia(
    IN p_id_usuario INT,
    IN p_id_cuenta_destino INT,
    IN p_monto DECIMAL(10,2),
    OUT p_resultado VARCHAR(100)
)
BEGIN
proc_label:
BEGIN
    DECLARE v_id_cuenta_origen INT DEFAULT 0;
    DECLARE v_saldo_origen DECIMAL(10,2) DEFAULT 0;
    DECLARE v_monto_maximo DECIMAL(10,2) DEFAULT 0;
    DECLARE v_transacciones_hoy INT DEFAULT 0;
    DECLARE v_max_diarias INT DEFAULT 0;

    SELECT id_cuenta INTO v_id_cuenta_origen
    FROM cuentas_bancarias
    WHERE id_usuario_cliente = p_id_usuario;

    IF v_id_cuenta_origen IS NULL THEN
        SET p_resultado = 'No tienes cuenta asignada';
        LEAVE proc_label;
    END IF;

    SELECT saldo INTO v_saldo_origen
    FROM cuentas_bancarias
    WHERE id_cuenta = v_id_cuenta_origen;

    IF v_saldo_origen < p_monto THEN
        SET p_resultado = 'Saldo insuficiente';
        LEAVE proc_label;
    END IF;

    SELECT monto_maximo_por_transferencia, max_transferencias_diarias
    INTO v_monto_maximo, v_max_diarias
    FROM cuentas_terceros
    WHERE id_usuario_cliente = p_id_usuario AND id_cuenta_destino = p_id_cuenta_destino;

    IF v_monto_maximo IS NULL THEN
        SET p_resultado = 'Cuenta de tercero no autorizada';
        LEAVE proc_label;
    END IF;

    IF p_monto > v_monto_maximo THEN
        SET p_resultado = 'Monto excede límite permitido';
        LEAVE proc_label;
    END IF;

    SELECT COUNT(*) INTO v_transacciones_hoy
    FROM transferencias
    WHERE id_cuenta_origen = v_id_cuenta_origen
      AND id_cuenta_destino = p_id_cuenta_destino
      AND fecha = CURDATE()
      AND estado = 'completada';

    IF v_transacciones_hoy >= v_max_diarias THEN
        SET p_resultado = 'Límite diario de transferencias alcanzado';
        LEAVE proc_label;
    END IF;

    START TRANSACTION;

    UPDATE cuentas_bancarias SET saldo = saldo - p_monto WHERE id_cuenta = v_id_cuenta_origen;
    UPDATE cuentas_bancarias SET saldo = saldo + p_monto WHERE id_cuenta = p_id_cuenta_destino;

    INSERT INTO transferencias (id_cuenta_origen, id_cuenta_destino, monto, fecha, hora, estado)
    VALUES (v_id_cuenta_origen, p_id_cuenta_destino, p_monto, CURDATE(), CURTIME(), 'completada');

    COMMIT;
    SET p_resultado = 'Transferencia exitosa';
END;
END$$

-- 11. Obtener estado de cuenta
CREATE PROCEDURE obtener_estado_cuenta(
    IN p_id_usuario INT
)
BEGIN
    DECLARE v_id_cuenta INT DEFAULT 0;

    SELECT id_cuenta INTO v_id_cuenta
    FROM cuentas_bancarias
    WHERE id_usuario_cliente = p_id_usuario;

    SELECT 'Transferencia Enviada' AS tipo, monto, fecha, hora, 'debito' AS signo
    FROM transferencias
    WHERE id_cuenta_origen = v_id_cuenta
    UNION ALL
    SELECT 'Transferencia Recibida' AS tipo, monto, fecha, hora, 'credito' AS signo
    FROM transferencias
    WHERE id_cuenta_destino = v_id_cuenta
    UNION ALL
    SELECT CONCAT('Transacción - ', tipo) AS tipo, monto, fecha, hora,
           CASE WHEN tipo = 'deposito' THEN 'credito' ELSE 'debito' END AS signo
    FROM transacciones
    WHERE id_cuenta = v_id_cuenta
    ORDER BY fecha DESC, hora DESC;
END$$

DELIMITER ;

-- ==================================================
-- DATOS DE PRUEBA LIMPIOS (¡¡¡FUNCIONAN!!!)
-- ==================================================

-- Usuarios base (admin y cajero)
INSERT INTO usuarios (nombre_completo, correo, contrasena, rol, estado)
VALUES 
('Admin Principal', 'admin@banco.com', 'admin123', 'admin', 'activo'),
('Cajero Uno', 'cajero1@banco.com', 'cajero123', 'cajero', 'activo');

-- Usuario cliente de demostración (para pruebas rápidas)
INSERT INTO usuarios (nombre_completo, correo, contrasena, rol, estado)
VALUES ('Cliente Demo', 'cliente.demo@banco.com', 'demo123', 'cliente', 'activo');

-- Cuenta de prueba (¡¡¡SIN USUARIO ASIGNADO!!!)
INSERT INTO cuentas_bancarias (nombre_titular, numero_cuenta, dpi_titular, saldo)
VALUES ('Cliente Prueba', '1001001001', '1234567890101', 1000.00);

-- Cuenta adicional de demostración (ASIGNADA a Cliente Demo)
INSERT INTO cuentas_bancarias (nombre_titular, numero_cuenta, dpi_titular, saldo, id_usuario_cliente)
VALUES ('Cliente Demo', '1001001002', '1098765432101', 500.00,
        (SELECT id_usuario FROM usuarios WHERE correo = 'cliente.demo@banco.com'));

-- ==================================================
-- ✅ ¡¡¡BASE DE DATOS LISTA PARA USAR SIN ERRORES!!!
-- ==================================================
