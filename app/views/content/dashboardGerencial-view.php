<?php
	use app\controllers\dashboardGerencialController;
	$insDash = new dashboardGerencialController();
	
	// Obtener datos para el dashboard
	$totalAlumnosActivos   = $insDash->totalAlumnosActivos()->fetch()['totalAlumnosActivos'];
	$totalAlumnosInactivos = $insDash->totalAlumnosInactivos()->fetch()['totalAlumnosInactivos'];
	$totalRepresentantes   = $insDash->obtenerRepresentantes()->fetch()['totalRepresentantes'];
	$pagosPendientes       = $insDash->obtenerTotalPagosPendientes()->fetch()['totalPendientes'];
	$pagosCancelados       = $insDash->obtenerTotalPagosCancelados()->fetch()['totalCancelados'];
	$alumnosNuevosMes      = $insDash->obtenerAlumnosNuevosMes()->fetch()['nuevosEsteMes'];
	$alumnosRetiradosMes   = $insDash->obtenerAlumnosRetiradosMes()->fetch()['retiradosEsteMes'];

	// Indicadores avanzados
	$tasaMortalidad        = $insDash->tasaMortalidad();
	$tasaCrecimiento       = $insDash->tasaCrecimientoMensual();
	$promedioPago          = $insDash->promedioDiasPago();
	$resumenFin            = $insDash->resumenFinanciero();
	$ingresosPorRubro      = $insDash->ingresosPorRubro();

	// Datos para gráficos (JSON)
	$alumnosPorSede         = json_encode($insDash->alumnosPorSede());
	$ingresosPorMes         = json_encode($insDash->ingresosPorMes());
	$ingresosPorRubroJson   = json_encode($ingresosPorRubro);
	$alumnosNuevosPorMes    = json_encode($insDash->alumnosIngresosPorMes());
	$alumnosRetiradosPorMes = json_encode($insDash->alumnosRetiradosPorMes());
	$asistenciaEmpleados    = json_encode($insDash->asistenciaEmpleados());
	$rankingAsistencia      = $insDash->rankingAsistenciaAlumnos('ASC',  10);  // más faltones
	$rankingPuntuales       = $insDash->rankingAsistenciaAlumnos('DESC', 10);  // más regulares
	$alertasMora            = $insDash->alumnosEnMora(3); // 3+ meses sin pagar
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?> | Dashboard Gerencial</title>
	<link rel="icon" type="image/png" href="<?php echo APP_URL; ?>app/views/dist/img/Logos/LogoRojo.png">

	<!-- Google Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

	<!-- Font Awesome -->
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/fontawesome-free/css/all.min.css">
	<!-- AdminLTE -->
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/css/adminlte.css">
	<!-- Chart.js -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

	<style>
		/* ============================================================
		   DIGIFUTBOL — DASHBOARD GERENCIAL
		   Aesthetic: Sala de control deportiva · Dark · Verde cancha
		   ============================================================ */

		:root {
			--verde:      #00e676;
			--verde-dim:  #00c853;
			--verde-glow: rgba(0, 230, 118, 0.18);
			--rojo:       #ff4444;
			--rojo-dim:   #d32f2f;
			--amarillo:   #ffca28;
			--azul:       #29b6f6;
			--bg-base:    #0d0f14;
			--bg-card:    #13161e;
			--bg-card2:   #1a1e28;
			--border:     rgba(255,255,255,0.07);
			--text-main:  #e8eaf0;
			--text-muted: #6b7280;
			--font-display: 'Barlow Condensed', sans-serif;
			--font-body:    'DM Sans', sans-serif;
		}

		/* Override AdminLTE content wrapper background */
		.content-wrapper {
			background: var(--bg-base) !important;
			font-family: var(--font-body);
			color: var(--text-main);
		}

		/* ---- Page header ---- */
		.dash-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 28px 28px 0;
			flex-wrap: wrap;
			gap: 12px;
		}
		.dash-header-left h1 {
			font-family: var(--font-display);
			font-size: 2.6rem;
			font-weight: 800;
			letter-spacing: 1px;
			color: #fff;
			line-height: 1;
			margin: 0;
		}
		.dash-header-left h1 span { color: var(--verde); }
		.dash-header-left p {
			font-size: 0.82rem;
			color: var(--text-muted);
			margin: 4px 0 0;
			text-transform: uppercase;
			letter-spacing: 2px;
		}
		.dash-timestamp {
			font-family: var(--font-display);
			font-size: 0.95rem;
			color: var(--text-muted);
			letter-spacing: 1px;
		}
		.dash-timestamp span {
			color: var(--verde);
			font-weight: 700;
		}

		/* ---- Grid container ---- */
		.dash-body { padding: 24px 28px; }

		/* ---- KPI Cards ---- */
		.kpi-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
			gap: 14px;
			margin-bottom: 24px;
		}
		.kpi-card {
			background: var(--bg-card);
			border: 1px solid var(--border);
			border-radius: 14px;
			padding: 20px 18px 16px;
			position: relative;
			overflow: hidden;
			transition: transform .2s, box-shadow .2s;
		}
		.kpi-card:hover {
			transform: translateY(-3px);
			box-shadow: 0 8px 32px rgba(0,0,0,.4);
		}
		.kpi-card::before {
			content: '';
			position: absolute;
			top: 0; left: 0; right: 0;
			height: 3px;
			background: var(--accent, var(--verde));
			border-radius: 14px 14px 0 0;
		}
		.kpi-card .kpi-icon {
			width: 40px; height: 40px;
			border-radius: 10px;
			display: flex; align-items: center; justify-content: center;
			font-size: 1.1rem;
			background: var(--icon-bg, rgba(0,230,118,.12));
			color: var(--accent, var(--verde));
			margin-bottom: 14px;
		}
		.kpi-card .kpi-value {
			font-family: var(--font-display);
			font-size: 2.4rem;
			font-weight: 800;
			line-height: 1;
			color: #fff;
			letter-spacing: -1px;
		}
		.kpi-card .kpi-label {
			font-size: 0.73rem;
			color: var(--text-muted);
			text-transform: uppercase;
			letter-spacing: 1.5px;
			margin-top: 4px;
		}
		.kpi-card .kpi-delta {
			font-size: 0.78rem;
			margin-top: 8px;
			display: flex; align-items: center; gap: 4px;
		}
		.kpi-card.green  { --accent: var(--verde);    --icon-bg: rgba(0,230,118,.12); }
		.kpi-card.red    { --accent: var(--rojo);     --icon-bg: rgba(255,68,68,.12); }
		.kpi-card.yellow { --accent: var(--amarillo); --icon-bg: rgba(255,202,40,.12); }
		.kpi-card.blue   { --accent: var(--azul);     --icon-bg: rgba(41,182,246,.12); }

		/* ---- Charts / Panels ---- */
		.charts-row {
			display: grid;
			gap: 14px;
			margin-bottom: 24px;
		}
		.charts-row.two   { grid-template-columns: 1fr 1fr; }
		.charts-row.three { grid-template-columns: 2fr 1fr; }

		.panel {
			background: var(--bg-card);
			border: 1px solid var(--border);
			border-radius: 14px;
			padding: 22px;
			display: flex;
			flex-direction: column;
		}
		.panel-title {
			font-family: var(--font-display);
			font-size: 1.1rem;
			font-weight: 700;
			letter-spacing: .5px;
			color: #fff;
			margin: 0 0 4px;
		}
		.panel-sub {
			font-size: 0.75rem;
			color: var(--text-muted);
			margin: 0 0 18px;
			text-transform: uppercase;
			letter-spacing: 1px;
		}
		.panel canvas {
			max-height: 260px;
		}

		/* ---- Alert badges ---- */
		.alert-mora {
			background: var(--bg-card);
			border: 1px solid var(--border);
			border-left: 3px solid var(--rojo);
			border-radius: 14px;
			padding: 22px;
			margin-bottom: 24px;
		}
		.alert-mora .panel-title { color: var(--rojo); }
		.mora-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
		.mora-table thead tr th {
			font-size: 0.7rem;
			text-transform: uppercase;
			letter-spacing: 1.5px;
			color: var(--text-muted);
			padding: 6px 10px;
			text-align: left;
			border-bottom: 1px solid var(--border);
		}
		.mora-table tbody tr td {
			font-size: 0.82rem;
			color: var(--text-main);
			padding: 8px 10px;
			border-bottom: 1px solid rgba(255,255,255,.04);
		}
		.mora-table tbody tr:last-child td { border-bottom: none; }
		.badge-mora {
			display: inline-block;
			background: rgba(255,68,68,.15);
			color: var(--rojo);
			font-size: 0.7rem;
			font-weight: 600;
			padding: 2px 8px;
			border-radius: 20px;
			border: 1px solid rgba(255,68,68,.3);
		}
		.badge-ok {
			background: rgba(0,230,118,.12);
			color: var(--verde);
			border-color: rgba(0,230,118,.3);
		}

		/* ---- Ranking tables ---- */
		.rank-table { width: 100%; border-collapse: collapse; }
		.rank-table tbody tr td {
			font-size: 0.82rem;
			color: var(--text-main);
			padding: 7px 8px;
			border-bottom: 1px solid rgba(255,255,255,.04);
		}
		.rank-table tbody tr:last-child td { border-bottom: none; }
		.rank-num {
			font-family: var(--font-display);
			font-size: 1rem;
			font-weight: 700;
			color: var(--text-muted);
			width: 28px;
		}
		.rank-bar-wrap {
			height: 6px;
			background: rgba(255,255,255,.07);
			border-radius: 10px;
			margin-top: 3px;
		}
		.rank-bar {
			height: 6px;
			border-radius: 10px;
			background: var(--bar-color, var(--verde));
		}
		.rank-pct {
			font-size: 0.75rem;
			color: var(--text-muted);
			white-space: nowrap;
		}

		/* ---- Divider line ---- */
		.section-divider {
			font-family: var(--font-display);
			font-size: 0.75rem;
			letter-spacing: 3px;
			text-transform: uppercase;
			color: var(--text-muted);
			display: flex; align-items: center; gap: 12px;
			margin: 4px 0 16px;
		}
		.section-divider::after {
			content: '';
			flex: 1;
			height: 1px;
			background: var(--border);
		}

		/* ---- Sede comparison bar ---- */
		.sede-row {
			display: flex; align-items: center; gap: 10px;
			margin-bottom: 10px;
		}
		.sede-name {
			font-size: 0.8rem;
			color: var(--text-main);
			width: 120px;
			flex-shrink: 0;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.sede-bar-outer {
			flex: 1;
			height: 8px;
			background: rgba(255,255,255,.07);
			border-radius: 10px;
			overflow: hidden;
		}
		.sede-bar-inner {
			height: 8px;
			border-radius: 10px;
			background: var(--verde);
			transition: width 1s ease;
		}
		.sede-count {
			font-family: var(--font-display);
			font-size: 1rem;
			font-weight: 700;
			color: #fff;
			width: 38px;
			text-align: right;
		}

		/* ---- Responsive ---- */
		@media (max-width: 900px) {
			.charts-row.two,
			.charts-row.three { grid-template-columns: 1fr; }
			.dash-header { padding: 18px 16px 0; }
			.dash-body   { padding: 16px; }
		}
	</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

	<?php require_once "app/views/inc/navbar.php"; ?>
	<?php require_once "app/views/inc/main-sidebar.php"; ?>

	<div class="content-wrapper">

		<!-- ===== HEADER ===== -->
		<div class="dash-header">
			<div class="dash-header-left">
				<h1>DIGI<span>FÚTBOL</span> · GERENCIA</h1>
				<p>Resumen ejecutivo · Todas las sedes</p>
			</div>
			<div class="dash-timestamp">
				Actualizado: <span><?php echo date('d/m/Y H:i'); ?></span>
			</div>
		</div>

		<!-- ===== BODY ===== -->
		<div class="dash-body">

			<!-- KPI ROW -->
			<div class="section-divider">Indicadores clave</div>
			<div class="kpi-grid">

				<div class="kpi-card green">
					<div class="kpi-icon"><i class="fas fa-users"></i></div>
					<div class="kpi-value"><?php echo number_format($totalAlumnosActivos); ?></div>
					<div class="kpi-label">Alumnos activos</div>
				</div>

				<div class="kpi-card red">
					<div class="kpi-icon"><i class="fas fa-user-slash"></i></div>
					<div class="kpi-value"><?php echo number_format($totalAlumnosInactivos); ?></div>
					<div class="kpi-label">Alumnos inactivos</div>
				</div>

				<div class="kpi-card yellow">
					<div class="kpi-icon"><i class="fas fa-user-plus"></i></div>
					<div class="kpi-value"><?php echo number_format($alumnosNuevosMes); ?></div>
					<div class="kpi-label">Ingresos este mes</div>
				</div>

				<div class="kpi-card red">
					<div class="kpi-icon"><i class="fas fa-user-minus"></i></div>
					<div class="kpi-value"><?php echo number_format($alumnosRetiradosMes); ?></div>
					<div class="kpi-label">Retiros este mes</div>
				</div>

				<div class="kpi-card blue">
					<div class="kpi-icon"><i class="fas fa-handshake"></i></div>
					<div class="kpi-value"><?php echo number_format($totalRepresentantes); ?></div>
					<div class="kpi-label">Representantes</div>
				</div>

				<div class="kpi-card green">
					<div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
					<div class="kpi-value"><?php echo number_format($pagosCancelados); ?></div>
					<div class="kpi-label">Pagos cancelados</div>
				</div>

				<div class="kpi-card red">
					<div class="kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
					<div class="kpi-value"><?php echo number_format($pagosPendientes); ?></div>
					<div class="kpi-label">Pagos pendientes</div>
				</div>

				<div class="kpi-card yellow">
					<div class="kpi-icon"><i class="fas fa-percentage"></i></div>
					<div class="kpi-value"><?php echo number_format($tasaMortalidad, 1); ?>%</div>
					<div class="kpi-label">Tasa de baja anual</div>
				</div>

				<div class="kpi-card blue">
					<div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
					<div class="kpi-value"><?php 
						$signo = $tasaCrecimiento >= 0 ? '+' : '';
						echo $signo . number_format($tasaCrecimiento, 1) . '%';
					?></div>
					<div class="kpi-label">Crecimiento mes</div>
				</div>

				<div class="kpi-card yellow">
					<div class="kpi-icon"><i class="fas fa-hourglass-half"></i></div>
					<div class="kpi-value"><?php echo number_format($promedioPago, 0); ?> d</div>
					<div class="kpi-label">Días promedio pago</div>
				</div>

				<div class="kpi-card green">
					<div class="kpi-icon"><i class="fas fa-wallet"></i></div>
					<div class="kpi-value">$<?php echo number_format($resumenFin['total_completado'] / 1000, 1); ?>k</div>
					<div class="kpi-label">Ingresos 12 meses</div>
				</div>

			</div><!-- /.kpi-grid -->

			<!-- ALERTAS DE MORA -->
			<div class="section-divider">Alertas de cartera</div>
			<div class="alert-mora">
				<div class="panel-title"><i class="fas fa-bell" style="margin-right:8px;"></i>Alumnos en mora · 3 o más meses sin pago</div>
				<div class="panel-sub">requieren gestión de cobranza inmediata</div>
				<div style="overflow-x:auto;">
					<table class="mora-table">
						<thead>
							<tr>
								<th>Identificación</th>
								<th>Nombre</th>
								<th>Sede</th>
								<th>Meses sin pago</th>
								<th>Saldo vencido</th>
								<th>Estado</th>
							</tr>
						</thead>
						<tbody>
							<?php
								if($alertasMora && $alertasMora->rowCount() > 0){
									foreach($alertasMora as $row){
										$meses = intval($row['meses_mora']);
										$badge = $meses >= 6 ? 'badge-mora' : 'badge-mora';
										echo '<tr>
											<td>'.$row['alumno_identificacion'].'</td>
											<td>'.$row['alumno_nombre'].'</td>
											<td>'.$row['sede_nombre'].'</td>
											<td style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--rojo);">'.$meses.'</td>
											<td style="color:var(--amarillo);font-weight:600;">$ '.number_format($row['saldo_total'],2).'</td>
											<td><span class="badge-mora">'.$row['alumno_estado'].'</span></td>
										</tr>';
									}
								} else {
									echo '<tr><td colspan="6" style="color:var(--verde);text-align:center;padding:16px 0;">
										<i class="fas fa-check-circle"></i> Sin alumnos en mora grave
									</td></tr>';
								}
							?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- GRÁFICOS FILA 1: Ingresos por mes + Ingresos por rubro -->
			<div class="section-divider">Tendencias financieras y matrícula</div>
			<div class="charts-row two">

				<div class="panel">
					<div class="panel-title">Ingresos por mes</div>
					<div class="panel-sub">12 meses · todas las sedes</div>
					<canvas id="chartIngresos"></canvas>
				</div>

				<div class="panel">
					<div class="panel-title">Ingresos por tipo de rubro</div>
					<div class="panel-sub">composición actual</div>
					<canvas id="chartRubros"></canvas>
				</div>

			</div>

			<!-- GRÁFICOS FILA 2: Alumnos por sede + Movimiento -->
			<div class="charts-row two">

				<div class="panel">
					<div class="panel-title">Alumnos activos por sede</div>
					<div class="panel-sub">comparativa actual</div>
					<div id="sedeBarsContainer" style="margin-top:8px;"></div>
				</div>

				<div class="panel">
					<div class="panel-title">Movimiento de alumnos</div>
					<div class="panel-sub">ingresos vs retiros mensual</div>
					<canvas id="chartMovimiento"></canvas>
				</div>

			</div>

				<div class="panel">
					<div class="panel-title">Ingresos vs Retiros de alumnos</div>
					<div class="panel-sub">por mes · último año</div>
					<canvas id="chartMovimiento"></canvas>
				</div>

				<div class="panel">
					<div class="panel-title">Asistencia de empleados</div>
					<div class="panel-sub">marcaciones último mes</div>
					<canvas id="chartAsistenciaEmp"></canvas>
				</div>

			</div>

			<!-- RANKINGS ASISTENCIA -->
			<div class="section-divider">Asistencia de alumnos</div>
			<div class="charts-row two">

				<div class="panel">
					<div class="panel-title" style="color:var(--verde);">
						<i class="fas fa-star" style="margin-right:6px;"></i>Top 10 · Más regulares
					</div>
					<div class="panel-sub">mayor porcentaje de asistencia</div>
					<table class="rank-table">
						<tbody>
							<?php
								$maxAsist = null;
								$rowsRegular = $rankingPuntuales ? $rankingPuntuales->fetchAll() : [];
								if(!empty($rowsRegular)) $maxAsist = $rowsRegular[0]['pct_asistencia'] ?? 100;
								foreach($rowsRegular as $i => $row){
									$pct = floatval($row['pct_asistencia']);
									$w   = $maxAsist > 0 ? round(($pct/$maxAsist)*100) : 0;
									echo '<tr>
										<td class="rank-num">'.($i+1).'</td>
										<td>
											<div>'.$row['alumno_nombre'].'<br>
											<span style="font-size:.7rem;color:var(--text-muted);">'.$row['sede_nombre'].'</span></div>
											<div class="rank-bar-wrap">
												<div class="rank-bar" style="width:'.$w.'%;--bar-color:var(--verde);"></div>
											</div>
										</td>
										<td class="rank-pct">'.$pct.'%</td>
									</tr>';
								}
								if(empty($rowsRegular)) echo '<tr><td colspan="3" style="color:var(--text-muted);text-align:center;padding:16px 0;">Sin datos</td></tr>';
							?>
						</tbody>
					</table>
				</div>

				<div class="panel">
					<div class="panel-title" style="color:var(--rojo);">
						<i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>Top 10 · Más inasistencias
					</div>
					<div class="panel-sub">alumnos que requieren seguimiento</div>
					<table class="rank-table">
						<tbody>
							<?php
								$maxFaltas = null;
								$rowsFaltones = $rankingAsistencia ? $rankingAsistencia->fetchAll() : [];
								if(!empty($rowsFaltones)) $maxFaltas = $rowsFaltones[0]['total_faltas'] ?? 1;
								foreach($rowsFaltones as $i => $row){
									$faltas = intval($row['total_faltas']);
									$w      = $maxFaltas > 0 ? round(($faltas/$maxFaltas)*100) : 0;
									echo '<tr>
										<td class="rank-num">'.($i+1).'</td>
										<td>
											<div>'.$row['alumno_nombre'].'<br>
											<span style="font-size:.7rem;color:var(--text-muted);">'.$row['sede_nombre'].'</span></div>
											<div class="rank-bar-wrap">
												<div class="rank-bar" style="width:'.$w.'%;--bar-color:var(--rojo);"></div>
											</div>
										</td>
										<td class="rank-pct" style="color:var(--rojo);">'.$faltas.' faltas</td>
									</tr>';
								}
								if(empty($rowsFaltones)) echo '<tr><td colspan="3" style="color:var(--text-muted);text-align:center;padding:16px 0;">Sin datos</td></tr>';
							?>
						</tbody>
					</table>
				</div>

			</div><!-- /.charts-row -->

		</div><!-- /.dash-body -->
	</div><!-- /.content-wrapper -->

	<?php require_once "app/views/inc/footer.php"; ?>
	<aside class="control-sidebar control-sidebar-dark"></aside>
