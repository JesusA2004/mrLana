-- MR-Lana ERP schema (SQL) - versión sin gastos + con recurrencias + cambios solicitados
-- Motor: MySQL / MariaDB (InnoDB) | Charset: utf8mb4

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `system_logs`;
DROP TABLE IF EXISTS `ajustes`;
DROP TABLE IF EXISTS `folios`;
DROP TABLE IF EXISTS `comprobantes`;
DROP TABLE IF EXISTS `detalles`;
DROP TABLE IF EXISTS `requisicions`;
DROP TABLE IF EXISTS `requisicion_recurrencias`;
DROP TABLE IF EXISTS `proveedors`;
DROP TABLE IF EXISTS `conceptos`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `empleados`;
DROP TABLE IF EXISTS `areas`;
DROP TABLE IF EXISTS `sucursals`;
DROP TABLE IF EXISTS `corporativos`;

SET FOREIGN_KEY_CHECKS=1;

-- =========================
-- CORE ORGANIZACIONAL
-- =========================

CREATE TABLE `corporativos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `rfc` VARCHAR(20) NULL,
  `direccion` VARCHAR(255) NULL,
  `telefono` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `codigo` VARCHAR(20) NULL,
  `logo_path` VARCHAR(255) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sucursals` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `corporativo_id` BIGINT UNSIGNED NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `codigo` VARCHAR(20) NULL,
  `ciudad` VARCHAR(120) NULL,
  `estado` VARCHAR(120) NULL,
  `direccion` VARCHAR(255) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `sucursals_corporativo_id_index` (`corporativo_id`),
  CONSTRAINT `sucursals_corporativo_id_foreign`
    FOREIGN KEY (`corporativo_id`) REFERENCES `corporativos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `areas` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `corporativo_id` BIGINT UNSIGNED NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `areas_corporativo_id_index` (`corporativo_id`),
  CONSTRAINT `areas_corporativo_id_foreign`
    FOREIGN KEY (`corporativo_id`) REFERENCES `corporativos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `empleados` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `sucursal_id` BIGINT UNSIGNED NOT NULL,
  `area_id` BIGINT UNSIGNED NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `apellido_paterno` VARCHAR(120) NOT NULL,
  `apellido_materno` VARCHAR(120) NULL,
  `email` VARCHAR(150) NULL,
  `telefono` VARCHAR(30) NULL,
  `puesto` VARCHAR(120) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `empleados_sucursal_id_index` (`sucursal_id`),
  KEY `empleados_area_id_index` (`area_id`),
  CONSTRAINT `empleados_sucursal_id_foreign`
    FOREIGN KEY (`sucursal_id`) REFERENCES `sucursals`(`id`),
  CONSTRAINT `empleados_area_id_foreign`
    FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `empleado_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('ADMIN','CONTADOR','COLABORADOR') NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_empleado_id_index` (`empleado_id`),
  CONSTRAINT `users_empleado_id_foreign`
    FOREIGN KEY (`empleado_id`) REFERENCES `empleados`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CATALOGOS
-- =========================

CREATE TABLE `conceptos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proveedors` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `user_duenio_id` BIGINT UNSIGNED NOT NULL,
  `nombre_comercial` VARCHAR(200) NOT NULL,
  `razon_social` VARCHAR(200) NULL,
  `rfc` VARCHAR(20) NULL,
  `direccion` VARCHAR(255) NULL,
  `contacto` VARCHAR(150) NULL,
  `telefono` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `beneficiario` VARCHAR(200) NULL,
  `banco` VARCHAR(120) NULL,
  `cuenta` VARCHAR(50) NULL,
  `clabe` VARCHAR(30) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proveedors_user_nombre_unique` (`user_duenio_id`,`nombre_comercial`),
  KEY `proveedors_user_duenio_id_index` (`user_duenio_id`),
  CONSTRAINT `proveedors_user_duenio_id_foreign`
    FOREIGN KEY (`user_duenio_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- RECURRENCIA (PAGOS RECURRENTES)
