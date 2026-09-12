<?php
require_once __DIR__ . '/../controllers/ConfiguracionController.php';
require_once __DIR__ . '/../models/Alerta.php';
$alertasCount = Alerta::conteoActivas();
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = ConfiguracionController::guardar($_POST);
    $mensaje = $res['ok'] ? '<div class="msg-ok">✅ '.$res['mensaje'].'</div>' : '<div class="msg-err">❌ '.$res['error'].'</div>';
}
$cfg = ConfiguracionController::obtener();
?><!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configuracion – Red Mesh</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<div class="app">
  <aside class="sidebar">
    <div class="sidebar-logo"><div class="logo-cloud">☁</div><div class="logo-text"><span class="logo-title">Red Mesh Climatica</span><span class="logo-sub">Monitoreo Autonomo</span></div></div>
    <nav class="sidebar-nav">
      <a href="index.php" class="nav-item"><span class="nav-icon">🏠</span><span>Inicio</span></a>
      <a href="index.php?pagina=nodos" class="nav-item"><span class="nav-icon">📡</span><span>Nodos</span></a>
      <a href="index.php?pagina=historial" class="nav-item"><span class="nav-icon">📊</span><span>Historial</span></a>
      <a href="index.php?pagina=alertas" class="nav-item"><span class="nav-icon">🔔</span><span>Alertas</span><span class="nav-badge"><?=$alertasCount?></span></a>
      <a href="index.php?pagina=configuracion" class="nav-item active"><span class="nav-icon">⚙️</span><span>Configuracion</span></a>
    </nav>
    <div class="sidebar-bottom"><div class="sidebar-tagline">Configuracion<br><strong>del Sistema</strong></div></div>
  </aside>
  <div class="main">
    <header class="topbar"><div class="topbar-item">⚙️ <strong>Configuracion del Sistema</strong></div><div class="topbar-item">🕐 <span id="top-time"></span></div></header>
    <main class="content">
      <?= $mensaje ?>
      <div class="panel" style="max-width:700px">
        <div class="panel-header"><span class="panel-title">⚙️ Umbrales de Alerta</span></div>
        <form method="POST" style="padding:24px;display:flex;flex-direction:column;gap:20px">
          <div class="cfg-group"><div class="cfg-title">🌡️ Temperatura</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
              <div class="cfg-field"><label>Maxima permitida (°C)</label><input type="number" name="temp_max" value="<?=$cfg['temp_max']?>" step="0.5" class="cfg-input"></div>
              <div class="cfg-field"><label>Minima permitida (°C)</label><input type="number" name="temp_min" value="<?=$cfg['temp_min']?>" step="0.5" class="cfg-input"></div>
            </div>
          </div>
          <div class="cfg-group"><div class="cfg-title">💧 Humedad</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
              <div class="cfg-field"><label>Maxima permitida (%)</label><input type="number" name="humedad_max" value="<?=$cfg['humedad_max']?>" step="1" class="cfg-input"></div>
              <div class="cfg-field"><label>Minima permitida (%)</label><input type="number" name="humedad_min" value="<?=$cfg['humedad_min']?>" step="1" class="cfg-input"></div>
            </div>
          </div>
          <div class="cfg-group"><div class="cfg-title">🔋 Bateria</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
              <div class="cfg-field"><label>Nivel minimo (%)</label><input type="number" name="bateria_minima" value="<?=$cfg['bateria_minima']?>" step="1" class="cfg-input"></div>
              <div class="cfg-field"><label>Intervalo actualizacion (seg)</label><input type="number" name="intervalo_actualizacion" value="<?=$cfg['intervalo_actualizacion']?>" step="5" class="cfg-input"></div>
            </div>
          </div>
          <div style="display:flex;gap:12px">
            <button type="submit" class="status-btn" style="width:auto;padding:10px 28px">💾 Guardar Configuracion</button>
            <a href="index.php" class="btn-sm" style="padding:10px 20px;text-decoration:none">← Volver</a>
          </div>
        </form>
      </div>
      <!-- Simular datos -->
      <div class="panel" style="max-width:700px">
        <div class="panel-header"><span class="panel-title">▶ Datos Simulados</span></div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:12px">
          <p style="font-size:13px;color:var(--txt2)">Genera datos de prueba para todos los nodos sin necesidad de dispositivos fisicos conectados.</p>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button class="status-btn" style="width:auto" onclick="simular(1)">▶ Simular 1 ronda</button>
            <button class="status-btn" style="width:auto" onclick="simular(10)">▶ Simular 10 rondas</button>
            <button class="status-btn" style="width:auto;background:rgba(239,68,68,.2);color:#EF4444;border-color:rgba(239,68,68,.4)" onclick="if(confirm('¿Eliminar todas las lecturas y alertas?'))simular(5,true)">🔄 Reset y simular</button>
          </div>
          <div id="sim-resultado" style="font-size:12px;color:var(--teal);min-height:20px"></div>
        </div>
      </div>
    </main>
  </div>
</div>
<style>
.cfg-group{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:10px;padding:16px}
.cfg-title{font-size:13px;font-weight:600;margin-bottom:12px;color:var(--txt)}
.cfg-field{display:flex;flex-direction:column;gap:6px}
.cfg-field label{font-size:11px;color:var(--txt2)}
.cfg-input{background:var(--bg3);border:1px solid var(--border);color:var(--txt);padding:8px 12px;border-radius:6px;font-size:13px;width:100%}
.cfg-input:focus{outline:none;border-color:var(--purple)}
.msg-ok{background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.3);color:#10B981;padding:12px 16px;border-radius:8px;margin-bottom:4px}
.msg-err{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#EF4444;padding:12px 16px;border-radius:8px;margin-bottom:4px}
</style>
<script>
function tick(){const t=new Date();const el=document.getElementById('top-time');if(el)el.textContent=String(t.getHours()).padStart(2,'0')+':'+String(t.getMinutes()).padStart(2,'0');}
tick(); setInterval(tick,1000);
function simular(rondas,reset=false){
  const url='api/simular.php?rondas='+rondas+(reset?'&reset=1':'');
  const el=document.getElementById('sim-resultado');
  el.textContent='Generando datos...';
  fetch(url).then(r=>r.json()).then(d=>{el.textContent='✅ '+d.mensaje;}).catch(e=>{el.textContent='❌ Error: '+e.message;});
}
</script>
</body></html>