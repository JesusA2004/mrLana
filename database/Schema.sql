-- MR-Lana ERP schema (generated from migrations)
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `system_logs`;
DROP TABLE IF EXISTS `gastos`;
DROP TABLE IF EXISTS `ingresos`;
DROP TABLE IF EXISTS `ajustes`;
DROP TABLE IF EXISTS `folios`;
DROP TABLE IF EXISTS `comprobantes`;
DROP TABLE IF EXISTS `detalles`;
DROP TABLE IF EXISTS `requisicions`;
DROP TABLE IF EXISTS `pagos`;
DROP TABLE IF EXISTS `contratos`;
DROP TABLE IF EXISTS `inversionistas`;
DROP TABLE IF EXISTS `proveedors`;
DROP TABLE IF EXISTS `conceptos`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `empleados`;
DROP TABLE IF EXISTS `areas`;
DROP TABLE IF EXISTS `sucursals`;
DROP TABLE IF EXISTS `corporativos`;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE `corporativos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `rfc` VARCHAR(20) NULL,
  `direccion` VARCHAR(255) NULL,
  `telefono` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `codigo` VARCHAR(20) NULL,
  `logo_path` VARCHAR(255) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT true,
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
  `activo` TINYINT(1) NOT NULL DEFAULT true,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `sucursals_corporativo_id_foreign` FOREIGN KEY (`corporativo_id`) REFERENCES `corporativos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `areas` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `corporativo_id` BIGINT UNSIGNED NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT true,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `areas_corporativo_id_foreign` FOREIGN KEY (`corporativo_id`) REFERENCES `corporativos`(`id`)
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
  `activo` TINYINT(1) NOT NULL DEFAULT true,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `empleados_sucursal_id_foreign` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursals`(`id`),
  CONSTRAINT `empleados_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `empleado_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('ADMIN','CONTADOR','COLABORADOR') NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT true,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  CONSTRAINT `users_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleados`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `conceptos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `grupo` VARCHAR(150) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT true,
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
  CONSTRAINT `proveedors_user_duenio_id_foreign` FOREIGN KEY (`user_duenio_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inversionistas` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `nombre` VARCHAR(200) NOT NULL,
  `rfc` VARCHAR(20) NULL,
  `direccion` VARCHAR(255) NULL,
  `telefono` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contratos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `inversionista_id` BIGINT UNSIGNED NOT NULL,
  `corporativo_id` BIGINT UNSIGNED NULL,
  `no_contrato` VARCHAR(50) NOT NULL,
  `fecha_contrato` DATE NOT NULL,
  `capital_inicial` DECIMAL(15,2) NOT NULL,
  `fecha_reembolso` DATE NOT NULL,
  `plazo_meses` TEXT NOT NULL,
  `tasa_anual` DECIMAL(8,5) NOT NULL,
  `tasa_mensual` DECIMAL(8,5) NOT NULL,
  `banco` VARCHAR(120) NOT NULL,
  `clabe` VARCHAR(30) NOT NULL,
  `cuenta` VARCHAR(30) NOT NULL,
  `rendimiento_bruto_mensual` DECIMAL(15,2) NOT NULL,
  `retencion_mensual` DECIMAL(15,2) NOT NULL,
  `rendimiento_neto_mensual` DECIMAL(15,2) NOT NULL,
  `periodicidad_pago` ENUM('mensual','quincenal','semanal','unico') NOT NULL DEFAULT 'mensual',
  `dia_pago` TEXT NOT NULL,
  `status` ENUM('CAPTURADA','ACTIVA','VENCIDA','CANCELADA') NOT NULL DEFAULT 'CAPTURADA',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contratos_no_contrato_unique` (`no_contrato`),
  CONSTRAINT `contratos_inversionista_id_foreign` FOREIGN KEY (`inversionista_id`) REFERENCES `inversionistas`(`id`),
  CONSTRAINT `contratos_corporativo_id_foreign` FOREIGN KEY (`corporativo_id`) REFERENCES `corporativos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pagos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `contrato_id` BIGINT UNSIGNED NOT NULL,
  `fecha_pago` DATE NOT NULL,
  `rendimiento_bruto` DECIMAL(15,2) NOT NULL,
  `retenciones` DECIMAL(15,2) NOT NULL,
  `rendimiento_neto` DECIMAL(15,2) NOT NULL,
  `status` ENUM('PENDIENTE','PAGADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
  `recibo_pago_ruta` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `pagos_contrato_id_foreign` FOREIGN KEY (`contrato_id`) REFERENCES `contratos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `requisicions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `folio` VARCHAR(50) NOT NULL,
  `tipo` ENUM('ANTICIPO','REEMBOLSO') NOT NULL,
  `status` ENUM('BORRADOR','CAPTURADA','COMPROBADA','PAGADA','CANCELADA') NOT NULL DEFAULT 'BORRADOR',
  `comprador_corp_id` BIGINT UNSIGNED NOT NULL,
  `sucursal_id` BIGINT UNSIGNED NOT NULL,
  `solicitante_id` BIGINT UNSIGNED NOT NULL,
  `proveedor_id` BIGINT UNSIGNED NULL,
  `concepto_id` BIGINT UNSIGNED NOT NULL,
  `monto_subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `monto_iva` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `monto_total` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `lugar_entrega_texto` VARCHAR(200) NULL,
  `fecha_entrega` DATE NULL,
  `fecha_captura` DATETIME NOT NULL,
  `fecha_pago` DATE NULL,
  `beneficiario_pago` VARCHAR(200) NULL,
  `banco_pago` VARCHAR(120) NULL,
  `clabe_pago` VARCHAR(30) NULL,
  `cuenta_pago` VARCHAR(30) NULL,
  `observaciones` TEXT NULL,
  `creada_por_user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `requisicions_folio_unique` (`folio`),
  CONSTRAINT `requisicions_comprador_corp_id_foreign` FOREIGN KEY (`comprador_corp_id`) REFERENCES `corporativos`(`id`),
  CONSTRAINT `requisicions_sucursal_id_foreign` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursals`(`id`),
  CONSTRAINT `requisicions_solicitante_id_foreign` FOREIGN KEY (`solicitante_id`) REFERENCES `empleados`(`id`),
  CONSTRAINT `requisicions_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedors`(`id`),
  CONSTRAINT `requisicions_concepto_id_foreign` FOREIGN KEY (`concepto_id`) REFERENCES `conceptos`(`id`),
  CONSTRAINT `requisicions_creada_por_user_id_foreign` FOREIGN KEY (`creada_por_user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `requisicion_id` BIGINT UNSIGNED NOT NULL,
  `sucursal_id` BIGINT UNSIGNED NULL,
  `cantidad` DECIMAL(12,2) NOT NULL DEFAULT 1,
  `descripcion` VARCHAR(255) NOT NULL,
  `precio_unitario` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `iva` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `detalles_requisicion_id_foreign` FOREIGN KEY (`requisicion_id`) REFERENCES `requisicions`(`id`),
  CONSTRAINT `detalles_sucursal_id_foreign` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursals`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comprobantes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `requisicion_id` BIGINT UNSIGNED NOT NULL,
  `proveedor_id` BIGINT UNSIGNED NULL,
  `tipo_doc` ENUM('FACTURA','TICKET','NOTA','OTRO') NOT NULL DEFAULT 'FACTURA',
  `uuid_cfdi` VARCHAR(50) NULL,
  `folio` VARCHAR(50) NULL,
  `rfc_emisor` VARCHAR(20) NULL,
  `rfc_receptor` VARCHAR(20) NULL,
  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `iva` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `estatus` ENUM('CARGADO','EN_REVISION','VALIDADO','RECHAZADO') NOT NULL DEFAULT 'CARGADO',
  `fecha_emision` DATE NULL,
  `fecha_carga` DATETIME NOT NULL,
  `user_carga_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `comprobantes_requisicion_id_foreign` FOREIGN KEY (`requisicion_id`) REFERENCES `requisicions`(`id`),
  CONSTRAINT `comprobantes_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedors`(`id`),
  CONSTRAINT `comprobantes_user_carga_id_foreign` FOREIGN KEY (`user_carga_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `folios` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `rfc_emisor` VARCHAR(20) NULL,
  `rfc_receptor` VARCHAR(20) NULL,
  `monto_total` DECIMAL(15,2) NULL,
  `origen` ENUM('SISTEMA','MANUAL') NOT NULL DEFAULT 'MANUAL',
  `user_registro_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `folio` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `folios_user_registro_id_foreign` FOREIGN KEY (`user_registro_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ajustes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `requisicion_id` BIGINT UNSIGNED NOT NULL,
  `tipo` ENUM('DEVOLUCION','FALTANTE') NOT NULL,
  `monto` DECIMAL(15,2) NOT NULL,
  `estatus` ENUM('PENDIENTE','APLICADO') NOT NULL DEFAULT 'PENDIENTE',
  `fecha_registro` DATETIME NOT NULL,
  `user_registro_id` BIGINT UNSIGNED NOT NULL,
  `notas` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `ajustes_requisicion_id_foreign` FOREIGN KEY (`requisicion_id`) REFERENCES `requisicions`(`id`),
  CONSTRAINT `ajustes_user_registro_id_foreign` FOREIGN KEY (`user_registro_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ingresos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `fecha_ingreso` DATE NOT NULL,
  `corporativo_id` BIGINT UNSIGNED NULL,
  `sucursal_id` BIGINT UNSIGNED NULL,
  `inversionista_id` BIGINT UNSIGNED NULL,
  `monto` DECIMAL(15,2) NOT NULL,
  `moneda` VARCHAR(10) NOT NULL DEFAULT 'MXN',
  `origen` ENUM('VENTA','APORTE_INVERSIONISTA','OTRO') NOT NULL DEFAULT 'OTRO',
  `descripcion` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `ingresos_corporativo_id_foreign` FOREIGN KEY (`corporativo_id`) REFERENCES `corporativos`(`id`),
  CONSTRAINT `ingresos_sucursal_id_foreign` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursals`(`id`),
  CONSTRAINT `ingresos_inversionista_id_foreign` FOREIGN KEY (`inversionista_id`) REFERENCES `inversionistas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `gastos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `fecha_gasto` DATE NOT NULL,
  `corporativo_id` BIGINT UNSIGNED NULL,
  `sucursal_id` BIGINT UNSIGNED NULL,
  `empleado_id` BIGINT UNSIGNED NULL,
  `proveedor_id` BIGINT UNSIGNED NULL,
  `monto` DECIMAL(15,2) NOT NULL,
  `moneda` VARCHAR(10) NOT NULL DEFAULT 'MXN',
  `tipo_gasto` ENUM('OPERATIVO','RUTA','VIAJE','CAJA_CHICA','OTRO') NOT NULL DEFAULT 'OTRO',
  `metodo_pago` ENUM('EFECTIVO','TRANSFERENCIA','TARJETA_CREDITO','TARJETA_DEBITO','CHEQUE','OTRO') NOT NULL DEFAULT 'TRANSFERENCIA',
  `estatus_validacion` ENUM('PENDIENTE','VALIDADO','RECHAZADO') NOT NULL DEFAULT 'PENDIENTE',
  `requisicion_id` BIGINT UNSIGNED NULL,
  `comprobante_id` BIGINT UNSIGNED NULL,
  `descripcion` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `gastos_corporativo_id_foreign` FOREIGN KEY (`corporativo_id`) REFERENCES `corporativos`(`id`),
  CONSTRAINT `gastos_sucursal_id_foreign` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursals`(`id`),
  CONSTRAINT `gastos_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleados`(`id`),
  CONSTRAINT `gastos_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedors`(`id`),
  CONSTRAINT `gastos_requisicion_id_foreign` FOREIGN KEY (`requisicion_id`) REFERENCES `requisicions`(`id`),
  CONSTRAINT `gastos_comprobante_id_foreign` FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  CONSTRAINT `system_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

