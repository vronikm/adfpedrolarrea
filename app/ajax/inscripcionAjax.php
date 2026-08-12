<?php

	require_once "../../config/app.php";
	require_once "../views/inc/session_start.php";
	require_once "../../autoload.php";

	use app\controllers\inscripcionController;

	/* El token de inscripción se firma con la clave del sistema: solo un
	   usuario autenticado puede pedir que se genere uno. */
	if(empty($_SESSION['usuario'])){
		http_response_code(401);
		echo json_encode([
			"tipo"   => "simple",
			"titulo" => "Sesión expirada",
			"texto"  => "Vuelva a iniciar sesión para generar enlaces de inscripción.",
			"icono"  => "error"
		]);
		exit();
	}

	if(isset($_POST['modulo_inscripcion'])){

		$insInscripcion = new inscripcionController();

		if($_POST['modulo_inscripcion']=="generar_enlace"){
			echo $insInscripcion->generarEnlaceControlador();
		}

	}else{
		session_destroy();
		header("Location: ".APP_URL."login/");
	}
