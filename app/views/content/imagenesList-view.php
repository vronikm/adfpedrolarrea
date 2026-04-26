<?php
	use app\controllers\alumnoController;
	$insAlumno = new alumnoController();
	$rolid = isset($_SESSION['rol']) ? (int)$_SESSION['rol'] : 0;

	if(isset($_POST['alumno_sedeid'])){
		$alumno_sedeid = $insAlumno->limpiarCadena($_POST['alumno_sedeid']);
	}else{
		$alumno_sedeid = "";
	}

	if(isset($_POST['alumno_identificacion'])){
		$alumno_identificacion = $insAlumno->limpiarCadena($_POST['alumno_identificacion']);
	}else{
		$alumno_identificacion = "";
	}

	if(isset($_POST['alumno_nombre1'])){
		$alumno_primernombre = $insAlumno->limpiarCadena($_POST['alumno_nombre1']);
	}else{
		$alumno_primernombre = "";
	}

	if(isset($_POST['alumno_apellido1'])){
		$alumno_apellidopaterno = $insAlumno->limpiarCadena($_POST['alumno_apellido1']);
	}else{
		$alumno_apellidopaterno = "";
	}

	if(isset($_POST['alumno_anio'])){
		$alumno_anio = $insAlumno->limpiarCadena($_POST['alumno_anio']);
	}else{
		$alumno_anio = "";
	}
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?> | Imagenes</title>
	<link rel="icon" type="image/png" href="<?php echo APP_URL; ?>app/views/dist/img/Logos/LogoRojo.png">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/fontawesome-free/css/all.min.css">
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/css/adminlte.css">
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/css/sweetalert2.min.css">
	<script src="<?php echo APP_URL; ?>app/views/dist/js/sweetalert2.all.min.js" ></script>
  </head>
  <body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
      <?php require_once "app/views/inc/navbar.php"; ?>
      <?php require_once "app/views/inc/main-sidebar.php"; ?>

      <div class="content-wrapper">

		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-6">
						<h1 class="m-0">Modulo Imagenes</h1>
					</div>
					<div class="col-sm-6">
						<ol class="breadcrumb float-sm-right">
							<li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>dashboard/">Inicio</a></li>
							<li class="breadcrumb-item active">Imagenes</li>
						</ol>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<form action="<?php echo APP_URL."imagenesList/" ?>" method="POST" autocomplete="off" enctype="multipart/form-data" >
				<div class="container-fluid">
					<div class="card card-default">
						<div class="card-header">
							<h3 class="card-title">Criterios de busqueda</h3>
							<div class="card-tools">
								<button type="button" class="btn btn-tool" data-card-widget="collapse">
									<i class="fas fa-minus"></i>
								</button>
							</div>
						</div>

						<div class="card-body">
							<div class="row">
								<div class="col-sm-2">
									<div class="form-group">
										<label for="alumno_identificacion">Identificacion</label>
										<input type="text" class="form-control" id="alumno_identificacion" name="alumno_identificacion" placeholder="Identificacion" value="<?php echo $alumno_identificacion; ?>">
									</div>
								</div>
								<div class="col-sm-2">
									<div class="form-group">
										<label for="alumno_apellido1">Apellido paterno</label>
										<input type="text" class="form-control" id="alumno_apellido1" name="alumno_apellido1" placeholder="Primer apellido" value="<?php echo $alumno_apellidopaterno; ?>">
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label for="alumno_nombre1">Primer nombre</label>
										<input type="text" class="form-control" id="alumno_nombre1" name="alumno_nombre1" placeholder="Primer nombre" value="<?php echo $alumno_primernombre; ?>">
									</div>
								</div>

								<div class="col-md-2">
									<div class="form-group">
										<label for="alumno_anio">Anio</label>
										<input type="text" class="form-control" id="alumno_anio" name="alumno_anio" placeholder="Anio" value="<?php echo $alumno_anio; ?>">
									</div>
								</div>

								<div class="col-md-2">
									<div class="form-group">
										<label for="alumno_sedeid">Sede</label>
										<select class="form-control select2" id="alumno_sedeid" name="alumno_sedeid">
											<?php
												if($rolid == 1 || $rolid == 2){
													if($alumno_sedeid == 0){
														echo "<option value='0' selected='selected'>Todas</option>";
													}else{
														echo "<option value='0'>Todas</option>";
													}
												}
											?>
											<?php echo $insAlumno->listarSedebusqueda($alumno_sedeid, $_SESSION['rol'], $_SESSION['usuario']); ?>
										</select>
									</div>
								</div>

								<div class="col-md-2">
									<div class="form-group">
										<label for="btn_buscar_imagenes">.</label>
										<button id="btn_buscar_imagenes" type="submit" class="form-control btn btn-info">Buscar</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>

			<div class="container-fluid">
				<div class="card card-default">
					<div class="card-header">
						<h3 class="card-title">Resultado de la busqueda</h3>
						<div class="card-tools">
							<button type="button" class="btn btn-tool" data-card-widget="collapse">
								<i class="fas fa-minus"></i>
							</button>
						</div>
					</div>

					<div class="card-body">
						<table id="example1" class="table table-bordered table-striped table-sm">
							<thead>
								<tr>
									<th style="width: 90px;">Foto</th>
									<th>Nombres y apellidos</th>
									<th>Fecha de nacimiento</th>
									<th style="width: 260px;">Opciones</th>
								</tr>
							</thead>
							<tbody>
								<?php
									echo $insAlumno->listarImagenesAlumnos(
										$alumno_identificacion,
										$alumno_apellidopaterno,
										$alumno_primernombre,
										$alumno_anio,
										$alumno_sedeid
									);
								?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</section>
      </div>

      <?php require_once "app/views/inc/footer.php"; ?>

      <aside class="control-sidebar control-sidebar-dark"></aside>
    </div>

	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jquery/jquery.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables/jquery.dataTables.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jszip/jszip.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/pdfmake/pdfmake.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/pdfmake/vfs_fonts.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/buttons.print.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/js/adminlte.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/js/ajax.js" ></script>

	<script>
	$(function () {
		$("#example1").DataTable({
			"order": [[1, "asc"]],
			"responsive": true,
			"lengthChange": false,
			"autoWidth": false,
			"language": {
				"decimal": "",
				"emptyTable": "No hay datos disponibles en la tabla",
				"info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
				"infoEmpty": "Mostrando 0 a 0 de 0 entradas",
				"infoFiltered": "(filtrado de _MAX_ entradas totales)",
				"lengthMenu": "Mostrar _MENU_ entradas",
				"loadingRecords": "Cargando...",
				"processing": "Procesando...",
				"search": "Buscar:",
				"zeroRecords": "No se encontraron registros coincidentes",
				"paginate": {
					"first": "Primero",
					"last": "Ultimo",
					"next": "Siguiente",
					"previous": "Anterior"
				}
			},
			"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
		}).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
	});
	</script>
  </body>
</html>