</div><!-- /.wrapper -->

<!-- jQuery + AdminLTE -->
<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jquery/jquery.min.js"></script>
<script src="<?php echo APP_URL; ?>app/views/dist/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo APP_URL; ?>app/views/dist/js/adminlte.min.js"></script>

<script>
/* ============================================================
   Datos desde PHP → JS
   ============================================================ */
const dataIngresos      = <?php echo $ingresosPorMes; ?>;
const dataRubros        = <?php echo $ingresosPorRubroJson; ?>;
const dataMovimiento    = <?php echo $alumnosNuevosPorMes; ?>;
const dataRetirados     = <?php echo $alumnosRetiradosPorMes; ?>;
const dataAsistenciaEmp = <?php echo $asistenciaEmpleados; ?>;
const dataSedes         = <?php echo $alumnosPorSede; ?>;

/* ---- Paleta compartida ---- */
const VERDE   = '#00e676';
const ROJO    = '#ff4444';
const AZUL    = '#29b6f6';
const AMARILLO= '#ffca28';
const MUTED   = 'rgba(255,255,255,0.08)';
const GRID    = 'rgba(255,255,255,0.06)';

Chart.defaults.color          = '#6b7280';
Chart.defaults.borderColor    = GRID;
Chart.defaults.font.family    = "'DM Sans', sans-serif";

