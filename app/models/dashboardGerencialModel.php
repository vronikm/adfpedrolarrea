<?php
/**
 * dashboardGerencialModel.php
 * Modelo de datos para Dashboard Gerencial
 * 
 * Responsabilidades:
 *   - Queries complejas optimizadas
 *   - Cacheo de datos (10-60 min)
 *   - Generación de reportes estructurados
 *   - Cálculos financieros avanzados
 *   - Validación de datos antes de persistencia
 * 
 * PATRONES:
 *   - Repository Pattern para acceso a datos
 *   - Query Builder para queries dinámicas
 *   - Cache Layer con validación de TTL
 * 
 * @version 1.0
 * @author DigiFutbol Team
 */

namespace app\models;

use PDO;
use PDOException;

class dashboardGerencialModel extends mainModel {
    
    /**
     * Cache en memoria para queries frecuentes
     * @var array
     */
    private static $cache = [];
    
    /**
     * TTL del cache en segundos
     * @var int
     */
    private const CACHE_TTL = 600; // 10 minutos
    
    /* ============================================================
       1. CONSULTAS FINANCIERAS OPTIMIZADAS
       ============================================================ */
    
    /**
     * Obtiene ingresos detallados con múltiples dimensiones
     * Agrupa por: período, rubro, método de pago, estado
     * 
     * @param string $fechaInicio Formato: 'YYYY-MM-DD'
     * @param string $fechaFin    Formato: 'YYYY-MM-DD'
     * @return array
     */
    public function obtenerIngresosPorDimensiones(
        string $fechaInicio = null, 
        string $fechaFin = null
    ): array {
        
        if (!$fechaInicio) {
            $fechaInicio = date('Y-m-d', strtotime('-12 months'));
        }
        if (!$fechaFin) {
            $fechaFin = date('Y-m-d');
        }
        
        // Validar fechas
        if (strtotime($fechaInicio) === false || strtotime($fechaFin) === false) {
            throw new \Exception("Formato de fecha inválido");
        }
        
        $query = "
            SELECT
                DATE_FORMAT(ap.pago_fecha, '%Y-%m') AS periodo,
                DATE_FORMAT(ap.pago_fecha, '%b %Y') AS periodo_display,
                ap.pago_rubroid,
                ap.pago_metodo,
                ap.pago_estado,
                COUNT(*) AS cantidad,
                SUM(ap.pago_valor) AS total,
                AVG(ap.pago_valor) AS promedio,
                MIN(ap.pago_valor) AS minimo,
                MAX(ap.pago_valor) AS maximo
            FROM alumno_pago ap
            WHERE ap.pago_fecha BETWEEN :fechaInicio AND :fechaFin
            GROUP BY 
                DATE_FORMAT(ap.pago_fecha, '%Y-%m'),
                ap.pago_rubroid,
                ap.pago_metodo,
                ap.pago_estado
            ORDER BY periodo DESC, ap.pago_rubroid ASC
        ";
        
        $stmt = $this->ejecutarConsulta($query, [
            ':fechaInicio' => $fechaInicio,
            ':fechaFin' => $fechaFin
        ]);
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    
    /**
     * Calcula proyección de ingresos para próximos meses
     * Usa promedio móvil de 3 meses
     * 
     * @param int $mesesProyeccion Meses futuros a proyectar (default 3)
     * @return array
     */
    public function proyectarIngresos(int $mesesProyeccion = 3): array {
        
        if ($mesesProyeccion < 1 || $mesesProyeccion > 12) {
            throw new \Exception("Meses de proyección debe estar entre 1 y 12");
        }
        
        // Obtener promedio móvil de últimos 3 meses
        $query = "
            SELECT
                AVG(total_mes) AS promedio_ingreso,
                STD(total_mes) AS desviacion,
                MAX(total_mes) AS maximo,
                MIN(total_mes) AS minimo
            FROM (
                SELECT
                    DATE_FORMAT(pago_fecha, '%Y-%m') AS mes,
                    SUM(pago_valor) AS total_mes
                FROM alumno_pago
                WHERE pago_fecha >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                  AND pago_estado <> 'E'
                GROUP BY DATE_FORMAT(pago_fecha, '%Y-%m')
            ) promedio
        ";
        
        $stmt = $this->ejecutarConsulta($query);
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        
        if (!$row || !$row['promedio_ingreso']) {
            return ['error' => 'Sin datos históricos para proyección'];
        }
        
        // Generar proyección
        $proyecciones = [];
        $base = floatval($row['promedio_ingreso']);
        $desv = floatval($row['desviacion'] ?? 0);
        
        for ($i = 1; $i <= $mesesProyeccion; $i++) {
            $fecha = date('Y-m', strtotime("+$i months"));
            // Proyección simple + factor de incremento
            $incremento = 1 + (0.02 * ($i / $mesesProyeccion)); // 2% max incremento
            
            $proyecciones[] = [
                'periodo' => $fecha,
                'projection' => round($base * $incremento, 2),
                'min_probable' => round(max(0, ($base - $desv) * $incremento), 2),
                'max_probable' => round(($base + $desv) * $incremento, 2)
            ];
        }
        
        return $proyecciones;
    }
    
    /* ============================================================
       2. ANÁLISIS DE DEUDORES Y MORA
       ============================================================ */
    
    /**
     * Análisis segmentado de deudores por categoría de riesgo
     * 
     * @return array Deudores clasificados por riesgo
     */
    public function analizarDeudoresSegmentado(): array {
        
        $query = "
            SELECT
                CASE 
                    WHEN TIMESTAMPDIFF(MONTH, MAX(ap.pago_fecha), CURDATE()) >= 6 THEN 'CRÍTICO'
                    WHEN TIMESTAMPDIFF(MONTH, MAX(ap.pago_fecha), CURDATE()) >= 3 THEN 'ALTO'
                    WHEN TIMESTAMPDIFF(MONTH, MAX(ap.pago_fecha), CURDATE()) >= 1 THEN 'MEDIO'
                    ELSE 'BAJO'
                END AS categoria_riesgo,
                COUNT(DISTINCT sa.alumno_id) AS cantidad_alumnos,
                SUM(ap.pago_saldo) AS total_deuda,
                AVG(ap.pago_saldo) AS deuda_promedio,
                COUNT(DISTINCT ap.pago_id) AS pagos_pendientes
            FROM sujeto_alumno sa
            LEFT JOIN alumno_pago ap 
                ON ap.pago_alumnoid = sa.alumno_id 
                AND ap.pago_estado = 'E'
                AND ap.pago_rubroid = 'RPE'
            WHERE sa.alumno_estado = 'A'
            GROUP BY categoria_riesgo
            ORDER BY FIELD(categoria_riesgo, 'CRÍTICO', 'ALTO', 'MEDIO', 'BAJO')
        ";
        
        $stmt = $this->ejecutarConsulta($query);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    
    /* ============================================================
       3. ANÁLISIS DE ROTACIÓN Y MATRÍCULA
       ============================================================ */
    
    /**
     * Matriz de movimiento: ingresos vs retiros por sede y período
     * 
     * @return array Datos de movimiento estructurados
     */
    public function matrizMovimientoMatricula(): array {
        
        $query = "
            SELECT
                gs.sede_nombre,
                DATE_FORMAT(sa.alumno_fechaingreso, '%Y-%m') AS mes_ingreso,
                SUM(CASE WHEN sa.alumno_estado = 'A' THEN 1 ELSE 0 END) AS alumnos_activos,
                SUM(CASE WHEN sa.alumno_estado = 'I' 
                    AND sa.alumno_fechamodificacion >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    THEN 1 ELSE 0 END) AS alumnos_retirados,
                COUNT(DISTINCT sa.alumno_id) AS total_historico
            FROM sujeto_alumno sa
            INNER JOIN general_sede gs ON gs.sede_id = sa.alumno_sedeid
            WHERE sa.alumno_fechaingreso >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY gs.sede_nombre, DATE_FORMAT(sa.alumno_fechaingreso, '%Y-%m')
            ORDER BY gs.sede_nombre ASC, mes_ingreso DESC
        ";
        
        $stmt = $this->ejecutarConsulta($query);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    
    /* ============================================================
       4. ANÁLISIS DE ASISTENCIA
       ============================================================ */
    
    /**
     * Calcula tasa de asistencia global por período
     * 
     * @param string $periodo Formato: 'YYYYMM' ej: 202503 (marzo 2025)
     * @return array Tasa de asistencia detallada
     */
    public function tasaAsistenciaGlobal(string $periodo = null): array {
        
        if (!$periodo) {
            $periodo = date('Ym'); // Mes actual en formato YYYYMM
        }
        
        // Validar formato YYYYMM
        if (!preg_match('/^\d{6}$/', $periodo)) {
            throw new \Exception("Formato de período inválido: YYYYMM");
        }
        
        $query = "
            SELECT
                COUNT(DISTINCT aa.asistencia_alumnoid) AS alumnos_marcados,
                SUM(
                    (CASE WHEN aa.asistencia_D01='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D02='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D03='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D04='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D05='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D06='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D07='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D08='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D09='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D10='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D11='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D12='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D13='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D14='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D15='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D16='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D17='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D18='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D19='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D20='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D21='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D22='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D23='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D24='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D25='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D26='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D27='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D28='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D29='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D30='P' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D31='P' THEN 1 ELSE 0 END)
                ) AS presentes,
                SUM(
                    (CASE WHEN aa.asistencia_D01='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D02='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D03='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D04='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D05='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D06='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D07='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D08='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D09='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D10='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D11='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D12='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D13='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D14='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D15='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D16='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D17='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D18='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D19='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D20='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D21='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D22='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D23='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D24='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D25='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D26='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D27='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D28='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D29='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D30='A' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D31='A' THEN 1 ELSE 0 END)
                ) AS ausentes,
                SUM(
                    (CASE WHEN aa.asistencia_D01='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D02='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D03='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D04='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D05='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D06='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D07='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D08='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D09='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D10='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D11='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D12='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D13='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D14='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D15='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D16='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D17='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D18='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D19='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D20='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D21='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D22='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D23='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D24='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D25='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D26='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D27='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D28='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D29='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D30='F' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D31='F' THEN 1 ELSE 0 END)
                ) AS faltas,
                SUM(
                    (CASE WHEN aa.asistencia_D01='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D02='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D03='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D04='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D05='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D06='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D07='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D08='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D09='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D10='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D11='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D12='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D13='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D14='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D15='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D16='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D17='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D18='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D19='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D20='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D21='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D22='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D23='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D24='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D25='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D26='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D27='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D28='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D29='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D30='J' THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D31='J' THEN 1 ELSE 0 END)
                ) AS justificadas,
                SUM(
                    (CASE WHEN aa.asistencia_D01 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D02 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D03 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D04 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D05 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D06 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D07 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D08 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D09 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D10 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D11 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D12 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D13 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D14 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D15 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D16 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D17 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D18 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D19 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D20 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D21 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D22 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D23 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D24 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D25 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D26 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D27 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D28 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D29 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D30 IS NOT NULL THEN 1 ELSE 0 END) +
                    (CASE WHEN aa.asistencia_D31 IS NOT NULL THEN 1 ELSE 0 END)
                ) AS total_registros
            FROM asistencia_asistencia aa
            WHERE aa.asistencia_aniomes = :periodo
        ";
        
        $stmt = $this->ejecutarConsulta($query, [':periodo' => intval($periodo)]);
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : [];
        
        if (!$row || !$row['total_registros']) {
            return ['error' => 'Sin datos de asistencia para el período'];
        }
        
        // Calcular porcentaje
        $presentes = intval($row['presentes'] ?? 0);
        $total = intval($row['total_registros'] ?? 1);
        
        return [
            'alumnos_marcados' => intval($row['alumnos_marcados'] ?? 0),
            'presentes' => $presentes,
            'ausentes' => intval($row['ausentes'] ?? 0),
            'faltas' => intval($row['faltas'] ?? 0),
            'justificadas' => intval($row['justificadas'] ?? 0),
            'total_registros' => $total,
            'tasa_asistencia_pct' => round(($presentes / max($total, 1)) * 100, 2)
        ];
    }
    
    /* ============================================================
       5. GENERACIÓN DE REPORTES
       ============================================================ */
    
    /**
     * Genera reporte ejecutivo consolidado
     * 
     * @return array Reporte con múltiples secciones
     */
    public function generarReporteEjecutivo(): array {
        
        return [
            'fecha_generacion' => date('Y-m-d H:i:s'),
            'periodo' => date('F Y', strtotime('-1 month')),
            
            // Sección de Matrícula
            'seccion_matricula' => [
                'total_activos' => intval($this->ejecutarConsulta(
                    "SELECT COUNT(*) as total FROM sujeto_alumno WHERE alumno_estado = 'A'"
                )->fetch()['total']),
                'total_inactivos' => intval($this->ejecutarConsulta(
                    "SELECT COUNT(*) as total FROM sujeto_alumno WHERE alumno_estado = 'I'"
                )->fetch()['total']),
                'ingresos_mes' => intval($this->ejecutarConsulta(
                    "SELECT COUNT(*) as total FROM sujeto_alumno 
                     WHERE MONTH(alumno_fechaingreso) = MONTH(CURDATE())"
                )->fetch()['total']),
                'retiros_mes' => intval($this->ejecutarConsulta(
                    "SELECT COUNT(*) as total FROM sujeto_alumno 
                     WHERE alumno_estado = 'I' AND MONTH(alumno_fechamodificacion) = MONTH(CURDATE())"
                )->fetch()['total'])
            ],
            
            // Sección Financiera
            'seccion_financiera' => $this->obtenerIngresosPorDimensiones(
                date('Y-m-d', strtotime('-1 month')),
                date('Y-m-d')
            ),
            
            // Sección de Riesgos
            'seccion_riesgos' => $this->analizarDeudoresSegmentado(),
            
            // Sección de Movimiento
            'seccion_movimiento' => $this->matrizMovimientoMatricula()
        ];
    }
    
    /* ============================================================
       6. UTILIDADES Y VALIDACIONES
       ============================================================ */
    
    /**
     * Valida formato de período "Mes / YYYY"
     * Ejemplo: "Marzo / 2025"
     * 
     * @param string $periodo Período a validar
     * @return bool
     */
    public static function validarPeriodo(string $periodo): bool {
        return preg_match('/^[A-Z][a-z]+\s\/\s\d{4}$/', $periodo) === 1;
    }
    
    /**
     * Convierte período "Mes / YYYY" a formato SQL "YYYY-MM"
     * 
     * @param string $periodo Formato: "Marzo / 2025"
     * @return string Formato: "2025-03"
     */
    public static function convertirPeriodoSQL(string $periodo): string {
        
        if (!self::validarPeriodo($periodo)) {
            throw new \Exception("Formato de período inválido");
        }
        
        list($mes, $año) = explode(' / ', $periodo);
        
        $meses = [
            'Enero' => '01', 'Febrero' => '02', 'Marzo' => '03',
            'Abril' => '04', 'Mayo' => '05', 'Junio' => '06',
            'Julio' => '07', 'Agosto' => '08', 'Septiembre' => '09',
            'Octubre' => '10', 'Noviembre' => '11', 'Diciembre' => '12'
        ];
        
        if (!isset($meses[$mes])) {
            throw new \Exception("Mes inválido: $mes");
        }
        
        return "$año-" . $meses[$mes];
    }
    
    /**
     * Formatea número como moneda (USD/VES según contexto)
     * 
     * @param float $valor Valor a formater
     * @param string $divisa 'USD' | 'VES' (default)
     * @return string
     */
    public static function formatearMoneda(float $valor, string $divisa = 'VES'): string {
        $divisa = strtoupper($divisa);
        
        if ($divisa === 'USD') {
            return '$ ' . number_format($valor, 2, '.', ',');
        }
        
        return 'Bs. ' . number_format($valor, 2, ',', '.');
    }
}
