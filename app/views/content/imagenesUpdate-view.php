<?php
	use app\controllers\alumnoController;
	$insAlumno = new alumnoController();

	$alumnoid = isset($url[1]) ? $insAlumno->limpiarCadena($url[1]) : 0;
	$datos = $insAlumno->seleccionarDatos("Unico","sujeto_alumno","alumno_id",$alumnoid);
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?> | Actualizar imagen alumno</title>
	<link rel="icon" type="image/png" href="<?php echo APP_URL; ?>app/views/dist/img/Logos/LogoRojo.png">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/fontawesome-free/css/all.min.css">
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/css/adminlte.css">
	<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/css/sweetalert2.min.css">
	<script src="<?php echo APP_URL; ?>app/views/dist/js/sweetalert2.all.min.js"></script>
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
						<h1 class="m-0">Actualizar imagen del alumno</h1>
					</div>
					<div class="col-sm-6">
						<ol class="breadcrumb float-sm-right">
							<li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>imagenesList/">Imagenes</a></li>
							<li class="breadcrumb-item active">Actualizar foto</li>
						</ol>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">
				<?php
					if($datos->rowCount()==1){
						$datos = $datos->fetch();
						$foto = APP_URL.'app/views/imagenes/fotos/alumno/alumno.png';
						if(trim((string)$datos['alumno_imagen'])!=""){
							$foto = APP_URL.'app/views/imagenes/fotos/alumno/'.$datos['alumno_imagen'];
						}

						$sedeNombre = (string)$datos['alumno_sedeid'];
						$datosSede = $insAlumno->informacionSede($datos['alumno_sedeid']);
						if($datosSede->rowCount()==1){
							$infoSede = $datosSede->fetch();
							$sedeNombre = $infoSede['sede_nombre'];
						}
				?>

				<div class="card card-primary card-outline">
					<div class="card-header">
						<h3 class="card-title">Solo se permite actualizar la foto</h3>
					</div>

					<form class="FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/alumnoAjax.php" method="POST" autocomplete="off" enctype="multipart/form-data">
						<input type="hidden" name="modulo_alumno" value="actualizarImagen">
						<input type="hidden" name="alumno_id" value="<?php echo $datos['alumno_id']; ?>">

						<div class="card-body">
							<div class="row">
								<div class="col-md-4 text-center">
									<img id="preview_foto_alumno" src="<?php echo $foto; ?>" alt="Foto alumno" style="width: 220px; height: 260px; object-fit: cover; border: 1px solid #dee2e6; border-radius: 8px;" onerror="this.onerror=null;this.src='<?php echo APP_URL; ?>app/views/imagenes/fotos/alumno/alumno.png';">
									<div class="mt-3 text-left">
										<label for="alumno_foto">Nueva foto (JPG/PNG, max 4MB)</label>
										<input type="file" class="form-control-file" id="alumno_foto" name="alumno_foto" accept="image/jpeg,image/png" required>
									</div>
								</div>

								<div class="col-md-8">
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label>Sede</label>
												<input type="text" class="form-control" value="<?php echo $sedeNombre; ?>" readonly>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Fecha de nacimiento</label>
												<input type="text" class="form-control" value="<?php echo $datos['alumno_fechanacimiento']; ?>" readonly>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Nombres</label>
												<input type="text" class="form-control" value="<?php echo $datos['alumno_primernombre'].' '.$datos['alumno_segundonombre']; ?>" readonly>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Apellidos</label>
												<input type="text" class="form-control" value="<?php echo $datos['alumno_apellidopaterno'].' '.$datos['alumno_apellidomaterno']; ?>" readonly>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="card-footer">
							<button type="submit" class="btn btn-success btn-sm">Guardar foto</button>
							<a class="btn btn-dark btn-sm" href="#" onclick="cerrarVentana(); return false;">Regresar</a>
						</div>
					</form>
				</div>

				<?php
					}else{
						include "./app/views/inc/error_alert.php";
					}
				?>
			</div>
		</section>
      </div>

      <?php require_once "app/views/inc/footer.php"; ?>
      <aside class="control-sidebar control-sidebar-dark"></aside>
    </div>

	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jquery/jquery.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/js/adminlte.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/js/ajax.js"></script>
	<script>
		/**
		 * Función para actualizar la vista previa de la imagen
		 * Validación de tipo y tamaño de archivo
		 */
		document.getElementById('alumno_foto')?.addEventListener('change', function(e){
			const file = e.target.files && e.target.files[0];
			if(!file){ return; }
			
			// Validar tipo de archivo
			const tiposValidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
			if(!tiposValidos.includes(file.type)){
				alert('Por favor, seleccione una imagen válida (JPG, PNG, GIF o WebP)');
				this.value = '';
				return;
			}
			
			// Validar tamaño (4MB máximo)
			const maxSize = 4 * 1024 * 1024;
			if(file.size > maxSize){
				alert('La imagen no debe exceder 4MB');
				this.value = '';
				return;
			}
			
			// Crear preview
			const reader = new FileReader();
			reader.onload = function(ev){
				const preview = document.getElementById('preview_foto_alumno');
				if(preview){
					preview.src = ev.target.result;
				}
			};
			reader.readAsDataURL(file);
		});

		function cerrarVentana() {
			window.close();
		}
	</script>
  </body>
</html>
