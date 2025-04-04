<?php
	use app\controllers\jugadorController;
	$insJugador = new jugadorController();	

	$equipo_torneoid = ($url[1] != "") ? $url[1] : 0;
	$equipo_id 		 = ($url[2] != "") ? $url[2] : 0;
	
	$modulo_equipo	= '';

	if($equipo_id != 0){
		$nombreEquipo=$insJugador->BuscarEquipo($equipo_id);		
		if($nombreEquipo->rowCount()==1){
			$nombreEquipo	=	$nombreEquipo->fetch();				
			$equipo_nombre	= 	$nombreEquipo['equipo_nombre'];		
			$equipo_categoria	= $nombreEquipo['equipo_categoria'];		
		}
	}else{
		$equipo_nombre 		= '';
		$equipo_categoria	= '';
	}
	
	if(isset($_POST['alumno_sedeid'])){
		$alumno_sedeid = $insJugador->limpiarCadena($_POST['alumno_sedeid']);
	} ELSE{
		$alumno_sedeid = "";
	}

	if(isset($_POST['alumno_identificacion'])){
		$alumno_identificacion = $insJugador->limpiarCadena($_POST['alumno_identificacion']);
	} ELSE{
		$alumno_identificacion = "";
	}

	if(isset($_POST['alumno_nombre1'])){
		$alumno_primernombre = $insJugador->limpiarCadena($_POST['alumno_nombre1']);
	} ELSE{
		$alumno_primernombre = "";
	}

	if(isset($_POST['alumno_apellido1'])){
		$alumno_apellidopaterno = $insJugador->limpiarCadena($_POST['alumno_apellido1']);
	} ELSE{
		$alumno_apellidopaterno = "";
	}
	
	if(isset($_POST['alumno_ano'])){
		$alumno_anio = $insJugador->limpiarCadena($_POST['alumno_ano']);
	} ELSE{
		$alumno_anio = "";
	}	
?>


