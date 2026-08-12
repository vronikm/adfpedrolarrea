-- ============================================================
--  Módulo: Generar Enlace de Inscripción (inscripcionEnlace)
-- ============================================================
--  Los roles administrativos (1 y 2) ya ven la opción porque el
--  sidebar la toma de app/views/inc/menu_admin.php.
--
--  Para el resto de roles el menú es dinámico (seguridad_menu +
--  seguridad_permiso), así que hay que registrar la vista y luego
--  asignarla a cada rol desde la pantalla "permisoNew".
-- ============================================================

-- 1) Registrar la opción en el menú dinámico
INSERT INTO `seguridad_menu`
    (`menu_id`, `menu_nombre`, `menu_orden`, `menu_padreid`, `menu_hijo`, `menu_vista`, `menu_icono`, `menu_estado`)
SELECT
    (SELECT COALESCE(MAX(`menu_id`), 0) + 1 FROM `seguridad_menu` m),
    'Inscripción Online',
    2,
    0,
    'N',
    'inscripcionEnlace',
    'nav-icon fab fa-whatsapp',
    'A'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `seguridad_menu` m2 WHERE m2.`menu_vista` = 'inscripcionEnlace'
);

-- 2) Asignar el permiso a los roles que deban generar enlaces.
--    Reemplace <ROL_ID> por el rol correspondiente, o hágalo desde
--    la pantalla "permisoNew" del sistema (opción recomendada).
--
-- INSERT INTO `seguridad_permiso` (`permiso_rolid`, `permiso_menuid`)
-- SELECT <ROL_ID>, `menu_id` FROM `seguridad_menu` WHERE `menu_vista` = 'inscripcionEnlace';
