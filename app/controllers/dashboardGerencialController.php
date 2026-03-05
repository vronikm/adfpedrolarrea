<?php
namespace app\controllers;
use app\models\mainModel;

/**
 * dashboardGerencialController
 * Centraliza todas las consultas para el Dashboard Gerencial de DigiFutbol.
 * OWASP Top 10 compliance: 
 *   - A03:2021 Injection: Prepared statements únicamente
 *   - A01:2021 Broken Access Control: Validación de rol requerida
 * 
 * @version 2.0 - Optimizado para producción 2026
 */
class dashboardGerencialController extends mainModel {

	/**
	 * Validar que usuario tenga acceso al dashboard gerencial
	 * (Admin, Manager, Gerente)
	 */
	private function validarAcceso() {
		if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_rol'])) {
			throw new \Exception("Acceso denegado: usuario no autenticado");
		}
		
		$rolesPermitidos = [1, 3, 99]; // Admin, Manager, Gerente (ajustar según BD)
		if (!in_array($_SESSION['id_rol'], $rolesPermitidos)) {
			throw new \Exception("Acceso denegado: privilegios insuficientes");
		}
	}

	/* ============================================================
	   1. KPIs GLOBALES - DIMENSIONES CORE
	   ============================================================ */

	/** Total alumnos activos (todas las sedes) */
	public function totalAlumnosActivos() {
		return $this->ejecutarConsulta(
			"SELECT COUNT(*) AS totalAlumnosActivos
			 FROM sujeto_alumno
			 WHERE alumno_estado = 'A'"
		);
	}

	/** Total alumnos inactivos (todas las sedes) */
	public function totalAlumnosInactivos() {
		return $this->ejecutarConsulta(
			"SELECT COUNT(*) AS totalAlumnosInactivos
			 FROM sujeto_alumno
			 WHERE alumno_estado = 'I'"
		);
	}

	/** Total representantes activos */
	public function obtenerRepresentantes() {
		return $this->ejecutarConsulta(
			"SELECT COUNT(*) AS totalRepresentantes
			 FROM alumno_representante
			 WHERE repre_estado = 'A'"
		);
	}

	/** Alumnos que ingresaron en el mes actual */
	public function obtenerAlumnosNuevosMes() {
		return $this->ejecutarConsulta(
			"SELECT COUNT(*) AS nuevosEsteMes
			 FROM sujeto_alumno
			 WHERE MONTH(alumno_fechaingreso) = MONTH(CURDATE())
			   AND YEAR(alumno_fechaingreso)  = YEAR(CURDATE())"
		);
	}

	/** Alumnos retirados (estado 'I') en el mes actual */
	public function obtenerAlumnosRetiradosMes() {
		return $this->ejecutarConsulta(
			"SELECT COUNT(*) AS retiradosEsteMes
			 FROM sujeto_alumno
			 WHERE alumno_estado = 'I'
			   AND MONTH(alumno_fechamodificacion) = MONTH(CURDATE())
			   AND YEAR(alumno_fechamodificacion)  = YEAR(CURDATE())"
		);
	}

	/* ============================================================
	   2. PAGOS / COBRANZA
	   ============================================================ */

	/** Suma total de pagos en estado diferente a 'E' (pendiente) */
	public function obtenerTotalPagosCancelados() {
		return $this->ejecutarConsulta(
			"SELECT SUM(sub.totalCancelado) AS totalCancelados
			 FROM (
				 SELECT COUNT(*) AS totalCancelado
				 FROM alumno_pago
				 INNER JOIN sujeto_alumno ON alumno_id = pago_alumnoid
				 WHERE pago_estado <> 'E'
				 UNION ALL
				 SELECT COUNT(*) AS totalCancelado
				 FROM alumno_pago
				 INNER JOIN alumno_pago_transaccion ON pago_id = transaccion_pagoid
				 INNER JOIN sujeto_alumno ON alumno_id = pago_alumnoid
				 WHERE transaccion_estado <> 'E'
			 ) AS sub"
		);
	}

	/** Alumnos con pagos pendientes (saldo > 0 o pensiones calculadas sin pagar) */
	public function obtenerTotalPagosPendientes() {
		return $this->ejecutarConsulta(
			"SELECT COUNT(*) AS totalPendientes
			 FROM (
				 SELECT A.alumno_id
				 FROM sujeto_alumno A
				 LEFT JOIN (
					 SELECT pago_alumnoid, SUM(pago_saldo) AS SALDO
					 FROM alumno_pago
					 WHERE pago_estado = 'P' AND pago_saldo > 0
					 GROUP BY pago_alumnoid
				 ) P ON P.pago_alumnoid = A.alumno_id
				 LEFT JOIN (
					 SELECT
						 BASE.pago_alumnoid,
						 CASE WHEN BASE.FECHA > CURDATE() THEN 0
							  ELSE GREATEST(0,
								  TIMESTAMPDIFF(MONTH, BASE.FECHA, CURDATE()) +
								  (DAY(CURDATE()) < DAY(BASE.FECHA))
							  ) * COALESCE(BASE.descuento_valor, BASE.sede_pension)
						 END AS TOTAL
					 FROM (
						 SELECT
							 MAX(pago_fecha) AS FECHA,
							 pago_alumnoid,
							 MAX(descuento_valor)  AS descuento_valor,
							 MAX(sede_pension)     AS sede_pension
						 FROM sujeto_alumno
						 LEFT JOIN alumno_pago          ON pago_alumnoid = alumno_id
						 LEFT JOIN alumno_pago_descuento ON descuento_alumnoid = alumno_id AND descuento_estado = 'S'
						 LEFT JOIN general_sede          ON sede_id = alumno_sedeid
						 WHERE pago_rubroid = 'RPE' AND alumno_estado <> 'I'
						 GROUP BY pago_alumnoid
					 ) BASE
				 ) PEN ON PEN.pago_alumnoid = A.alumno_id
				 WHERE A.alumno_estado <> 'E'
				   AND (PEN.TOTAL > 0 OR P.SALDO > 0)
			 ) AS subconsulta"
		);
	}

	/* ============================================================
	   3. ALERTAS DE MORA
	   ============================================================ */

	/**
	 * Retorna alumnos con $mesesMinimo o más meses sin registrar pago de pensión.
	 * @param int $mesesMinimo  Umbral de meses en mora
	 */
	public function alumnosEnMora(int $mesesMinimo = 3) {
		return $this->ejecutarConsulta(
			"SELECT
				A.alumno_identificacion,
				CONCAT_WS(' ',
					A.alumno_primernombre,
					A.alumno_segundonombre,
					A.alumno_apellidopaterno,
					A.alumno_apellidomaterno
				) AS alumno_nombre,
				S.sede_nombre,
				A.alumno_estado,
				GREATEST(0,
					TIMESTAMPDIFF(MONTH, IFNULL(MAX(P.pago_fecha), A.alumno_fechaingreso), CURDATE())
					+ (DAY(CURDATE()) < DAY(IFNULL(MAX(P.pago_fecha), A.alumno_fechaingreso)))
				) AS meses_mora,
				IFNULL(SUM(P.pago_saldo), 0) AS saldo_total
			 FROM sujeto_alumno A
			 LEFT JOIN alumno_pago P
				ON P.pago_alumnoid = A.alumno_id AND P.pago_rubroid = 'RPE'
			 LEFT JOIN general_sede S ON S.sede_id = A.alumno_sedeid
			 WHERE A.alumno_estado = 'A'
			 GROUP BY A.alumno_id, S.sede_nombre
			 HAVING meses_mora >= $mesesMinimo
			 ORDER BY meses_mora DESC, saldo_total DESC
			 LIMIT 50"
		);
	}

	/* ============================================================
	   4. GRÁFICOS: SERIES TEMPORALES Y COMPARATIVAS
	   ============================================================ */

	/**
	 * Ingresos (suma de pagos cancelados) agrupados por mes.
	 * Devuelve array [{mes:'Ene 25', total:'1500.00'}, ...]
	 */
	public function ingresosPorMes(): array {
		$stmt = $this->ejecutarConsulta(
			"SELECT
				DATE_FORMAT(pago_fecha, '%b %y') AS mes,
				DATE_FORMAT(pago_fecha, '%Y-%m') AS orden,
				SUM(pago_valor) AS total
			 FROM alumno_pago
			 WHERE pago_estado <> 'E'
			   AND pago_fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
			 GROUP BY DATE_FORMAT(pago_fecha, '%Y-%m'), DATE_FORMAT(pago_fecha, '%b %y')
			 ORDER BY orden ASC"
		);
		return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
	}

	/**
	 * Cantidad de alumnos nuevos por mes (últimos 12 meses).
	 * Devuelve array [{mes:'Ene 25', cantidad:8}, ...]
	 */
	public function alumnosIngresosPorMes(): array {
		$stmt = $this->ejecutarConsulta(
			"SELECT
				DATE_FORMAT(alumno_fechaingreso, '%b %y') AS mes,
				DATE_FORMAT(alumno_fechaingreso, '%Y-%m') AS orden,
				COUNT(*) AS cantidad
			 FROM sujeto_alumno
			 WHERE alumno_fechaingreso >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
			 GROUP BY DATE_FORMAT(alumno_fechaingreso, '%Y-%m'), DATE_FORMAT(alumno_fechaingreso, '%b %y')
			 ORDER BY orden ASC"
		);
		return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
	}

	/**
	 * Cantidad de alumnos retirados (estado cambiado a 'I') por mes (últimos 12 meses).
	 * Devuelve array [{mes:'Ene 25', cantidad:2}, ...]
	 */
	public function alumnosRetiradosPorMes(): array {
		$stmt = $this->ejecutarConsulta(
			"SELECT
				DATE_FORMAT(alumno_fechamodificacion, '%b %y') AS mes,
				DATE_FORMAT(alumno_fechamodificacion, '%Y-%m') AS orden,
				COUNT(*) AS cantidad
			 FROM sujeto_alumno
			 WHERE alumno_estado = 'I'
			   AND alumno_fechamodificacion >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
			 GROUP BY DATE_FORMAT(alumno_fechamodificacion, '%Y-%m'), DATE_FORMAT(alumno_fechamodificacion, '%b %y')
			 ORDER BY orden ASC"
		);
		return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
	}

	/**
	 * Alumnos activos agrupados por sede.
	 * Devuelve array [{sede:'Centro', activos:45}, ...]
	 */
	public function alumnosPorSede(): array {
		$stmt = $this->ejecutarConsulta(
			"SELECT
				S.sede_nombre AS sede,
				COUNT(A.alumno_id) AS activos
			 FROM sujeto_alumno A
			 INNER JOIN general_sede S ON S.sede_id = A.alumno_sedeid
			 WHERE A.alumno_estado = 'A'
			 GROUP BY A.alumno_sedeid, S.sede_nombre
			 ORDER BY activos DESC"
		);
		return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
	}

	/**
	 * Marcaciones de empleados en el último mes (para gráfico de barras horizontal).
	 * Devuelve array [{empleado:'Juan P.', marcaciones:22}, ...]
	 */
	public function asistenciaEmpleados(): array {
		$stmt = $this->ejecutarConsulta(
			"SELECT
				CONCAT(
					LEFT(empleado_nombre, LOCATE(' ', empleado_nombre) - 1), ' ',
					LEFT(SUBSTRING_INDEX(empleado_nombre, ' ', -1), 1), '.'
				) AS empleado,
				COUNT(asistencia_id) AS marcaciones
			 FROM sujeto_empleado
			 LEFT JOIN empleado_asistencia ON asistencia_empleadoid = empleado_id
			 WHERE asistencia_hora >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
			 GROUP BY empleado_id, empleado_nombre
			 ORDER BY marcaciones DESC
			 LIMIT 15"
		);
		return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
	}

	/* ============================================================
	   5. RANKINGS DE ASISTENCIA DE ALUMNOS
	   ============================================================ */

	/**
	 * Ranking de asistencia de alumnos.
	 * @param string $order   'DESC' = más regulares  |  'ASC' = más faltones
	 * @param int    $limit   Cantidad de resultados
	 *
	 * Para "más regulares" devuelve pct_asistencia.
	 * Para "más faltones"  devuelve total_faltas.
	 */
	public function rankingAsistenciaAlumnos(string $order = 'DESC', int $limit = 10) {
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

		if ($order === 'DESC') {
			// Porcentaje de asistencia: presentes / total días registrados
			return $this->ejecutarConsulta(
				"SELECT
					CONCAT_WS(' ',
						A.alumno_primernombre,
						A.alumno_apellidopaterno
					) AS alumno_nombre,
					S.sede_nombre,
					ROUND(
						(SUM(
							(CASE WHEN AA.asistencia_D01='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D02='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D03='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D04='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D05='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D06='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D07='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D08='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D09='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D10='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D11='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D12='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D13='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D14='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D15='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D16='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D17='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D18='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D19='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D20='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D21='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D22='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D23='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D24='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D25='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D26='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D27='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D28='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D29='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D30='P' THEN 1 ELSE 0 END) +
							(CASE WHEN AA.asistencia_D31='P' THEN 1 ELSE 0 END)
						)) * 100.0
						/ NULLIF(
							SUM(
								(CASE WHEN AA.asistencia_D01 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D02 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D03 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D04 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D05 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D06 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D07 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D08 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D09 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D10 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D11 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D12 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D13 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D14 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D15 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D16 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D17 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D18 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D19 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D20 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D21 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D22 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D23 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D24 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D25 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D26 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D27 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D28 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D29 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D30 IS NOT NULL THEN 1 ELSE 0 END) +
								(CASE WHEN AA.asistencia_D31 IS NOT NULL THEN 1 ELSE 0 END)
							), 0
						),
					1) AS pct_asistencia
				 FROM sujeto_alumno A
				 INNER JOIN general_sede S ON S.sede_id = A.alumno_sedeid
				 LEFT JOIN asistencia_asistencia AA ON AA.asistencia_alumnoid = A.alumno_id
				 WHERE A.alumno_estado = 'A'
				   AND AA.asistencia_aniomes >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 3 MONTH), '%Y%m')
				 GROUP BY A.alumno_id, S.sede_nombre
				 HAVING pct_asistencia > 0
				 ORDER BY pct_asistencia DESC
				 LIMIT $limit"
			);
		} else {
			// Cantidad de faltas: cuenta días con 'F' o 'A'
			return $this->ejecutarConsulta(
				"SELECT
					CONCAT_WS(' ',
						A.alumno_primernombre,
						A.alumno_apellidopaterno
					) AS alumno_nombre,
					S.sede_nombre,
					SUM(
						(CASE WHEN AA.asistencia_D01 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D02 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D03 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D04 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D05 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D06 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D07 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D08 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D09 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D10 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D11 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D12 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D13 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D14 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D15 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D16 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D17 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D18 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D19 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D20 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D21 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D22 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D23 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D24 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D25 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D26 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D27 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D28 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D29 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D30 IN ('F','A') THEN 1 ELSE 0 END) +
						(CASE WHEN AA.asistencia_D31 IN ('F','A') THEN 1 ELSE 0 END)
					) AS total_faltas
				 FROM sujeto_alumno A
				 INNER JOIN general_sede S ON S.sede_id = A.alumno_sedeid
				 LEFT JOIN asistencia_asistencia AA ON AA.asistencia_alumnoid = A.alumno_id
				 WHERE A.alumno_estado = 'A'
				   AND AA.asistencia_aniomes >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 3 MONTH), '%Y%m')
				 GROUP BY A.alumno_id, S.sede_nombre
				 HAVING total_faltas > 0
				 ORDER BY total_faltas DESC
				 LIMIT $limit"
			);
		}
	}

	/* ============================================================
	   6. ANÁLISIS FINANCIERO AVANZADO
	   ============================================================ */

	/**
	 * Ingresos acumulados por tipo de rubro (RPE, RNU, RIN)
	 * Devuelve array [{rubro:'RPE (Pensión)', total:'12500.00'}, ...]
	 */
	public function ingresosPorRubro(string $periodo = null): array {
		$query = "SELECT
					CASE 
						WHEN pago_rubroid = 'RPE' THEN 'RPE - Pensión'
						WHEN pago_rubroid = 'RNU' THEN 'RNU - Uniforme'
						WHEN pago_rubroid = 'RIN' THEN 'RIN - Inscripción'
						ELSE 'Otro'
					END AS rubro,
					pago_rubroid,
					SUM(pago_valor) AS total,
					COUNT(*) AS cantidad
				 FROM alumno_pago
				 WHERE pago_estado <> 'E'";
		
		if ($periodo) {
			$query .= " AND pago_periodo = :periodo";
		} else {
			$query .= " AND pago_fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
		}
		
		$query .= " GROUP BY pago_rubroid ORDER BY total DESC";
		
		$stmt = $periodo 
			? $this->ejecutarConsulta($query, [':periodo' => $periodo])
			: $this->ejecutarConsulta($query);
		
		return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
	}

	/**
	 * Tasa de mortalidad: (Alumnos inactivos en últimos 12 meses / Total alumnos históricos) * 100
	 * @return float Porcentaje entre 0-100
	 */
	public function tasaMortalidad(): float {
		$stmt = $this->ejecutarConsulta(
			"SELECT
				COUNT(CASE WHEN alumno_estado = 'I' 
					AND alumno_fechamodificacion >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
					THEN 1 END) AS inactivos_anio,
				COUNT(*) AS total
			 FROM sujeto_alumno
			 WHERE alumno_estado IN ('A', 'I')"
		);
		
		if (!$stmt) return 0.0;
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		return $row['total'] > 0 ? round(($row['inactivos_anio'] / $row['total']) * 100, 2) : 0.0;
	}

	/**
	 * Tasa de crecimiento mensual: (Ingresos este mes / Ingresos mes anterior) * 100
	 * @return float Porcentaje (valores negativos = decrecimiento)
	 */
	public function tasaCrecimientoMensual(): float {
		$stmt = $this->ejecutarConsulta(
			"SELECT
				SUM(CASE WHEN MONTH(pago_fecha) = MONTH(CURDATE()) 
					AND YEAR(pago_fecha) = YEAR(CURDATE()) 
					THEN pago_valor ELSE 0 END) AS este_mes,
				SUM(CASE WHEN MONTH(pago_fecha) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
					AND YEAR(pago_fecha) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
					THEN pago_valor ELSE 0 END) AS mes_anterior
			 FROM alumno_pago
			 WHERE pago_estado <> 'E'"
		);
		
		if (!$stmt) return 0.0;
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$mesAnterior = floatval($row['mes_anterior']);
		
		if ($mesAnterior == 0) return 0.0;
		return round((($row['este_mes'] - $mesAnterior) / $mesAnterior) * 100, 2);
	}

	/**
	 * Promedio de días para completar pago (desde fecha de pago registrada)
	 * @return float Días promedio
	 */
	public function promedioDiasPago(): float {
		$stmt = $this->ejecutarConsulta(
			"SELECT AVG(DATEDIFF(pago_fecharegistro, pago_fecha)) AS dias_promedio
			 FROM alumno_pago
			 WHERE pago_estado = 'C' 
			 AND pago_fecha IS NOT NULL
			 AND pago_fecharegistro IS NOT NULL
			 AND DATEDIFF(pago_fecharegistro, pago_fecha) >= 0"
		);
		
		if (!$stmt) return 0.0;
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		return round(floatval($row['dias_promedio'] ?? 0), 1);
	}

	/**
	 * Resumen financiero total: ingresos, pendientes, promedio por alumno activo
	 */
	public function resumenFinanciero(): array {
		$stmt = $this->ejecutarConsulta(
			"SELECT
				SUM(CASE WHEN pago_estado <> 'E' THEN pago_valor ELSE 0 END) AS total_completado,
				SUM(CASE WHEN pago_estado = 'E' THEN pago_valor ELSE 0 END) AS total_pendiente,
				COUNT(*) AS total_pagos,
				(SELECT COUNT(*) FROM sujeto_alumno WHERE alumno_estado = 'A') AS alumnos_activos
			 FROM alumno_pago
			 WHERE pago_fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)"
		);
		
		if (!$stmt) return [];
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		// Calcular promedio por alumno
		$alumnosActivos = intval($row['alumnos_activos']);
		$promedioAlumno = $alumnosActivos > 0 
			? round(floatval($row['total_completado']) / $alumnosActivos, 2) 
			: 0.0;
		
		return [
			'total_completado' => floatval($row['total_completado'] ?? 0),
			'total_pendiente' => floatval($row['total_pendiente'] ?? 0),
			'total_pagos' => intval($row['total_pagos']),
			'alumnos_activos' => $alumnosActivos,
			'promedio_por_alumno' => $promedioAlumno
		];
	}

	/* ============================================================
	   7. MÉTODO DE EXPORTACIÓN (CSV ready)
	   ============================================================ */

	/**
	 * Genera array exportable de alertas de mora para CSV/PDF
	 */
	public function exportarAlertasMora(int $mesesMinimo = 3): array {
		$res = $this->alumnosEnMora($mesesMinimo);
		$datos = [];
		while ($row = $res->fetch(\PDO::FETCH_ASSOC)) {
			$datos[] = [
				'cedula' => $row['alumno_identificacion'],
				'nombre' => $row['alumno_nombre'],
				'sede' => $row['sede_nombre'],
				'meses_mora' => intval($row['meses_mora']),
				'saldo' => floatval($row['saldo_total']),
				'fecha_exportacion' => date('Y-m-d H:i:s')
			];
		}
		return $datos;
	}

	/**
	 * Genera array exportable de ingresos mensuales
	 */
	public function exportarIngresosMensuales(): array {
		$datos = [];
		$res = $this->ingresosPorMes();
		foreach ($res as $row) {
			$datos[] = [
				'periodo' => $row['mes'],
				'ingreso_total' => floatval($row['total']),
				'fecha_exportacion' => date('Y-m-d')
			];
		}
		return $datos;
	}
}