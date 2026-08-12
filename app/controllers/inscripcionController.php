<?php

namespace app\controllers;

use app\models\mainModel;
use app\models\TokenHelper;

class inscripcionController extends mainModel
{
    /**
     * Genera el enlace de inscripción para una sede.
     */
    public function generarEnlaceControlador()
    {
        $sede_id = intval($this->limpiarCadena($_POST['sede_id'] ?? '0'));
        $horas   = intval($this->limpiarCadena($_POST['horas_vigencia'] ?? '72'));

        if ($sede_id <= 0) {
            return json_encode([
                'tipo'   => 'simple',
                'titulo' => 'Error',
                'texto'  => 'Debe seleccionar una sede.',
                'icono'  => 'error'
            ]);
        }

        if ($horas < 1 || $horas > 720) {
            $horas = 72;
        }

        $expiraEnSegundos = $horas * 3600;

        /* El usuario solo puede generar enlaces de las sedes que tiene asignadas.
           Se valida contra la BD y no contra el <select>, que es manipulable. */
        $sedeNombre = $this->obtenerSedePermitida($sede_id);

        if ($sedeNombre === null) {
            return json_encode([
                'tipo'   => 'simple',
                'titulo' => 'Sede no permitida',
                'texto'  => 'La sede seleccionada no existe o no está asignada a su usuario.',
                'icono'  => 'error'
            ]);
        }

        $urlFormulario  = TokenHelper::generarURL($sede_id, $expiraEnSegundos);
        $enlaceWhatsApp = TokenHelper::generarEnlaceWhatsApp($sede_id, $sedeNombre, $expiraEnSegundos);

        return json_encode([
            'tipo'            => 'enlace',
            'titulo'          => 'Enlace generado',
            'texto'           => 'El enlace de inscripción para la sede ' . $sedeNombre . ' ha sido generado.',
            'icono'           => 'success',
            'url_formulario'  => $urlFormulario,
            'enlace_whatsapp' => $enlaceWhatsApp,
            'sede_nombre'     => $sedeNombre,
            'vigencia_horas'  => $horas
        ]);
    }

    /**
     * Lista las sedes que el usuario en sesión puede usar para generar enlaces.
     * Los roles administrativos (1 y 2) ven todas; el resto solo las asignadas.
     */
    public function listarSedesActivas()
    {
        $rolid   = $_SESSION['rol'] ?? null;
        $usuario = $_SESSION['usuario'] ?? '';

        if ($rolid != 1 && $rolid != 2) {
            $consulta = $this->ejecutarConsulta(
                "SELECT S.sede_id, S.sede_nombre
                   FROM general_sede S
                   INNER JOIN seguridad_usuario_sede US ON US.usuariosede_sedeid = S.sede_id
                   INNER JOIN seguridad_usuario U ON U.usuario_id = US.usuariosede_usuarioid
                  WHERE U.usuario_usuario = :usuario
                  ORDER BY S.sede_nombre",
                [":usuario" => $usuario]
            );
        } else {
            $consulta = $this->ejecutarConsulta(
                "SELECT sede_id, sede_nombre FROM general_sede ORDER BY sede_nombre"
            );
        }

        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve el nombre de la sede si el usuario en sesión tiene acceso a ella,
     * o null si no existe o no le corresponde.
     */
    private function obtenerSedePermitida($sede_id)
    {
        $rolid   = $_SESSION['rol'] ?? null;
        $usuario = $_SESSION['usuario'] ?? '';

        if ($rolid != 1 && $rolid != 2) {
            $consulta = $this->ejecutarConsulta(
                "SELECT S.sede_nombre
                   FROM general_sede S
                   INNER JOIN seguridad_usuario_sede US ON US.usuariosede_sedeid = S.sede_id
                   INNER JOIN seguridad_usuario U ON U.usuario_id = US.usuariosede_usuarioid
                  WHERE S.sede_id = :sede AND U.usuario_usuario = :usuario",
                [":sede" => $sede_id, ":usuario" => $usuario]
            );
        } else {
            $consulta = $this->ejecutarConsulta(
                "SELECT sede_nombre FROM general_sede WHERE sede_id = :sede",
                [":sede" => $sede_id]
            );
        }

        $sede = $consulta->fetch(\PDO::FETCH_ASSOC);

        return $sede ? $sede['sede_nombre'] : null;
    }
}