/* ---- Helper: opciones base ---- */
function baseOpts(extra) {
	return Object.assign({
		responsive: true,
		maintainAspectRatio: true,
		plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1a1e28', borderColor: 'rgba(255,255,255,.1)', borderWidth: 1 } },
		scales: {
			x: { grid: { color: GRID }, ticks: { color: '#6b7280' } },
			y: { grid: { color: GRID }, ticks: { color: '#6b7280' } }
		}
	}, extra);
}

/* ============================================================
   CHART 1 · Ingresos por mes
   dataIngresos: [{mes: 'Ene', total: 1200}, ...]
   ============================================================ */
new Chart(document.getElementById('chartIngresos'), {
	type: 'bar',
	data: {
		labels: dataIngresos.map(d => d.mes),
		datasets: [{
			label: 'Ingresos $',
			data:  dataIngresos.map(d => parseFloat(d.total)),
			backgroundColor: dataIngresos.map((d, i) =>
				i === dataIngresos.length - 1 ? VERDE : 'rgba(0,230,118,0.25)'),
			borderColor: VERDE,
			borderWidth: 1,
			borderRadius: 6,
		}]
	},
	options: {
		...baseOpts(),
		plugins: {
			legend: { display: false },
			tooltip: {
				backgroundColor: '#1a1e28',
				callbacks: { label: ctx => ' $ ' + ctx.parsed.y.toFixed(2) }
			}
		},
		scales: {
			x: { grid: { display: false }, ticks: { color: '#6b7280' } },
			y: { grid: { color: GRID }, ticks: { color: '#6b7280', callback: v => '$'+v } }
		}
	}
});

