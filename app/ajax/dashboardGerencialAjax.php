<?php
/**
 * dashboardGerencialAjax.php
 * Gestor AJAX para el Dashboard Gerencial
 * 
 * Funcionalidades:
 *   - Exportación de datos (CSV, JSON)
 *   - Filtrado por período
 *   - Recarga de indicadores específicos
 *   - Generación de reportes
 * 
 * OWASP Top 10 compliance:
 *   - A03:2021 Injection: Prepared statements (en Controller)
 *   - A01:2021 Broken Access Control: Validación de rol requerida
 *   - A07:2021 XSS: Output encoding en JSON
 * 
 * @version 1.0
 */

require_once "../../config/app.php";
require_once "../views/inc/session_start.php";
require_once "../../autoload.php";

use app\controllers\dashboardGerencialController;

// Validar autenticación
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_rol'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso denegado: no autenticado']);
    exit;
}

// Validar rol (Admin, Manager, Gerente)
$rolesPermitidos = [1, 3, 99]; // Ajustar según BD
if (!in_array($_SESSION['id_rol'], $rolesPermitidos)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso denegado: privilegios insuficientes']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$insDash = new dashboardGerencialController();
$accion = isset($_POST['accion']) ? trim($_POST['accion']) : '';

try {
    switch ($accion) {
        
        /* ============================================================
           EXPORTACIÓN DE DATOS
           ============================================================ */
        
        case 'exportar_mora_csv':
            /**
             * Exporta alertas de mora en formato CSV
             * POST['meses_minimo'] = umbral de meses en mora (default 3)
             */
            $mesesMin = isset($_POST['meses_minimo']) ? intval($_POST['meses_minimo']) : 3;
            $datos = $insDash->exportarAlertasMora($mesesMin);
            
            $csv = "Cédula,Nombre,Sede,Meses en mora,Saldo Vencido,Fecha Exportación\n";
            foreach ($datos as $fila) {
                $csv .= sprintf(
                    '"%s","%s","%s",%d,%.2f,"%s"%s',
                    addslashes($fila['cedula']),
                    addslashes($fila['nombre']),
                    addslashes($fila['sede']),
                    $fila['meses_mora'],
                    $fila['saldo'],
                    $fila['fecha_exportacion'],
                    "\n"
                );
            }
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="alertas_mora_' . date('Y-m-d_His') . '.csv"');
            echo $csv;
            exit;
            
        case 'exportar_ingresos_csv':
            /**
             * Exporta ingresos mensuales en formato CSV
             */
            $datos = $insDash->exportarIngresosMensuales();
            
            $csv = "Período,Ingreso Total,Fecha Exportación\n";
            foreach ($datos as $fila) {
                $csv .= sprintf(
                    '"%s",%.2f,"%s"%s',
                    $fila['periodo'],
                    $fila['ingreso_total'],
                    $fila['fecha_exportacion'],
                    "\n"
                );
            }
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="ingresos_' . date('Y-m-d_His') . '.csv"');
            echo $csv;
            exit;
        
        /* ============================================================
           RECARGA DE INDICADORES (AJAX JSON)
           ============================================================ */
        
        case 'recarga_kpis':
            /**
             * Retorna todos los KPIs en JSON para actualización sin recargar página
             */
            $resumen = $insDash->resumenFinanciero();
            
            echo json_encode([
                'success' => true,
                'kpis' => [
                    'totalAlumnosActivos'   => intval($insDash->totalAlumnosActivos()->fetch()['totalAlumnosActivos']),
                    'totalAlumnosInactivos' => intval($insDash->totalAlumnosInactivos()->fetch()['totalAlumnosInactivos']),
                    'totalRepresentantes'   => intval($insDash->obtenerRepresentantes()->fetch()['totalRepresentantes']),
                    'pagosPendientes'       => intval($insDash->obtenerTotalPagosPendientes()->fetch()['totalPendientes']),
                    'pagosCancelados'       => intval($insDash->obtenerTotalPagosCancelados()->fetch()['totalCancelados']),
                    'alumnosNuevosMes'      => intval($insDash->obtenerAlumnosNuevosMes()->fetch()['nuevosEsteMes']),
                    'alumnosRetiradosMes'   => intval($insDash->obtenerAlumnosRetiradosMes()->fetch()['retiradosEsteMes']),
                    'tasaMortalidad'        => floatval($insDash->tasaMortalidad()),
                    'tasaCrecimiento'       => floatval($insDash->tasaCrecimientoMensual()),
                    'promedioPago'          => floatval($insDash->promedioDiasPago()),
                    'resumen'               => $resumen
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        
        case 'recarga_alertas':
            /**
             * Retorna tabla de alertas de mora en HTML
             * POST['meses_minimo'] = umbral (default 3)
             */
            $mesesMin = isset($_POST['meses_minimo']) ? intval($_POST['meses_minimo']) : 3;
            $alertas = $insDash->alumnosEnMora($mesesMin);
            
            $html = '<table class="mora-table"><thead><tr>';
            $html .= '<th>Identificación</th><th>Nombre</th><th>Sede</th>';
            $html .= '<th>Meses sin pago</th><th>Saldo vencido</th><th>Estado</th></tr></thead><tbody>';
            
            if ($alertas && $alertas->rowCount() > 0) {
                foreach ($alertas as $row) {
                    $meses = intval($row['meses_mora']);
                    $badge = $meses >= 6 ? 'badge-mora-grave' : 'badge-mora';
                    $html .= sprintf(
                        '<tr><td>%s</td><td>%s</td><td>%s</td><td style="color:var(--rojo);font-weight:700;">%d</td><td style="color:var(--amarillo);font-weight:600;">$ %.2f</td><td><span class="%s">%s</span></td></tr>',
                        htmlspecialchars($row['alumno_identificacion'], ENT_QUOTES),
                        htmlspecialchars($row['alumno_nombre'], ENT_QUOTES),
                        htmlspecialchars($row['sede_nombre'], ENT_QUOTES),
                        $meses,
                        floatval($row['saldo_total']),
                        $badge,
                        htmlspecialchars($row['alumno_estado'], ENT_QUOTES)
                    );
                }
            } else {
                $html .= '<tr><td colspan="6" style="color:var(--verde);text-align:center;padding:16px 0;">';
                $html .= '<i class="fas fa-check-circle"></i> Sin alumnos en mora grave</td></tr>';
            }
            
            $html .= '</tbody></table>';
            
            echo json_encode([
                'success' => true,
                'html' => $html,
                'count' => $alertas ? $alertas->rowCount() : 0
            ], JSON_UNESCAPED_UNICODE);
            exit;
        
        case 'grafico_ingresos_rubro':
            /**
             * Retorna datos de ingresos por rubro para Chart.js
             * POST['periodo'] = período específico (opcional) ej: "Marzo / 2025"
             */
            $periodo = isset($_POST['periodo']) ? trim($_POST['periodo']) : null;
            $datos = $insDash->ingresosPorRubro($periodo);
            
            echo json_encode([
                'success' => true,
                'data' => $datos,
                'periodo' => $periodo ?? 'Últimos 12 meses'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        
        case 'ranking_asistencia':
            /**
             * Retorna ranking de asistencia de alumnos
             * POST['tipo'] = 'regulares' | 'faltones'
             * POST['limite'] = cantidad de resultados (default 10)
             */
            $tipo = isset($_POST['tipo']) && $_POST['tipo'] === 'faltones' ? 'ASC' : 'DESC';
            $limite = isset($_POST['limite']) ? intval($_POST['limite']) : 10;
            
            $ranking = $insDash->rankingAsistenciaAlumnos($tipo, $limite);
            $datos = [];
            
            while ($row = $ranking->fetch(\PDO::FETCH_ASSOC)) {
                $datos[] = [
                    'alumno' => $row['alumno_nombre'],
                    'sede' => $row['sede_nombre'],
                    'valor' => $tipo === 'ASC' 
                        ? intval($row['total_faltas']) 
                        : floatval($row['pct_asistencia'])
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $datos,
                'tipo' => $tipo === 'ASC' ? 'Faltones' : 'Regulares'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        
        /* ============================================================
           REPORTES AVANZADOS
           ============================================================ */
        
        case 'reporte_resumen_financiero':
            /**
             * Retorna resumen financiero detallado
             */
            $resumen = $insDash->resumenFinanciero();
            
            echo json_encode([
                'success' => true,
                'resumen' => [
                    'total_completado' => $resumen['total_completado'],
                    'total_pendiente' => $resumen['total_pendiente'],
                    'total_pagos' => $resumen['total_pagos'],
                    'alumnos_activos' => $resumen['alumnos_activos'],
                    'promedio_por_alumno' => $resumen['promedio_por_alumno'],
                    'tasa_conversion' => $resumen['total_pagos'] > 0 
                        ? round(($resumen['total_completado'] / ($resumen['total_completado'] + $resumen['total_pendiente'])) * 100, 2)
                        : 0
                ],
                'fecha_generacion' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Acción no válida o no especificada'
            ], JSON_UNESCAPED_UNICODE);
            exit;
    }
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => APP_URL ? $e->getMessage() : ''
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
