<?php
/**
 * Genera la tarjeta de cumpleaños como imagen JPEG.
 * Ruta: cumpleaniosTarjeta/{alumno_id}/
 *
 * La foto del alumno se coloca en el rectángulo blanco de la plantilla marcocp.jpeg.
 * Las proporciones del rectángulo se definen abajo y pueden ajustarse si cambia la plantilla.
 */
	ob_start();
	use app\controllers\cumpleaniosController;

	$insCumple = new cumpleaniosController();

	// Validar ID
	$alumno_id = isset($url[1]) ? (int)$insCumple->limpiarCadena($url[1]) : 0;
	if ($alumno_id <= 0) {
		http_response_code(400);
		exit('ID de alumno no válido.');
	}

	// Obtener datos del alumno
	$datos = $insCumple->infoAlumno($alumno_id);
	if ($datos->rowCount() !== 1) {
		http_response_code(404);
		exit('Alumno no encontrado.');
	}
	$alumno = $datos->fetch();

	// ── Rutas físicas ──────────────────────────────────────────
	$base_path    = realpath(__DIR__ . '/../../..');
	$marco_path   = $base_path . '/app/views/imagenes/cumples/marco.png';
	$foto_dir     = $base_path . '/app/views/imagenes/fotos/alumno/';
	$default_foto = $foto_dir  . 'alumno.png';

	// ── Cargar plantilla ───────────────────────────────────────
	if (!file_exists($marco_path)) {
		http_response_code(500);
		exit('Plantilla no encontrada.');
	}
	$marco = imagecreatefrompng($marco_path);
	if (!$marco) {
		http_response_code(500);
		exit('No se pudo cargar la plantilla.');
	}
	imagesavealpha($marco, true);
	$marco_w = imagesx($marco);
	$marco_h = imagesy($marco);

	// ── Lienzo base (la foto irá aquí, el marco encima) ────────
	$canvas = imagecreatetruecolor($marco_w, $marco_h);
	$bg = imagecolorallocate($canvas, 255, 255, 255);
	imagefilledrectangle($canvas, 0, 0, $marco_w - 1, $marco_h - 1, $bg);

	// ── Proporciones del rectángulo blanco en marcocp.jpeg ─────
	// Ajustar estos valores si se cambia la plantilla.
	// Coordenadas del interior blanco (sin el borde amarillo).
		$rect_x = (int)($marco_w * 0.204);   // fallback: plantilla anterior
	$rect_y = (int)($marco_h * 0.298);
	$rect_w = (int)($marco_w * 0.588);
	$rect_h = (int)($marco_h * 0.482);

	// Detectar automaticamente la ventana transparente del marco.
	$alpha_threshold = 110; // GD alpha: 0 opaco, 127 transparente.
	$min_x = $marco_w;
	$min_y = $marco_h;
	$max_x = -1;
	$max_y = -1;
	$transparent_count = 0;

	for ($y = 0; $y < $marco_h; $y++) {
		for ($x = 0; $x < $marco_w; $x++) {
			$rgba = imagecolorat($marco, $x, $y);
			$alpha = ($rgba & 0x7F000000) >> 24;

			if ($alpha >= $alpha_threshold) {
				$transparent_count++;
				if ($x < $min_x) { $min_x = $x; }
				if ($y < $min_y) { $min_y = $y; }
				if ($x > $max_x) { $max_x = $x; }
				if ($y > $max_y) { $max_y = $y; }
			}
		}
	}

	if ($transparent_count > 0) {
		$det_w = $max_x - $min_x + 1;
		$det_h = $max_y - $min_y + 1;

		// Ignorar detecciones no validas.
		if ($det_w > (int)($marco_w * 0.20) && $det_h > (int)($marco_h * 0.20)) {
			$pad_x = max(1, (int)round($det_w * 0.005));
			$pad_y = max(1, (int)round($det_h * 0.005));

			$rect_x = $min_x + $pad_x;
			$rect_y = $min_y + $pad_y;
			$rect_w = max(10, $det_w - ($pad_x * 2));
			$rect_h = max(10, $det_h - ($pad_y * 2));
		}
	}

	// ── Cargar foto del alumno ─────────────────────────────────
	$foto_path = $default_foto;
	$foto_nombre = isset($alumno['alumno_imagen']) ? trim((string)$alumno['alumno_imagen']) : '';

	if ($foto_nombre !== '') {
		$foto_candidata = $foto_dir . basename($foto_nombre);
		if (is_file($foto_candidata)) {
			$foto_path = $foto_candidata;
		}
	}

	$cargarImagen = function ($path) {
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		if ($ext === 'jpg' || $ext === 'jpeg') { return @imagecreatefromjpeg($path); }
		if ($ext === 'png')                    { return @imagecreatefrompng($path);  }
		if ($ext === 'gif')                    { return @imagecreatefromgif($path);  }
		return null;
	};

	$foto_src = is_file($foto_path) ? $cargarImagen($foto_path) : null;

	if (!$foto_src && is_file($default_foto)) {
		$foto_src = $cargarImagen($default_foto);
	}

	// ── Encajar la foto en el rectángulo blanco ────────────────
	if ($foto_src) {
		$foto_orig_w = imagesx($foto_src);
		$foto_orig_h = imagesy($foto_src);

		// Centro-recortar la foto para que coincida con la relación de aspecto del rectángulo
		$aspecto_rect = $rect_w / $rect_h;
		$aspecto_foto = $foto_orig_w / $foto_orig_h;

		if ($aspecto_foto > $aspecto_rect) {
			// La foto es más ancha que el rectángulo: recortar laterales
			$src_h = $foto_orig_h;
			$src_w = (int)($foto_orig_h * $aspecto_rect);
			$src_x = (int)(($foto_orig_w - $src_w) / 2);
			$src_y = 0;
		} else {
			// La foto es más alta que el rectángulo: recortar arriba/abajo
			$src_w = $foto_orig_w;
			$src_h = (int)($foto_orig_w / $aspecto_rect);
			$src_x = 0;
			$src_y = (int)(($foto_orig_h - $src_h) / 2);
		}

		// Dibujar la foto en el lienzo base (detrás del marco)
		imagecopyresampled(
			$canvas, $foto_src,
			$rect_x, $rect_y,       // destino: esquina superior-izquierda del rectángulo
			$src_x,  $src_y,        // origen: inicio del recorte
			$rect_w, $rect_h,       // tamaño destino
			$src_w,  $src_h         // tamaño origen (recortado)
		);

		imagedestroy($foto_src);
	}

	// ── Nombre del alumno sobre la franja inferior de la foto ──
		$nombre = trim($alumno['alumno_primernombre'] . ' ' . $alumno['alumno_segundonombre'])
		. ' ' .
		trim($alumno['alumno_apellidopaterno'] . ' ' . $alumno['alumno_apellidomaterno']);

	// Buscar fuente TrueType: primero fuentes incluidas en el proyecto,
	// luego rutas comunes de hosting Linux/Unix y finalmente Windows local.
	$font_paths = [
		$base_path . '/app/views/dist/fonts/OpenSans-Bold.ttf',
		$base_path . '/app/views/dist/fonts/OpenSans-Regular.ttf',
		'/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
		'/usr/share/fonts/dejavu-sans-fonts/DejaVuSans-Bold.ttf',
		'/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
		'/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
		'/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
		'/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
		'/usr/share/fonts/liberation/LiberationSans-Bold.ttf',
		'/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
		'/usr/share/fonts/gnu-free/FreeSansBold.ttf',
		'/usr/share/fonts/truetype/ubuntu-font-family/Ubuntu-B.ttf',
		'/usr/share/fonts/ubuntu/Ubuntu-B.ttf',
		'C:/Windows/Fonts/arialbd.ttf',
		'C:/Windows/Fonts/arial.ttf',
	];
	$font_file = null;
	foreach ($font_paths as $fp) {
		if (file_exists($fp)) { $font_file = $fp; break; }
	}

	// Recuadro de nombre bajo "Feliz Cumpleanos" (contorno blanco).
	$name_box_x = (int)($marco_w * 0.40);
	$name_box_y = (int)($marco_h * 0.885);
	$name_box_w = (int)($marco_w * 0.55);
	$name_box_h = (int)($marco_h * 0.06);

	// Detectar automaticamente el recuadro blanco inferior.
	$scan_start_y = (int)($marco_h * 0.75);
	$scan_end_y   = (int)($marco_h * 0.985);
	$min_run_len  = (int)($marco_w * 0.32);
	$run_rows = [];

	for ($y = $scan_start_y; $y <= $scan_end_y; $y++) {
		$best_start = -1;
		$best_end = -1;
		$run_start = -1;
		$run_len = 0;
		$best_len = 0;

		for ($x = 0; $x < $marco_w; $x++) {
			$rgba = imagecolorat($marco, $x, $y);
			$r = ($rgba >> 16) & 0xFF;
			$g = ($rgba >> 8) & 0xFF;
			$b = $rgba & 0xFF;
			$a = ($rgba & 0x7F000000) >> 24;
			$is_white = ($a < 12 && $r > 232 && $g > 232 && $b > 232);

			if ($is_white) {
				if ($run_start < 0) { $run_start = $x; }
				$run_len++;
			} elseif ($run_start >= 0) {
				if ($run_len > $best_len) {
					$best_len = $run_len;
					$best_start = $run_start;
					$best_end = $x - 1;
				}
				$run_start = -1;
				$run_len = 0;
			}
		}
		if ($run_start >= 0 && $run_len > $best_len) {
			$best_len = $run_len;
			$best_start = $run_start;
			$best_end = $marco_w - 1;
		}

		if ($best_len >= $min_run_len && $best_start > (int)($marco_w * 0.15)) {
			$run_rows[] = ['y' => $y, 'x1' => $best_start, 'x2' => $best_end];
		}
	}

	if (!empty($run_rows)) {
		$top = $run_rows[0];
		$bottom = $top;
		$tol_x = (int)($marco_w * 0.08);

		foreach ($run_rows as $row) {
			if (abs($row['x1'] - $top['x1']) <= $tol_x && abs($row['x2'] - $top['x2']) <= $tol_x) {
				$bottom = $row;
			}
		}

		if (($bottom['y'] - $top['y']) >= 18) {
			$inset_x = max(8, (int)round($marco_w * 0.008));
			$inset_y = max(5, (int)round($marco_h * 0.004));
			$left = min($top['x1'], $bottom['x1']);
			$right = max($top['x2'], $bottom['x2']);

			$name_box_x = $left + $inset_x;
			$name_box_w = max(60, ($right - $left + 1) - ($inset_x * 2));
			$name_box_y = $top['y'] + $inset_y;
			$name_box_h = max(28, ($bottom['y'] - $top['y'] + 1) - ($inset_y * 2));
		}
	}

	// Superponer el marco encima de la foto.
	imagealphablending($canvas, true);
	imagecopy($canvas, $marco, 0, 0, 0, 0, $marco_w, $marco_h);
	imagedestroy($marco);

	// Dibujar nombre centrado dentro del recuadro blanco inferior.
	$color_blanco = imagecolorallocate($canvas, 255, 255, 255);
	$color_sombra = imagecolorallocatealpha($canvas, 0, 0, 0, 80);

	if ($font_file) {
		$fs_nombre = max(14, (int)($name_box_h * 0.58));
		$bbox_n = imagettfbbox($fs_nombre, 0, $font_file, $nombre);
		$tw_n = abs($bbox_n[2] - $bbox_n[0]);

		while ($tw_n > ($name_box_w - 8) && $fs_nombre > 11) {
			$fs_nombre--;
			$bbox_n = imagettfbbox($fs_nombre, 0, $font_file, $nombre);
			$tw_n = abs($bbox_n[2] - $bbox_n[0]);
		}

		$text_h = abs($bbox_n[7] - $bbox_n[1]);
		$tx_n = $name_box_x + (int)(($name_box_w - $tw_n) / 2);
		$ty_n = $name_box_y + (int)(($name_box_h + $text_h) / 2) - 2;

		imagettftext($canvas, $fs_nombre, 0, $tx_n + 1, $ty_n + 1, $color_sombra, $font_file, $nombre);
		imagettftext($canvas, $fs_nombre, 0, $tx_n, $ty_n, $color_blanco, $font_file, $nombre);
	} else {
		$gd_font = 5;
		$tw = strlen($nombre) * imagefontwidth($gd_font);
		$tx = $name_box_x + (int)(($name_box_w - $tw) / 2);
		$ty = $name_box_y + (int)(($name_box_h - imagefontheight($gd_font)) / 2);
		imagestring($canvas, $gd_font, $tx + 1, $ty + 1, $nombre, $color_sombra);
		imagestring($canvas, $gd_font, $tx, $ty, $nombre, $color_blanco);
	}
	// ── Enviar imagen al navegador ─────────────────────────────
	ob_end_clean();
	header('Content-Type: image/jpeg');
	header('Content-Disposition: inline; filename="cumpleanios_' . $alumno_id . '.jpg"');
	header('Cache-Control: max-age=3600');
	imagejpeg($canvas, null, 92);
	imagedestroy($canvas);
	exit;
