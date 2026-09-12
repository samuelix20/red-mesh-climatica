<?php
require_once __DIR__ . '/../controllers/NodoController.php';
$nodos        = NodoController::listar();
$resumen      = NodoController::resumen();
$alertasCount = 0;
try { require_once __DIR__ . '/../models/Alerta.php'; $alertasCount = Alerta::conteoActivas(); } catch(Exception $e) {}
?><!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nodos – Red Mesh</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<div class="app">
  <aside class="sidebar">
    <div class="sidebar-logo"><div class="logo-cloud">☁</div><div class="logo-text"><span class="logo-title">Red Mesh Climatica</span><span class="logo-sub">Monitoreo Autonomo</span></div></div>
    <nav class="sidebar-nav">
      <a href="index.php" class="nav-item"><span class="nav-icon">🏠</span><span>Inicio</span></a>
      <a href="index.php?pagina=nodos" class="nav-item active"><span class="nav-icon">📡</span><span>Nodos</span></a>
      <a href="index.php?pagina=historial" class="nav-item"><span class="nav-icon">📊</span><span>Historial</span></a>
      <a href="index.php?pagina=alertas" class="nav-item"><span class="nav-icon">🔔</span><span>Alertas</span><span class="nav-badge"><?= $alertasCount ?></span></a>
      <a href="index.php?pagina=configuracion" class="nav-item"><span class="nav-icon">⚙️</span><span>Configuracion</span></a>
    </nav>
    <div class="sidebar-bottom"><div class="sidebar-tagline">Gestion de Nodos<br><strong>Red Mesh</strong></div></div>
  </aside>
  <div class="main">
    <header class="topbar">
      <div class="topbar-item">📡 <strong>Gestion de Nodos</strong></div>
      <div class="topbar-item">🕐 <span id="top-time"></span></div>
    </header>
    <main class="content">
      <!-- Stats rapidas -->
      <div class="kpi-grid">
        <div class="kpi-card" style="--accent:#10B981;--accent-bg:rgba(16,185,129,.2)"><div class="kpi-icon-wrap">✅</div><div class="kpi-label">En Linea</div><div class="kpi-value"><?=$resumen['activos']??0?></div></div>
        <div class="kpi-card" style="--accent:#EF4444;--accent-bg:rgba(239,68,68,.2)"><div class="kpi-icon-wrap">❌</div><div class="kpi-label">Desconectados</div><div class="kpi-value"><?=$resumen['inactivos']??0?></div></div>
        <div class="kpi-card" style="--accent:#F59E0B;--accent-bg:rgba(245,158,11,.2)"><div class="kpi-icon-wrap">⚠️</div><div class="kpi-label">Con Alerta</div><div class="kpi-value"><?=($resumen['alertas']??0)+($resumen['bateria_baja']??0)?></div></div>
        <div class="kpi-card" style="--accent:#7B2FBE;--accent-bg:rgba(123,47,190,.2)"><div class="kpi-icon-wrap">📡</div><div class="kpi-label">Total Nodos</div><div class="kpi-value"><?=$resumen['total']??0?></div></div>
      </div>
      <!-- Grid de nodos -->
      <div class="nodes-grid">
        <?php foreach($nodos as $n): $css=Nodo::cssEstado($n['estado']); $label=Nodo::etiquetaEstado($n['estado']); ?>
        <div class="node-card <?=$css?>">
          <div class="node-card-header">
            <div class="node-icon"><?=$n['estado']==='activo'?'📡':($n['estado']==='alerta'?'⚠️':($n['estado']==='bateria_baja'?'🔋':'📵'))?></div>
            <div class="node-info"><div class="node-name"><?=htmlspecialchars($n['nombre'])?></div><div class="node-id"><?=htmlspecialchars($n['identificador'])?></div></div>
            <span class="status-badge <?=$css?>"><span class="dot"></span><?=$label?></span>
          </div>
          <div class="node-metrics">
            <div class="metric"><span class="m-icon">🌡️</span><span class="m-val"><?=$n['temperatura_actual']!==null?$n['temperatura_actual'].'°C':'—'?></span><span class="m-lbl">Temperatura</span></div>
            <div class="metric"><span class="m-icon">💧</span><span class="m-val"><?=$n['humedad_actual']!==null?$n['humedad_actual'].'%':'—'?></span><span class="m-lbl">Humedad</span></div>
            <div class="metric"><span class="m-icon">🔋</span><span class="m-val"><?=$n['bateria']?>%</span><span class="m-lbl">Bateria</span></div>
          </div>
          <div class="node-bat-bar"><div class="bat-fill" style="width:<?=$n['bateria']?>%;background:<?=$n['bateria']>50?'#10B981':($n['bateria']>20?'#F59E0B':'#EF4444')?>"></div></div>
          <div class="node-footer">
            <span class="node-loc">📍 <?=htmlspecialchars($n['ubicacion'])?></span>
            <span class="node-ts"><?=$n['ultima_conexion']?date('d/m H:i',strtotime($n['ultima_conexion'])):'Sin datos'?></span>
          </div>
          <div class="node-actions">
            <a href="index.php?pagina=historial&nodo_id=<?=$n['id']?>" class="btn-sm">📊 Historial</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </main>
  </div>
</div>
<script>
function tick(){const t=new Date();document.getElementById('top-time').textContent=String(t.getHours()).padStart(2,'0')+':'+String(t.getMinutes()).padStart(2,'0');}
tick(); setInterval(tick,1000);
</script>
</body></html>