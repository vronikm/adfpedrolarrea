<?php

	namespace app\controllers;
	use app\models\mainModel;
	
	class loginController extends mainModel{

		/*----------  Controlador iniciar sesion  ----------*/
		public function iniciarSesionControlador(){

			$usuario=$this->limpiarCadena($_POST['login_usuario']);
		    $clave=$this->limpiarCadena($_POST['login_clave']);

		    # Verificando campos obligatorios #
		    if($usuario=="" || $clave==""){
		        echo "<script>
			        Swal.fire({
					  icon: 'error',
					  title: 'Ocurrió un error inesperado',
					  text: 'No has llenado todos los campos que son obligatorios'
					});
				</script>";
		    }else{

			    # Verificando integridad de los datos #
			    if($this->verificarDatos("[a-zA-Z0-9]{4,20}",$usuario)){
			        echo "<script>
				        Swal.fire({
						  icon: 'error',
						  title: 'Ocurrió un error inesperado',
						  text: 'El USUARIO no coincide con el formato solicitado'
						});
					</script>";
			    }else{

			    	# Verificando integridad de los datos #
				    if($this->verificarDatos("[a-zA-Z0-9$@.-]{7,100}",$clave)){
				        echo "<script>
					        Swal.fire({
							  icon: 'error',
							  title: 'Ocurrió un error inesperado',
							  text: 'La CLAVE no coincide con el formato solicitado'
							});
						</script>";
				    }else{

					    # Verificando usuario #
					    $check_usuario=$this->ejecutarConsulta("SELECT usuario_empleadoid, empleado_identificacion, empleado_nombre, empleado_correo, empleado_celular, 
																				empleado_foto, sede_nombre AS Sede, usuario_estado, usuario_cambiaclave, usuario_usuario, 
																				usuario_fechacreacion, usuario_fechaactualizado, usuario_rolid, usuario_clave, 	usuario_tienebloqueo
																				FROM seguridad_usuario																				
																					LEFT JOIN sujeto_empleado on empleado_id = usuario_empleadoid
																					LEFT JOIN general_sede ON empleado_sedeid = sede_id
																					WHERE usuario_usuario ='$usuario'");

					    if($check_usuario->rowCount()==1){

					    	$check_usuario=$check_usuario->fetch();

							if($check_usuario['usuario_estado']=='I'){
								echo "<script>
							        Swal.fire({
									  icon: 'error',
									  title: 'Usuario inactivo',
									  text: 'Contacte al administrador del sistema'
									});
								</script>";
							}elseif ($check_usuario['usuario_tienebloqueo']=='S'){
								echo "<script>
							        Swal.fire({
									  icon: 'error',
									  title: 'Usuario bloqueado',
									  text: 'Contacte al administrador del sistema'
									});
								</script>";
							}else{

								if($check_usuario['usuario_usuario']==$usuario && password_verify($clave,$check_usuario['usuario_clave'])){

									$_SESSION['usuario']=$check_usuario['usuario_usuario'];					           
									$_SESSION['rol']=$check_usuario['usuario_rolid'];					           
									$_SESSION['foto']=$check_usuario['empleado_foto'];
									$_SESSION['sede']=$check_usuario['Sede'];
									$_SESSION['identificacion']=$check_usuario['empleado_identificacion'];
									$_SESSION['usuario_id']=$check_usuario['usuario_empleadoid'];
									

									if ($check_usuario['empleado_nombre']==""){
										$_SESSION['nombre']=$check_usuario['usuario_rolid'];
									}else{
										$_SESSION['nombre']=$check_usuario['empleado_nombre'];
									}
									
									if ($_SESSION['rol'] <> 1 && $_SESSION['rol'] <> 2  && $_SESSION['rol'] <> 4 && $_SESSION['rol'] <> 6){
										if(headers_sent()){
											echo "<script> window.location.href='".APP_URL."empleadoEntrada/'; </script>";
										}else{
											header("Location: ".APP_URL."empleadoEntrada/");
										} 
									}else if($_SESSION['rol'] == 4){
										header("Location: ".APP_URL."representanteList/");
									} 
									else{
										if(headers_sent()){
											echo "<script> window.location.href='".APP_URL."dashboard/'; </script>";
										}else{
											header("Location: ".APP_URL."dashboard/");
										}
									}
								}else{
									echo "<script>
										Swal.fire({
										icon: 'error',
										title: 'Ocurrió un error inesperado',
										text: 'Usuario o clave incorrectos'
										});
									</script>";
								}
							}

					    }else{
					        echo "<script>
						        Swal.fire({
								  icon: 'error',
								  title: 'Ocurrió un error inesperado',
								  text: 'Usuario no existe'
								});
							</script>";
					    }
				    }
			    }
		    }
		}


		/*----------  Controlador cerrar sesion  ----------*/
		public function cerrarSesionControlador(){

			session_destroy();

		    if(headers_sent()){
                echo "<script> window.location.href='".APP_URL."login/'; </script>";
            }else{
                header("Location: ".APP_URL."login/");
            }
		}

	}