-- =========================

CREATE TABLE `requisicion_recurrencias` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,

  `status` ENUM('PENDIENTE_APROBACION','APROBADA','RECHAZADA') NOT NULL DEFAULT 'PENDIENTE_APROBACION',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,

  `frecuencia` ENUM('SEMANAL','QUINCENAL','MENSUAL','BIMESTRAL','TRIMESTRAL','ANUAL') NOT NULL,
  `intervalo` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `dia_semana` TINYINT UNSIGNED NULL,
  `dia_mes` TINYINT UNSIGNED NULL,
  `hora_ejecucion` TIME NULL,

  `proxima_ejecucion` DATETIME NULL,
  `ultima_generacion` DATETIME NULL,

  `tipo` ENUM('ANTICIPO','REEMBOLSO') NOT NULL,

  `solicitante_id` BIGINT UNSIGNED NOT NULL,
  `sucursal_id` BIGINT UNSIGNED NOT NULL,
  `comprador_corp_id` BIGINT UNSIGNED NOT NULL,

  `proveedor_id` BIGINT UNSIGNED NULL,
  `concepto_id` BIGINT UNSIGNED NOT NULL,

  `monto_subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `monto_total` DECIMAL(15,2) NOT NULL DEFAULT 0,

  `observaciones` TEXT NULL,

  `creada_por_user_id` BIGINT UNSIGNED NOT NULL,
  `aprobada_por_user_id` BIGINT UNSIGNED NULL,
  `fecha_aprobacion` DATETIME NULL,

  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  PRIMARY KEY (`id`),

  KEY `requisicion_recurrencias_solicitante_id_index` (`solicitante_id`),
  KEY `requisicion_recurrencias_sucursal_id_index` (`sucursal_id`),
  KEY `requisicion_recurrencias_comprador_corp_id_index` (`comprador_corp_id`),
  KEY `requisicion_recurrencias_proveedor_id_index` (`proveedor_id`),
  KEY `requisicion_recurrencias_concepto_id_index` (`concepto_id`),
  KEY `requisicion_recurrencias_creada_por_user_id_index` (`creada_por_user_id`),
  KEY `requisicion_recurrencias_aprobada_por_user_id_index` (`aprobada_por_user_id`),

  KEY `recurrencias_run_idx` (`activo`,`status`,`proxima_ejecucion`),

  CONSTRAINT `requisicion_recurrencias_solicitante_id_foreign`
    FOREIGN KEY (`solicitante_id`) REFERENCES `empleados`(`id`),
  CONSTRAINT `requisicion_recurrencias_sucursal_id_foreign`
    FOREIGN KEY (`sucursal_id`) REFERENCES `sucursals`(`id`),
  CONSTRAINT `requisicion_recurrencias_comprador_corp_id_foreign`
    FOREIGN KEY (`comprador_corp_id`) REFERENCES `corporativos`(`id`),
  CONSTRAINT `requisicion_recurrencias_proveedor_id_foreign`
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedors`(`id`),
  CONSTRAINT `requisicion_recurrencias_concepto_id_foreign`
    FOREIGN KEY (`concepto_id`) REFERENCES `conceptos`(`id`),
  CONSTRAINT `requisicion_recurrencias_creada_por_user_id_foreign`
    FOREIGN KEY (`creada_por_user_id`) REFERENCES `users`(`id`),
  CONSTRAINT `requisicion_recurrencias_aprobada_por_user_id_foreign`
    FOREIGN KEY (`aprobada_por_user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- OPERACION: REQUISICIONES
-- =========================

CREATE TABLE `requisicions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `folio` VARCHAR(50) NOT NULL,

  `tipo` ENUM('ANTICIPO','REEMBOLSO') NOT NULL,

  `status` ENUM(
    'BORRADOR',
    'CAPTURADA',
    'PAGADA',
    'POR_COMPROBAR',
    'COMPROBADA',
    'ACEPTADA',
    'RECHAZADA'
  ) NOT NULL DEFAULT 'BORRADOR',

  `recurrencia_id` BIGINT UNSIGNED NULL,

  `solicitante_id` BIGINT UNSIGNED NOT NULL,
  `sucursal_id` BIGINT UNSIGNED NOT NULL,
  `comprador_corp_id` BIGINT UNSIGNED NOT NULL,

  `proveedor_id` BIGINT UNSIGNED NULL,
  `concepto_id` BIGINT UNSIGNED NOT NULL,

  `monto_subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `monto_total` DECIMAL(15,2) NOT NULL DEFAULT 0,

  `fecha_captura` DATETIME NOT NULL,
  `fecha_pago` DATE NULL,

  `observaciones` TEXT NULL,

  `creada_por_user_id` BIGINT UNSIGNED NOT NULL,

  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `requisicions_folio_unique` (`folio`),

  KEY `requisicions_recurrencia_id_index` (`recurrencia_id`),
  KEY `requisicions_solicitante_id_index` (`solicitante_id`),
  KEY `requisicions_sucursal_id_index` (`sucursal_id`),
  KEY `requisicions_comprador_corp_id_index` (`comprador_corp_id`),
  KEY `requisicions_proveedor_id_index` (`proveedor_id`),
  KEY `requisicions_concepto_id_index` (`concepto_id`),
  KEY `requisicions_creada_por_user_id_index` (`creada_por_user_id`),

  KEY `requis_status_sucursal_fecha_idx` (`status`,`sucursal_id`,`fecha_captura`),
  KEY `requis_proveedor_pago_idx` (`proveedor_id`,`fecha_pago`),

  CONSTRAINT `requisicions_recurrencia_id_foreign`
    FOREIGN KEY (`recurrencia_id`) REFERENCES `requisicion_recurrencias`(`id`),

  CONSTRAINT `requisicions_solicitante_id_foreign`
    FOREIGN KEY (`solicitante_id`) REFERENCES `empleados`(`id`),
  CONSTRAINT `requisicions_sucursal_id_foreign`
    FOREIGN KEY (`sucursal_id`) REFERENCES `sucursals`(`id`),
  CONSTRAINT `requisicions_comprador_corp_id_foreign`
    FOREIGN KEY (`comprador_corp_id`) REFERENCES `corporativos`(`id`),

  CONSTRAINT `requisicions_proveedor_id_foreign`
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedors`(`id`),
  CONSTRAINT `requisicions_concepto_id_foreign`
    FOREIGN KEY (`concepto_id`) REFERENCES `conceptos`(`id`),

  CONSTRAINT `requisicions_creada_por_user_id_foreign`
    FOREIGN KEY (`creada_por_user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `requisicion_id` BIGINT UNSIGNED NOT NULL,
  `sucursal_id` BIGINT UNSIGNED NULL,

  `cantidad` DECIMAL(12,2) NOT NULL DEFAULT 1,
  `descripcion` VARCHAR(255) NOT NULL,
  `precio_unitario` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0,

  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  PRIMARY KEY (`id`),

  KEY `detalles_requisicion_id_index` (`requisicion_id`),
  KEY `detalles_sucursal_id_index` (`sucursal_id`),

  CONSTRAINT `detalles_requisicion_id_foreign`
    FOREIGN KEY (`requisicion_id`) REFERENCES `requisicions`(`id`),
  CONSTRAINT `detalles_sucursal_id_foreign`
    FOREIGN KEY (`sucursal_id`) REFERENCES `sucursals`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comprobantes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `requisicion_id` BIGINT UNSIGNED NOT NULL,
  `proveedor_id` BIGINT UNSIGNED NULL,

  `tipo_doc` ENUM('FACTURA','TICKET','NOTA','OTRO') NOT NULL DEFAULT 'FACTURA',

  `uuid_cfdi` VARCHAR(50) NULL,
  `folio` VARCHAR(50) NULL,

  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0,

  `estado` ENUM('CARGADO','EN_REVISION','OBSERVADO') NOT NULL DEFAULT 'CARGADO',

  `fecha_emision` DATE NULL,
  `fecha_carga` DATETIME NOT NULL,

  `user_carga_id` BIGINT UNSIGNED NOT NULL,

  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  PRIMARY KEY (`id`),

  KEY `comprobantes_requisicion_id_index` (`requisicion_id`),
  KEY `comprobantes_proveedor_id_index` (`proveedor_id`),
  KEY `comprobantes_user_carga_id_index` (`user_carga_id`),

  KEY `comprobantes_requis_estado_idx` (`requisicion_id`,`estado`),

  CONSTRAINT `comprobantes_requisicion_id_foreign`
    FOREIGN KEY (`requisicion_id`) REFERENCES `requisicions`(`id`),
  CONSTRAINT `comprobantes_proveedor_id_foreign`
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedors`(`id`),
  CONSTRAINT `comprobantes_user_carga_id_foreign`
    FOREIGN KEY (`user_carga_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- FOLIOS (SIMPLIFICADA)
-- =========================

CREATE TABLE `folios` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `folio` VARCHAR(100) NOT NULL,
  `monto_total` DECIMAL(15,2) NULL,
  `user_registro_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folios_folio_unique` (`folio`),
  KEY `folios_user_registro_id_index` (`user_registro_id`),
  CONSTRAINT `folios_user_registro_id_foreign`
    FOREIGN KEY (`user_registro_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- AJUSTES (MEJORADA)
-- =========================

CREATE TABLE `ajustes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `requisicion_id` BIGINT UNSIGNED NOT NULL,

  `tipo` ENUM('DEVOLUCION','FALTANTE','INCREMENTO_AUTORIZADO') NOT NULL,
  `sentido` ENUM('A_FAVOR_EMPRESA','A_FAVOR_SOLICITANTE') NOT NULL,

  `monto` DECIMAL(15,2) NOT NULL,

  `monto_anterior` DECIMAL(15,2) NULL,
  `monto_nuevo` DECIMAL(15,2) NULL,

  `estatus` ENUM('PENDIENTE','APROBADO','RECHAZADO','APLICADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',

  `metodo` ENUM('TRANSFERENCIA','EFECTIVO','DESCUENTO_NOMINA','OTRO') NULL,
  `referencia` VARCHAR(120) NULL,
  `motivo` VARCHAR(255) NULL,

  `fecha_registro` DATETIME NOT NULL,
  `fecha_resolucion` DATETIME NULL,

  `user_registro_id` BIGINT UNSIGNED NOT NULL,
  `user_resuelve_id` BIGINT UNSIGNED NULL,

  `notas` VARCHAR(255) NULL,

  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  PRIMARY KEY (`id`),

  KEY `ajustes_requisicion_id_index` (`requisicion_id`),
  KEY `ajustes_user_registro_id_index` (`user_registro_id`),
  KEY `ajustes_user_resuelve_id_index` (`user_resuelve_id`),

  KEY `ajustes_requis_tipo_estatus_idx` (`requisicion_id`,`tipo`,`estatus`),

  CONSTRAINT `ajustes_requisicion_id_foreign`
    FOREIGN KEY (`requisicion_id`) REFERENCES `requisicions`(`id`),
  CONSTRAINT `ajustes_user_registro_id_foreign`
    FOREIGN KEY (`user_registro_id`) REFERENCES `users`(`id`),
  CONSTRAINT `ajustes_user_resuelve_id_foreign`
    FOREIGN KEY (`user_resuelve_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- AUDITORIA
-- =========================

CREATE TABLE `system_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `accion` VARCHAR(50) NOT NULL,
  `tabla` VARCHAR(120) NOT NULL,
  `registro_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `descripcion` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),

  KEY `system_logs_user_id_index` (`user_id`),
  KEY `logs_tabla_registro_idx` (`tabla`,`registro_id`),

  CONSTRAINT `system_logs_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
