<?php
	use app\controllers\asistenciaController;
	$insAlumno = new asistenciaController();

	$horario_id = ($url[1] != "") ? $url[1] : 0;

	if($horario_id != 0){
		$horarioSede=$insAlumno->BuscarHorarioSede($horario_id);		
		if($horarioSede->rowCount()==1){
			$horarioSede	=	$horarioSede->fetch();				
		}
	}
	
	
	$fechahoy = date('Y-m-d');	
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?>| Alumnos </title>
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
		<nav class="main-header navbar navbar-expand navbar-white navbar-light">
			<!-- Left navbar links -->
			<ul class="navbar-nav">
				<li class="nav-item">
					<a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
				</li>
				<form action="<?php echo APP_URL."asistenciaAlumno/$horario_id" ?>" method="POST" autocomplete="off" enctype="multipart/form-data" >
				<div class="container-fluid">
				<?php 
					
						$fechahoy = date('Y-m-d');	
											echo "La fecha de hoy es: ".$fechahoy;
									
										?>	
				</div>
				</form>	
				
			</ul>

			 <!-- Right navbar links -->
		</nav>
	
    	<!--?php require_once "app/views/inc/navbar.php"; ?-->
      	<!-- /.navbar -->

		<!-- Main Sidebar Container -->
		<?php require_once "app/views/inc/main-sidebar.php"; ?>
		<!-- /.Main Sidebar Container -->  

      <!-- vista -->
      <div class="content-wrapper">
		
		<!-- Main content -->
		<section class="content">
			<div class="container-fluid">
			<!-- Small boxes (Stat box) -->
				<div class="card card-default">
					

					<div class="card-body card-comments">
						
					
		                  
						<table id="example1" class="table table-bordered table-striped table-sm ">
							<thead>
								<tr>
									<th>Categoría</th>
									<th>Nombres</th>									
									<th>Asistencia</th>	
								</tr>
							</thead>
							<tbody>
								<?php 
																				
										echo $insAlumno->ListadoAlumnos($horarioSede["horario_id"],$fechahoy);		
																		
								?>								
							</tbody>	
						</table>	
						<div class="card-footer">
							<!--a href="<?php echo APP_URL.'equipoList/'.$horarioSede["horario_id"].'/'; ?>" class="btn btn-dark btn-sm">Regresar</a-->
							<button class="btn btn-dark btn-back btn-sm" onclick="cerrarPestana()">Regresar</button>
						</div>
					</div>					

				</div>
			<!-- /.row -->
			</div><!-- /.container-fluid -->
		</section>
		<!-- /.content -->
      
      </div>
      <!-- /.vista -->

      <?php require_once "app/views/inc/footer.php"; ?>

      <!-- Control Sidebar -->
      <aside class="control-sidebar control-sidebar-dark">
				
      </aside>
      <!-- /.control-sidebar -->
    </div>
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
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/pdfmake/pdfmake.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/pdfmake/vfs_fonts.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/buttons.print.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
	<!-- AdminLTE App -->
	<script src="<?php echo APP_URL; ?>app/views/dist/js/adminlte.min.js"></script>
	
	<!-- Page specific script -->
	<script>
		$(function () {
			$("#example1").DataTable({
				"responsive": true,
				"lengthChange": false, // Deshabilitar el cambio de longitud
				"autoWidth": false,
				"paging": false, // Deshabilitar la paginación
				"searching": false, // Habilitar la búsqueda
				"ordering": false, // Desactiva la opción de ordenar
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
			}).buttons().container().appendTo('#example1_wrapper .col-md-5:eq(0)');
		});
	</script>


	<script type="text/javascript">
		function cerrarPestana() {
			window.close();
		}
    </script>

	<script src="<?php echo APP_URL; ?>app/views/dist/js/ajax.js" ></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/js/main.js" ></script>
    
  </body>
</html>