/* ============================================================
   CHART 1.5 · Ingresos por rubro (donut chart)
   dataRubros: [{rubro: 'RPE - Pensión', total: 5000, cantidad: 50}, ...]
   ============================================================ */
new Chart(document.getElementById('chartRubros'), {
	type: 'doughnut',
	data: {
		labels: dataRubros.map(d => d.rubro),
		datasets: [{
			label: 'Ingresos por rubro',
			data: dataRubros.map(d => parseFloat(d.total)),
			backgroundColor: [VERDE, AZUL, AMARILLO, ROJO],
			borderColor: '#0f1419',
			borderWidth: 2,
		}]
	},
	options: {
		...baseOpts(),
		plugins: {
			legend: {
				display: true,
				position: 'bottom',
				labels: { color: '#9ca3af', padding: 12, font: { size: 11 } }
			},
			tooltip: {
				backgroundColor: '#1a1e28',
				callbacks: { label: ctx => {
					const dataset = ctx.dataset.data[ctx.dataIndex];
					return ' $ ' + parseFloat(dataset).toFixed(2);
				}}
			}
		}
	}
});

/* ============================================================
   CHART 2 · Alumnos por sede (barras inline HTML)
   dataSedes: [{sede: 'Centro', activos: 45}, ...]
   ============================================================ */
