<?php
	/*
		Inserta una imagen en un PDF de FPDF leyendola del disco (ruta relativa a la raiz del proyecto).

		No usar APP_URL: si FPDF pide la imagen por HTTP y el servidor responde 403 (o el archivo
		no existe), FPDF lanza un error fatal y el recibo no se genera. Si $ruta no es valida se
		intenta con $alternativa; si tampoco existe, la imagen se omite y el PDF continua.
	*/
	if(!function_exists('pdfImagenLocal')){
		function pdfImagenLocal($pdf, $ruta, $x, $y, $w = 0, $h = 0, $alternativa = null){
			foreach([$ruta, $alternativa] as $archivo){
				if(!empty($archivo) && is_file($archivo)){
					try{
						$pdf->Image($archivo, $x, $y, $w, $h);
						return true;
					}catch(\Exception $e){
						// Imagen corrupta o formato no soportado: se prueba la alternativa
					}
				}
			}
			return false;
		}
	}
