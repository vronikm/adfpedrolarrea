<?php
	use app\controllers\dashboardController;
	$insDashboard = new dashboardController();

	$alumnosActivosSedeADFPL=$insDashboard->obtenerAlumnosActivosSedeADFPL();
	$alumnosActivosSedeCantera=$insDashboard->obtenerAlumnosActivosSedeCantera();

	$alumnosInactivosSedeADFPL=$insDashboard->obtenerAlumnosInactivosSedeADFPL();
	$alumnosInactivosSedeCantera=$insDashboard->obtenerAlumnosInactivosSedeCantera();

	$pagosCanceladoSedeADFPL=$insDashboard->obtenerPagosCancelados(1);	
	$pagosCanceladoSedeCantera=$insDashboard->obtenerPagosCancelados(2);	

	$pagosPendienteSedeADFPL=$insDashboard->obtenerPagosPendientes(1);
	$pagosPendienteSedeCantera=$insDashboard->obtenerPagosPendientes(2);

	$representantes=$insDashboard->obtenerRepresentantes();
	
	if($alumnosActivosSedeADFPL->rowCount()>0){
		$alumnosActivosSedeADFPL=$alumnosActivosSedeADFPL->fetch();
		$totalActivosSedeADFPL=$alumnosActivosSedeADFPL["totalActivosSedeADFPL"];
	}else{
		$totalActivosSedeADFPL= 0;
	}

	if($alumnosActivosSedeCantera->rowCount()>0){
		$alumnosActivosSedeCantera=$alumnosActivosSedeCantera->fetch();
		$totalActivosSedeCantera=$alumnosActivosSedeCantera["totalActivosSedeCantera"];
	}else{
		$totalActivosSedeCantera= 0;
	}

	if($alumnosInactivosSedeADFPL->rowCount()>0){
		$alumnosInactivosSedeADFPL=$alumnosInactivosSedeADFPL->fetch();
		$totalInactivosSedeADFPL=$alumnosInactivosSedeADFPL["totalInactivosSedeADFPL"];
	}else{
		$totalInactivosSedeADFPL= 0;
	}

	if($alumnosInactivosSedeCantera->rowCount()>0){
		$alumnosInactivosSedeCantera=$alumnosInactivosSedeCantera->fetch();
		$totalInactivosSedeCantera=$alumnosInactivosSedeCantera["totalInactivosSedeCantera"];
	}else{
		$totalInactivosSedeCantera= 0;
	}

	if($pagosCanceladoSedeADFPL->rowCount()>0){
		$pagosCanceladoSedeADFPL=$pagosCanceladoSedeADFPL->fetch();
		$totalCanceladoSedeADFPL=$pagosCanceladoSedeADFPL["totalCancelados"];
	}else{
		$totalCanceladoSedeADFPL= 0;
	}

	if($pagosCanceladoSedeCantera->rowCount()>0){
		$pagosCanceladoSedeCantera=$pagosCanceladoSedeCantera->fetch();
		$totalCanceladoSedeCantera=$pagosCanceladoSedeCantera["totalCancelados"];
	}else{
		$totalCanceladoSedeCantera= 0;
	}
	
	if($pagosPendienteSedeADFPL->rowCount()>0){
		$pagosPendienteSedeADFPL=$pagosPendienteSedeADFPL->fetch();
		$totalPendienteSedeADFPL=$pagosPendienteSedeADFPL["totalPendientes"];
	}else{
		$totalPendienteSedeADFPL= 0;
	}

	if($pagosPendienteSedeCantera->rowCount()>0){
		$pagosPendienteSedeCantera=$pagosPendienteSedeCantera->fetch();
		$totalPendienteSedeCantera=$pagosPendienteSedeCantera["totalPendientes"];
	}else{
		$totalPendienteSedeCantera= 0;
	}

	if($representantes->rowCount()>0){
		$representantes=$representantes->fetch();
		$totalRepresentantes=$representantes["totalRepresentantes"];
	}else{
		$totalRepresentantes= 0;
	}
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?>| Dashboard</title>
	<link rel="icon" type="image/png" href="<?php echo APP_URL; ?>app/views/dist/img/Logos/LogoRojo.png">
	<!-- Google Font: Source Sans Pro -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
	<!-- Font Awesome -->
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/fontawesome-free/css/all.min.css">
	<!-- Ionicons -->
	<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
	<!-- Tempusdominus Bootstrap 4 -->
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
	<!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
      integrity="sha256-9kPW/n5nn53j4WMRYAxe9c1rCY96Oogo/MKSVdKzPmI="
      crossorigin="anonymous"
    />
	<!-- iCheck -->
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
	<!-- JQVMap -->
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/jqvmap/jqvmap.min.css">
	<!-- Theme style -->
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/css/adminlte.css">

	<style>
		.icon-white {
			color: white;
			opacity: 0.4; /* 1 es totalmente visible, 0 es invisible */
		}
	</style>

  </head>
  <body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
      <!-- Navbar -->
      <?php require_once "app/views/inc/navbar.php"; ?>
      <!-- /.navbar -->

      <!-- Main Sidebar Container -->
      <?php require_once "app/views/inc/main-sidebar.php"; ?>
      <!-- /.Main Sidebar Container -->  

      <!-- vista -->
      <div class="content-wrapper">

        <!-- Content Header (Page header) -->
		<div class="content-header">
			<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
				<h1 class="m-0">Dashboard</h1>
				</div><!-- /.col -->
				<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="#">Inicio</a></li>
					<li class="breadcrumb-item active">Dashboard v1</li>
				</ol>
				</div><!-- /.col -->
			</div><!-- /.row -->
			</div><!-- /.container-fluid -->
		</div>
		<!-- /.content-header -->

		<!-- Main content -->
		<section class="content">
			<div class="container-fluid">
			<!-- Small boxes (Stat box) -->
				<div class="card card-default">
					<div class="card-header">
						<h3 class="card-title">CANCHA SINTÉTICA JIPIRO</h3>
						<div class="card-tools">
							<button type="button" class="btn btn-tool" data-card-widget="collapse">
								<i class="fas fa-minus"></i>
							</button>
						</div>
					</div>

					<div class="card-body">
						<div class="row">
							<!-- Alumnos activos -->
							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>dashboardAlumnos/1/A/" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-primary text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
										<i class="bi bi-people-fill fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Alumnos activos</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalActivosSedeADFPL; ?></span>
										</div>
									</div>
								</a>
							</div>

							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>reportePagos/1" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-success text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
										<i class="bi bi-check2-circle fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Pagos Receptados</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalCanceladoSedeADFPL; ?></span>
										</div>
									</div>
								</a>
							</div>

							<!-- Alumnos inactivos -->
							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>dashboardAlumnos/1/I/" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
											<i class="bi bi-person-x-fill fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Alumnos inactivos</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalInactivosSedeADFPL; ?></span>
										</div>
									</div>
								</a>
							</div>

							<!-- Alumnos con mora -->
							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>reportePendientes/1" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-danger text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
										<i class="bi bi-exclamation-triangle-fill fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Alumnos con mora</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalPendienteSedeADFPL; ?></span>
										</div>
									</div>
								</a>
							</div>
						</div>
						<!-- ./col -->
					</div>
				</div>				
				<div class="card card-default">
					<div class="card-header">
						<h3 class="card-title">LA CANTERA</h3>
						<div class="card-tools">
							<button type="button" class="btn btn-tool" data-card-widget="collapse">
								<i class="fas fa-minus"></i>
							</button>
						</div>
					</div>

					<div class="card-body">
						<div class="row">
							<!-- Alumnos activos -->
							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>dashboardAlumnos/2/A/" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-primary text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
										<i class="bi bi-people-fill fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Alumnos activos</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalActivosSedeCantera; ?></span>
										</div>
									</div>
								</a>
							</div>

							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>reportePagos/2" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-success text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
										<i class="bi bi-check2-circle fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Pagos Receptados</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalCanceladoSedeCantera; ?></span>
										</div>
									</div>
								</a>
							</div>

							<!-- Alumnos inactivos -->
							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>dashboardAlumnos/2/I/" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
											<i class="bi bi-person-x-fill fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Alumnos inactivos</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalInactivosSedeCantera; ?></span>
										</div>
									</div>
								</a>
							</div>

							<!-- Alumnos con mora -->
							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>reportePendientes/2" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-danger h6 text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
										<i class="bi bi-exclamation-triangle-fill fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Alumnos con mora</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalPendienteSedeCantera; ?></span>
										</div>
									</div>
								</a>
							</div>
						</div>
						<!-- ./col -->
					</div>
				</div>				
				<div class="card card-default">
					<div class="card-header">
						<h3 class="card-title">LA FLORESTA</h3>
						<div class="card-tools">
							<button type="button" class="btn btn-tool" data-card-widget="collapse">
								<i class="fas fa-minus"></i>
							</button>
						</div>
					</div>
					<div class="card-body">
						<div class="row">
							<!-- Alumnos activos -->
							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>dashboardAlumnos/2/A/" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-primary text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
										<i class="bi bi-people-fill fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Alumnos activos</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalActivosSedeCantera; ?></span>
										</div>
									</div>
								</a>
							</div>

							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>reportePagos/2" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-success text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
										<i class="bi bi-check2-circle fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Pagos Receptados</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalCanceladoSedeCantera; ?></span>
										</div>
									</div>
								</a>
							</div>

							<!-- Alumnos inactivos -->
							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>dashboardAlumnos/2/I/" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
											<i class="bi bi-person-x-fill fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Alumnos inactivos</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalInactivosSedeCantera; ?></span>
										</div>
									</div>
								</a>
							</div>

							<!-- Alumnos con mora -->
							<div class="col-md-3 mb-3">
								<a href="<?php echo APP_URL; ?>reportePendientes/2" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-danger text-white d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
										<i class="bi bi-exclamation-triangle-fill fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Alumnos con mora</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalPendienteSedeCantera; ?></span>
										</div>
									</div>
								</a>
							</div>
						</div>
						<!-- ./col -->
					</div>	
				</div>
				<div class="card card-default">
					<div class="card-header">
						<h3 class="card-title">REPRESENTANTES INGRESADOS</h3>
						<div class="card-tools">
							<button type="button" class="btn btn-tool" data-card-widget="collapse">
								<i class="fas fa-minus"></i>
							</button>
						</div>
					</div>
					<div class="card-body">
						<div class="row">
							<!-- Alumnos activos -->
							<div class="col-md-3 mb-3 align-items-center">
								<a href="<?php echo APP_URL; ?>representanteList/" class="text-decoration-none">
									<div class="info-box d-flex shadow-sm rounded border">
										<span class="info-box-icon bg-warning text-white d-flex align-items-center justify-content-center" style="width: 90px; height: 100px;">
										<i class="bi bi-people-fill fs-2"></i>
										</span>
										<div class="info-box-content ms-2">
											<span class="info-box-text h6 text-muted">Representantes activos</span>
											<span class="info-box-number h3 text-dark"><?php echo $totalRepresentantes; ?></span>
										</div>
									</div>
								</a>
							</div>
						</div>	
					</div>
				</div>
			</div><!-- /.container-fluid -->
		</section>
		<!-- /.content -->
      
      </div>
      <!-- /.vista -->

      <?php require_once "app/views/inc/footer.php"; ?>

      <!-- Control Sidebar -->
      <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
      </aside>
      <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    
	<!-- jQuery -->
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jquery/jquery.min.js"></script>
	<!-- jQuery UI 1.11.4 -->
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jquery-ui/jquery-ui.min.js"></script>
	<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->

	<!-- Bootstrap 4 -->
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
	<!-- ChartJS -->
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/chart.js/Chart.min.js"></script>
	<!-- Sparkline -->
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/sparklines/sparkline.js"></script>
	<!-- JQVMap -->
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jqvmap/jquery.vmap.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
	<!-- jQuery Knob Chart -->
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jquery-knob/jquery.knob.min.js"></script>
	<!-- daterangepicker -->
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/moment/moment.min.js"></script>
	<!-- Tempusdominus Bootstrap 4 -->
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
	<!-- AdminLTE App -->
	<script src="<?php echo APP_URL; ?>app/views/dist/js/adminlte.js"></script>

	<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
	<script src="<?php echo APP_URL; ?>app/views/dist/js/pages/dashboard.js"></script>

	<script src="<?php echo APP_URL; ?>app/views/js/ajax.js" ></script>
	<script src="<?php echo APP_URL; ?>app/views/js/main.js" ></script>	
	<script src="<?php echo APP_URL; ?>app/views/dist/js/sweetalert2.all.min.js" ></script>
	<script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
      integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy"
      crossorigin="anonymous"
    ></script>
	
  </body>
</html>