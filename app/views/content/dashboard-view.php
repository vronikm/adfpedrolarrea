<?php
use app\controllers\dashboardController;
$insDashboard = new dashboardController();
$sedes = $insDashboard->obtenerResumenSedes()->fetchAll(\PDO::FETCH_ASSOC);
$totalRepresentantes = (int) $insDashboard->obtenerRepresentantes()->fetch()['totalRepresentantes'];
$totalAlumnosActivos = array_sum(array_column($sedes, 'total_activos'));
$totalAlumnosInactivos = array_sum(array_column($sedes, 'total_inactivos'));
$totalPendientes = array_sum(array_column($sedes, 'total_pendientes'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo APP_NAME; ?> | Dashboard</title>
  <link rel="icon" type="image/png" href="<?php echo APP_URL; ?>app/views/dist/img/Logos/LogoRojo.png">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/css/adminlte.css">
  <link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/dist/css/sweetalert2.min.css">
  <script src="<?php echo APP_URL; ?>app/views/dist/js/sweetalert2.all.min.js"></script>
  <style>
    .content .card-header .card-title {
      font-size: 1.1rem;
      font-weight: 600;
      letter-spacing: .01em;
    }

    .content .info-box {
      min-height: 96px;
      overflow: hidden;
      transition: transform .15s ease, box-shadow .15s ease;
    }

    .content a .info-box:hover {
      transform: translateY(-2px);
      box-shadow: 0 .35rem .85rem rgba(0, 0, 0, .14) !important;
    }

    .content .info-box-icon {
      width: 78px;
      min-width: 78px;
      height: 78px;
      margin: 9px 0 9px 9px;
      border-radius: .3rem;
      font-size: 2rem;
    }

    .content .info-box-content {
      justify-content: center;
      padding: 10px 14px;
      line-height: 1.2;
    }

    .content .info-box-text {
      font-size: .95rem;
      font-weight: 500;
      margin-bottom: 5px;
      white-space: normal;
    }

    .content .info-box-number {
      color: #212529;
      font-size: 1.8rem;
      font-weight: 700;
      line-height: 1;
    }

    @media (max-width: 575.98px) {
      .content .info-box { min-height: 84px; }
      .content .info-box-icon { width: 66px; min-width: 66px; height: 66px; margin: 9px 0 9px 9px; font-size: 1.65rem; }
      .content .info-box-number { font-size: 1.55rem; }
    }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  <?php require_once 'app/views/inc/navbar.php'; ?>
  <?php require_once 'app/views/inc/main-sidebar.php'; ?>
  <div class="content-wrapper">
    <div class="content-header"><div class="container-fluid"><div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Dashboard Operativo</h1></div>
      <div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>dashboard/">Inicio</a></li><li class="breadcrumb-item active">Dashboard Operativo</li></ol></div>
    </div></div></div>
    <section class="content"><div class="container-fluid">
      <?php if (empty($sedes)) { ?>
        <div class="alert alert-info">No existen sedes registradas para mostrar.</div>
      <?php } ?>
      <?php foreach ($sedes as $sede) {
        $sedeId = (int) $sede['sede_id'];
        $sedeNombre = htmlspecialchars($sede['sede_nombre'], ENT_QUOTES, 'UTF-8');
      ?>
        <div class="card card-default">
          <div class="card-header py-2"><h3 class="card-title"><?php echo $sedeNombre; ?></h3><div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer sede"><i class="fas fa-minus"></i></button></div></div>
          <div class="card-body py-2"><div class="row">
            <div class="col-md-3 mb-3"><a href="<?php echo APP_URL; ?>dashboardAlumnos/<?php echo $sedeId; ?>/A/" class="text-decoration-none"><div class="info-box shadow-sm rounded border mb-0"><span class="info-box-icon bg-primary text-white"><i class="bi bi-people-fill"></i></span><div class="info-box-content"><span class="info-box-text text-muted">Alumnos activos</span><span class="info-box-number"><?php echo (int) $sede['total_activos']; ?></span></div></div></a></div>
            <div class="col-md-3 mb-3"><a href="<?php echo APP_URL; ?>reportePagos/<?php echo $sedeId; ?>" class="text-decoration-none"><div class="info-box shadow-sm rounded border mb-0"><span class="info-box-icon bg-success text-white"><i class="bi bi-check2-circle"></i></span><div class="info-box-content"><span class="info-box-text text-muted">Pagos receptados</span><span class="info-box-number"><?php echo (int) $sede['total_cancelados']; ?></span></div></div></a></div>
            <div class="col-md-3 mb-3"><a href="<?php echo APP_URL; ?>dashboardAlumnos/<?php echo $sedeId; ?>/I/" class="text-decoration-none"><div class="info-box shadow-sm rounded border mb-0"><span class="info-box-icon bg-secondary text-white"><i class="bi bi-person-x-fill"></i></span><div class="info-box-content"><span class="info-box-text text-muted">Alumnos inactivos</span><span class="info-box-number"><?php echo (int) $sede['total_inactivos']; ?></span></div></div></a></div>
            <div class="col-md-3 mb-3"><a href="<?php echo APP_URL; ?>reportePendientes/<?php echo $sedeId; ?>" class="text-decoration-none"><div class="info-box shadow-sm rounded border mb-0"><span class="info-box-icon bg-danger text-white"><i class="bi bi-exclamation-triangle-fill"></i></span><div class="info-box-content"><span class="info-box-text text-muted">Alumnos con mora</span><span class="info-box-number"><?php echo (int) $sede['total_pendientes']; ?></span></div></div></a></div>
          </div></div>
        </div>
      <?php } ?>
      <div class="card card-default">
        <div class="card-header py-2"><h3 class="card-title">Consolidado</h3></div>
        <div class="card-body py-2"><div class="row">
          <div class="col-md-3 mb-3"><a href="<?php echo APP_URL; ?>representanteList/" class="text-decoration-none"><div class="info-box shadow-sm rounded border mb-0"><span class="info-box-icon bg-warning text-white"><i class="bi bi-people-fill"></i></span><div class="info-box-content"><span class="info-box-text text-muted">Representantes</span><span class="info-box-number"><?php echo $totalRepresentantes; ?></span></div></div></a></div>
          <div class="col-md-3 mb-3"><div class="info-box shadow-sm rounded border mb-0"><span class="info-box-icon bg-primary text-white"><i class="bi bi-people-fill"></i></span><div class="info-box-content"><span class="info-box-text text-muted">Alumnos activos</span><span class="info-box-number"><?php echo $totalAlumnosActivos; ?></span></div></div></div>
          <div class="col-md-3 mb-3"><div class="info-box shadow-sm rounded border mb-0"><span class="info-box-icon bg-secondary text-white"><i class="bi bi-person-x-fill"></i></span><div class="info-box-content"><span class="info-box-text text-muted">Alumnos inactivos</span><span class="info-box-number"><?php echo $totalAlumnosInactivos; ?></span></div></div></div>
          <div class="col-md-3 mb-3"><div class="info-box shadow-sm rounded border mb-0"><span class="info-box-icon bg-danger text-white"><i class="bi bi-exclamation-triangle-fill"></i></span><div class="info-box-content"><span class="info-box-text text-muted">Alumnos con mora</span><span class="info-box-number"><?php echo $totalPendientes; ?></span></div></div></div>
        </div></div>
      </div>
    </div></section>
  </div>
  <?php require_once 'app/views/inc/footer.php'; ?>
  <aside class="control-sidebar control-sidebar-dark"></aside>
</div>
<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jquery/jquery.min.js"></script>
<script src="<?php echo APP_URL; ?>app/views/dist/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo APP_URL; ?>app/views/dist/js/adminlte.js"></script>
<script src="<?php echo APP_URL; ?>app/views/dist/js/ajax.js"></script>
<script src="<?php echo APP_URL; ?>app/views/dist/js/main.js"></script>
</body>
</html>