<html lang="es">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?php echo APP_NAME; ?>| Jugadores</title>
		<link rel="icon" type="image/png" href="<?php echo APP_URL; ?>app/views/dist/img/Logos/LogoRojo.png">
		<!-- Google Font: Source Sans Pro -->
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
		<!-- Font Awesome -->
		<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/fontawesome-free/css/all.min.css">
		<!-- DataTables -->
		<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
		<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
		<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
		<!-- Theme style -->
		<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/css/adminlte.css">


		<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/css/sweetalert2.min.css">
		<script src="<?php echo APP_URL; ?>app/views/dist/js/sweetalert2.all.min.js" ></script>

	</head>
	<body class="hold-transition sidebar-mini layout-fixed">
		<div class="wrapper">
			<!-- Preloader -->
			<!--?php require_once "app/views/inc/preloader.php"; ?-->
			<!-- /.Preloader -->

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
								<h4 class="m-0">Asignación jugadores Equipo <?php echo $equipo_nombre .' - '.$equipo_categoria; ?></h4>
							</div><!-- /.col -->
							<div class="col-sm-6">
								<ol class="breadcrumb float-sm-right">
									<li class="breadcrumb-item"><a href="#">Nuevo</a></li>
									<li class="breadcrumb-item active">Dashboard v1</li>
								</ol>
							</div><!-- /.col -->
						</div><!-- /.row -->
					</div><!-- /.container-fluid -->
				</div>
				<!-- /.content-header -->

				<!-- Section listado de alumnos -->
				<section class="content">					
					<div class="container-fluid">
						<form action="<?php echo APP_URL."jugadorNew/".$equipo_torneoid."/".$equipo_id."/" ?>" method="POST" autocomplete="off" enctype="multipart/form-data" >					
							<div class="card card-default">
								<div class="card-header" style='height: 40px;'>
									<h3 class="card-title">Búsqueda de alumnos</h3>
									<div class="card-tools">
										<button type="button" style='height: 40px;' class="btn btn-tool" data-card-widget="collapse">
										<i class="fas fa-minus"></i>
										</button>
									</div>
								</div>  

								<!-- card-body -->                
								<div class="card-body">
									<div class="row" style='font-size: 14px; height: 60px;'>
										<div class="col-sm-2">
											<div class="form-group">
												<label for="alumno_identificacion">Identificación</label>                        
												<input type="text" class="form-control" style='font-size: 13px; height: 31px;' id="alumno_identificacion" name="alumno_identificacion" placeholder="Identificación" value="<?php echo $alumno_identificacion; ?>">
											</div>        
										</div>
										<div class="col-sm-2">
											<div class="form-group">
												<label for="alumno_apellido1">Apellido paterno</label>
												<input type="text" class="form-control" style='font-size: 13px; height: 31px;' id="alumno_apellido1" name="alumno_apellido1" placeholder="Primer apellido" value="<?php echo $alumno_apellidopaterno; ?>">
											</div>         
										</div>
										<div class="col-md-2">
											<div class="form-group">
												<label for="alumno_nombre1">Primer nombre</label>
												<input type="text" class="form-control" style='font-size: 13px; height: 31px;' id="alumno_nombre1" name="alumno_nombre1" placeholder="Primer nombre" value="<?php echo $alumno_primernombre; ?>">
											</div>
										</div>  

										<div class="col-md-2">
											<div class="form-group">
												<div class="form-group">
													<label for="alumno_ano">Año</label>
													<input type="text" class="form-control" style='font-size: 13px; height: 31px;' id="alumno_ano" name="alumno_ano" placeholder="año" value="<?php echo $alumno_anio; ?>">
												</div>	
											</div>
										</div>
										<div class="col-md-2">
											<div class="form-group">
												<label for="alumno_sedeid">Sede</label>
												<select class="form-control select2" style='font-size: 13px; height: 31px;' id="alumno_sedeid" name="alumno_sedeid">
													<?php
														if($alumno_sedeid == 0){	
															echo "<option value='0' selected='selected'>Todas</option>";
														}else{
															echo "<option value='0'>Todas</option>";	
														}
													?>																		
													<?php echo $insJugador->listarSedebusqueda($alumno_sedeid); ?>
												</select>	
											</div>
										</div>

										<div class="col-md-2">
											<div class="form-group">
												<label for="alumno_sedeid">.</label>
												<button type="submit" style='font-size: 13px; height: 31px;' class="form-control btn btn-info">Buscar</button>
											</div>
										</div>
									</div>					
								</div>
							</div>
						</form>

						<div class="card card-default">
							<div class="card-header" style='height: 40px;'>
								<h3 class="card-title">Resultado de la búsqueda</h3>
								<div class="card-tools">							
									<button type="button" style='height: 40px;'class="btn btn-tool" data-card-widget="collapse">
										<i class="fas fa-minus"></i>
									</button>
								</div>
							</div>							
						
							<div class="card-body">
								<table id="example1" class="table table-bordered table-striped table-sm" style="font-size: 14px;">
									<thead>
										<tr>
											<th>Identificación</th>
											<th>Nombres y Apellidos</th>
											<th>Año</th>		
											<th>Posición de juego</th>							
											<th>Tipo</th>
											<th></th>	
										</tr>
									</thead>
									<tbody>
										<?php 												
											echo $insJugador->listarAlumnos($equipo_torneoid, $equipo_id, $alumno_identificacion,$alumno_apellidopaterno, $alumno_primernombre, $alumno_anio, $alumno_sedeid, $equipo_categoria); 												
										?>								
									</tbody>
								</table>	
							</div>
							<div class="card-footer">		
								<a href="<?php echo APP_URL.'equipoList/'.$equipo_torneoid.'/'; ?>" class="btn btn-dark btn-sm">Regresar</a>														
							</div>				
							
						</div>	

					</div>					
				</section>
				
			</div><!-- /.container-fluid -->


			<?php require_once "app/views/inc/footer.php"; ?>

			<!-- Control Sidebar -->
			<aside class="control-sidebar control-sidebar-dark">
			<!-- Control sidebar content goes here -->
			</aside>
			<!-- /.control-sidebar -->

		</div>
			
		<!-- fin -->

		<!-- ./wrapper -->
		<!-- jQuery -->
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jquery/jquery.min.js"></script>
		<!-- Bootstrap 4 -->
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
		<!-- DataTables  & Plugins -->
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables/jquery.dataTables.min.js"></script>
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jszip/jszip.min.js"></script>
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/buttons.print.min.js"></script>
		<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
		<!-- AdminLTE App -->
		<script src="<?php echo APP_URL; ?>app/views/dist/js/adminlte.min.js"></script>

		<!-- Page specific script -->
		<script>
			$(function () {
				$("#example1").DataTable({
				"responsive": true, "lengthChange": false, "autoWidth": false,
				"language": {
					"decimal": "",
					"emptyTable": "No hay datos disponibles en la tabla",
					"info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
					"infoEmpty": "Mostrando 0 a 0 de 0 entradas",
					"infoFiltered": "(filtrado de _MAX_ entradas totales)",
					"infoPostFix": "",
					"thousands": ",",
					"lengthMenu": "Mostrar _MENU_ entradas",
					"loadingRecords": "Cargando...",
					"processing": "Procesando...",
					"search": "Buscar:",
					"zeroRecords": "No se encontraron registros coincidentes",
					"paginate": {
						"first": "Primero",
						"last": "Último",
						"next": "Siguiente",
						"previous": "Anterior"
					},
					"aria": {
						"sortAscending": ": activar para ordenar la columna ascendente",
						"sortDescending": ": activar para ordenar la columna descendente"
					}
				},
				}).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');			    
			});
		</script>

		<script src="<?php echo APP_URL; ?>app/views/dist/js/ajax.js" ></script>
		<script src="<?php echo APP_URL; ?>app/views/dist/js/main.js" ></script>

	</body>
</html>








