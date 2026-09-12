<?php
require_once __DIR__ . '/../models/Nodo.php';
require_once __DIR__ . '/../models/Alerta.php';
require_once __DIR__ . '/../controllers/LecturaController.php';
$nodos        = Nodo::todos();
$alertasCount = Alerta::conteoActivas();
$nodoSel      = isset($_GET['nodo_id']) ? (int)$_GET['nodo_id'] : 0;
$rango        = $_GET['rango'] ?? '6h';
$datos        = LecturaController::paraGrafica($nodoSel,$rango);
$labels = array_column($datos,'hora');
$temps  = array_column($datos,'temperatura');
$hums   = array_column($datos,'humedad');
?><!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Historial – Red Mesh</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head><body>
<div class="app">
  <aside class="sidebar">
    <div class="sidebar-logo"><div class="logo-cloud">☁</div><div class="logo-text"><span class="logo-title">Red Mesh Climatica</span><span class="logo-sub">Monitoreo Autonomo</span></div></div>
    <nav class="sidebar-nav">
      <a href="index.php" class="nav-item"><span class="nav-icon">🏠</span><span>Inicio</span></a>
      <a href="index.php?pagina=nodos" class="nav-item"><span class="nav-icon">📡</span><span>Nodos</span></a>
      <a href="index.php?pagina=historial" class="nav-item active"><span class="nav-icon">📊</span><span>Historial</span></a>
      <a href="index.php?pagina=alertas" class="nav-item"><span class="nav-icon">🔔</span><span>Alertas</span><span class="nav-badge"><?=$alertasCount?></span></a>
      <a href="index.php?pagina=configuracion" class="nav-item"><span class="nav-icon">⚙️</span><span>Configuracion</span></a>
    </nav>
    <div class="sidebar-bottom"><div class="sidebar-tagline">Historial<br><strong>de Datos</strong></div></div>
  </aside>
  <div class="main">
    <header class="topbar">
      <div class="topbar-item">📊 <strong>Historial de Mediciones</strong></div>
      <div class="topbar-item">🕐 <span id="top-time"></span></div>
    </header>
    <main class="content">
      <!-- Filtros -->
      <div class="panel" style="padding:16px">
        <form method="GET" action="" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
          <input type="hidden" name="pagina" value="historial">
          <div style="display:flex;flex-direction:column;gap:4px">
            <label style="font-size:11px;color:var(--txt2)">Nodo</label>
            <select name="nodo_id" class="chart-select" style="min-width:160px">
              <option value="0" <?=$nodoSel===0?'selected':''?>>Todos los nodos</option>
              <?php foreach($nodos as $n): ?><option value="<?=$n['id']?>" <?=$nodoSel==$n['id']?'selected':''?>><?=htmlspecialchars($n['nombre'])?></option><?php endforeach; ?>
            </select>
          </div>
          <div style="display:flex;flex-direction:column;gap:4px">
            <label style="font-size:11px;color:var(--txt2)">Periodo</label>
            <select name="rango" class="chart-select">
              <option value="6h" <?=$rango==='6h'?'selected':''?>>Ultimas 6 horas</option>
              <option value="24h" <?=$rango==='24h'?'selected':''?>>Ultimas 24 horas</option>
              <option value="7d" <?=$rango==='7d'?'selected':''?>>7 dias</option>
              <option value="30d" <?=$rango==='30d'?'selected':''?>>30 dias</option>
            </select>
          </div>
          <button type="submit" class="status-btn" style="width:auto;padding:8px 20px;margin-top:18px">🔍 Filtrar</button>
        </form>
      </div>
      <!-- Grafica -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">📈 Evolucion Climatica<?=$nodoSel>0?' – '.htmlspecialchars(Nodo::porId($nodoSel)['nombre']??'').' ':' (Red completa)'?></span></div>
        <div class="chart-body" style="height:300px"><div class="chart-canvas-wrap" style="height:280px"><canvas id="histChart"></canvas></div></div>
      </div>
      <!-- Tabla -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">📋 Datos del Periodo</span><span style="font-size:11px;color:var(--txt2)"><?=count($datos)?> registros</span></div>
        <div class="sensors-body"><table class="sensors-table">
          <thead><tr><th>Hora</th><th>Temperatura (°C)</th><th>Humedad (%)</th></tr></thead>
          <tbody>
          <?php foreach(array_slice(array_reverse($datos),0,30) as $row): ?>
          <tr><td><?=htmlspecialchars($row['hora']??'')?></td><td><?=htmlspecialchars($row['temperatura']??'')?></td><td><?=htmlspecialchars($row['humedad']??'')?></td></tr>
          <?php endforeach; ?>
          <?php if(empty($datos)): ?><tr><td colspan="3" style="text-align:center;color:var(--txt2)">Sin datos para el periodo seleccionado</td></tr><?php endif; ?>
          </tbody>
        </table></div>
      </div>
    </main>
  </div>
</div>
<script>
function tick(){const t=new Date();const el=document.getElementById('top-time');if(el)el.textContent=String(t.getHours()).padStart(2,'0')+':'+String(t.getMinutes()).padStart(2,'0');}
tick(); setInterval(tick,1000);
const labels = <?=json_encode($labels)?>;
const temps  = <?=json_encode($temps)?>;
const hums   = <?=json_encode($hums)?>;
const ctx = document.getElementById('histChart');
if(ctx && labels.length > 0) {
  new Chart(ctx, {type:'line',data:{labels,datasets:[
    {label:'Temperatura (°C)',data:temps,borderColor:'#EC4899',backgroundColor:'rgba(236,72,153,.08)',fill:true,tension:.4,pointRadius:2,borderWidth:2,yAxisID:'y'},
    {label:'Humedad (%)',data:hums,borderColor:'#00D4AA',backgroundColor:'rgba(0,212,170,.08)',fill:true,tension:.4,pointRadius:2,borderWidth:2,yAxisID:'y2'},
  ]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
    plugins:{legend:{labels:{color:'rgba(255,255,255,.6)',font:{size:11},usePointStyle:true}},tooltip:{backgroundColor:'rgba(13,13,40,.95)',titleColor:'#fff',bodyColor:'rgba(255,255,255,.7)',borderColor:'rgba(123,47,190,.4)',borderWidth:1}},
    scales:{x:{ticks:{color:'rgba(255,255,255,.4)',font:{size:10}},grid:{color:'rgba(255,255,255,.04)'}},
      y:{position:'left',ticks:{color:'rgba(255,255,255,.4)',font:{size:10}},grid:{color:'rgba(255,255,255,.04)'},title:{display:true,text:'°C',color:'rgba(255,255,255,.3)',font:{size:10}}},
      y2:{position:'right',ticks:{color:'rgba(255,255,255,.4)',font:{size:10}},grid:{drawOnChartArea:false},title:{display:true,text:'%',color:'rgba(255,255,255,.3)',font:{size:10}}}}}});
}
</script>
</body></html>