(function buildSedeBars() {
	const container = document.getElementById('sedeBarsContainer');
	if (!dataSedes.length) { container.innerHTML = '<p style="color:var(--text-muted)">Sin datos</p>'; return; }
	const max = Math.max(...dataSedes.map(d => parseInt(d.activos)));
	container.innerHTML = dataSedes.map(d => {
		const pct = max > 0 ? Math.round((d.activos / max) * 100) : 0;
		return `<div class="sede-row">
			<div class="sede-name" title="${d.sede}">${d.sede}</div>
			<div class="sede-bar-outer">
				<div class="sede-bar-inner" style="width:${pct}%"></div>
			</div>
			<div class="sede-count">${d.activos}</div>
		</div>`;
	}).join('');
})();

/* ============================================================
   CHART 3 · Ingresos vs Retiros de alumnos por mes
   dataMovimiento: [{mes:'Ene', ingresos:8}, ...]
   dataRetirados:  [{mes:'Ene', retirados:2}, ...]
   ============================================================ */
new Chart(document.getElementById('chartMovimiento'), {
	type: 'line',
	data: {
		labels: dataMovimiento.map(d => d.mes),
		datasets: [
			{
				label: 'Ingresos',
				data:  dataMovimiento.map(d => parseInt(d.cantidad)),
				borderColor: VERDE,
				backgroundColor: 'rgba(0,230,118,0.08)',
				borderWidth: 2,
				tension: 0.4,
				fill: true,
				pointBackgroundColor: VERDE,
				pointRadius: 4,
			},
			{
				label: 'Retiros',
				data:  dataRetirados.map(d => parseInt(d.cantidad)),
				borderColor: ROJO,
				backgroundColor: 'rgba(255,68,68,0.06)',
				borderWidth: 2,
				tension: 0.4,
				fill: true,
				pointBackgroundColor: ROJO,
				pointRadius: 4,
			}
		]
	},
	options: {
		...baseOpts(),
		plugins: {
			legend: {
				display: true,
				labels: { color: '#9ca3af', boxWidth: 12, font: { size: 11 } }
			},
			tooltip: { backgroundColor: '#1a1e28' }
		},
		scales: {
			x: { grid: { display: false }, ticks: { color: '#6b7280' } },
			y: { grid: { color: GRID }, ticks: { color: '#6b7280', stepSize: 1 } }
		}
	}
});

/* ============================================================
   CHART 4 · Asistencia empleados (barras horizontales)
   dataAsistenciaEmp: [{empleado:'Juan P.', marcaciones:22}, ...]
   ============================================================ */
new Chart(document.getElementById('chartAsistenciaEmp'), {
	type: 'bar',
	data: {
		labels: dataAsistenciaEmp.map(d => d.empleado),
		datasets: [{
			label: 'Marcaciones',
			data:  dataAsistenciaEmp.map(d => parseInt(d.marcaciones)),
			backgroundColor: 'rgba(41,182,246,0.25)',
			borderColor: AZUL,
			borderWidth: 1,
			borderRadius: 4,
		}]
	},
	options: {
		...baseOpts(),
		indexAxis: 'y',
		plugins: {
			legend: { display: false },
			tooltip: { backgroundColor: '#1a1e28' }
		},
		scales: {
			x: { grid: { color: GRID }, ticks: { color: '#6b7280' } },
			y: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } } }
		}
	}
});
</script>

</body>
</html>