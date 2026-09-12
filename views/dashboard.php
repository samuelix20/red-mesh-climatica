<?php
require_once __DIR__ . '/../controllers/DashboardController.php';
$d = DashboardController::obtenerResumen();
$alertasCount = $d['alertas_activas'];
$estado       = $d['estado_general'];
$nodos        = $d['nodos'];
$alertas      = $d['alertas_recientes'];
$tempProm     = $d['temp_promedio'] ?? '--';
$humProm      = $d['hum_promedio']  ?? '--';
$batProm      = $d['bat_promedio']  ?? '--';
?><!DOCTYPE html>
<html lang="es"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard – Red Mesh Climatica</title>
  <link rel="stylesheet" href="/red-mesh-climatica/assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head><body>
<div class="app">
  <aside class="sidebar">
    <div class="sidebar-logo"><div class="logo-cloud">☁</div><div class="logo-text"><span class="logo-title">Red Mesh Climatica</span><span class="logo-sub">Monitoreo Autonomo</span></div></div>
    <nav class="sidebar-nav">
      <a href="index.php" class="nav-item active"><span class="nav-icon">🏠</span><span>Inicio</span></a>
      <a href="index.php?pagina=nodos" class="nav-item"><span class="nav-icon">📡</span><span>Nodos</span></a>
      <a href="index.php?pagina=historial" class="nav-item"><span class="nav-icon">📊</span><span>Historial</span></a>
      <a href="index.php?pagina=alertas" class="nav-item"><span class="nav-icon">🔔</span><span>Alertas</span><span class="nav-badge"><?= $alertasCount ?></span></a>
      <a href="index.php?pagina=configuracion" class="nav-item"><span class="nav-icon">⚙️</span><span>Configuracion</span></a>
    </nav>
    <div class="sidebar-bottom"><div class="mountain-wrap"><svg viewBox="0 0 220 120" preserveAspectRatio="none"><defs><linearGradient id="mg1" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#2563EB" stop-opacity="0.7"/><stop offset="100%" stop-color="#080818" stop-opacity="1"/></linearGradient><linearGradient id="mg2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#7B2FBE" stop-opacity="0.5"/><stop offset="100%" stop-color="#080818" stop-opacity="1"/></linearGradient></defs><polygon points="0,120 0,80 40,40 80,60 110,20 140,55 180,35 220,55 220,120" fill="url(#mg1)"/><polygon points="0,120 20,100 60,70 90,85 110,50 140,75 170,60 200,75 220,65 220,120" fill="url(#mg2)"/><circle cx="110" cy="20" r="3" fill="#00D4AA" opacity="0.9"/><line x1="110" y1="17" x2="110" y2="5" stroke="#00D4AA" stroke-width="1.5" opacity="0.7"/></svg></div>
    <div class="sidebar-tagline">Tecnologia al servicio<br><strong>y sostenible</strong><span class="leaf-icon">🌿</span></div></div>
  </aside>
  <div class="main">
    <header class="topbar">
      <?php if(!$d['db_conectada']): ?><span class="topbar-item" style="color:#F59E0B">⚠ Modo simulado</span><?php endif; ?>
      <div class="topbar-item"><span class="status-dot <?= $estado['css']==='red'?'red':'' ?>"></span><span><?= $estado['mensaje'] ?></span></div>
      <div class="topbar-item">📅 <span id="top-date"></span></div>
      <div class="topbar-item">🕐 <span id="top-time"></span></div>
      <button class="topbar-btn" onclick="simularDatos()" title="Simular datos">▶</button>
    </header>
    <main class="content">
      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card" style="--accent:#EC4899;--accent-bg:rgba(236,72,153,.2)">
          <div class="kpi-top"><div class="kpi-icon-wrap">🌡️</div><svg class="kpi-sparkline" viewBox="0 0 80 32"><polyline points="0,28 16,22 32,18 48,20 64,12 80,10" fill="none" stroke="#EC4899" stroke-width="2" stroke-linecap="round"/></svg></div>
          <div class="kpi-label">Temperatura Promedio</div>
          <div class="kpi-value" id="kpi-temp"><?= $tempProm ?> <span style="font-size:16px;color:var(--txt2)">°C</span></div>
          <div class="kpi-delta" id="kpi-temp-delta">Ultimas lecturas</div>
        </div>
        <div class="kpi-card" style="--accent:#00D4AA;--accent-bg:rgba(0,212,170,.2)">
          <div class="kpi-top"><div class="kpi-icon-wrap">💧</div><svg class="kpi-sparkline" viewBox="0 0 80 32"><polyline points="0,22 16,20 32,18 48,16 64,17 80,13" fill="none" stroke="#00D4AA" stroke-width="2" stroke-linecap="round"/></svg></div>
          <div class="kpi-label">Humedad Promedio</div>
          <div class="kpi-value" id="kpi-hum"><?= $humProm ?> <span style="font-size:16px;color:var(--txt2)">%</span></div>
          <div class="kpi-delta up">Ultima hora</div>
        </div>
        <div class="kpi-card" style="--accent:#F59E0B;--accent-bg:rgba(245,158,11,.2)">
          <div class="kpi-top"><div class="kpi-icon-wrap">🔋</div><svg class="kpi-sparkline" viewBox="0 0 80 32"><polyline points="0,20 16,18 32,16 48,14 64,15 80,13" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round"/></svg></div>
          <div class="kpi-label">Bateria Promedio</div>
          <div class="kpi-value" id="kpi-bat"><?= $batProm ?> <span style="font-size:16px;color:var(--txt2)">%</span></div>
          <div class="kpi-delta warn">Promedio red</div>
        </div>
        <div class="kpi-card" style="--accent:#2563EB;--accent-bg:rgba(37,99,235,.2)">
          <div class="kpi-top"><div class="kpi-icon-wrap">📶</div><svg class="kpi-sparkline" viewBox="0 0 80 32"><polyline points="0,18 16,16 32,14 48,16 64,14 80,14" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round"/></svg></div>
          <div class="kpi-label">Nodos en Linea</div>
          <div class="kpi-value" id="kpi-nodos"><?= $d['nodos_activos'] ?> <span style="font-size:16px;color:var(--txt2)">/ <?= $d['nodos_total'] ?></span></div>
          <div class="kpi-delta <?= $d['nodos_inactivos']>0?'warn':'up' ?>"><?= $d['nodos_inactivos'] ?> fuera de linea</div>
        </div>
      </div>
      <!-- Middle: Mapa + Grafica -->
      <div class="middle-grid">
        <div class="panel">
          <div class="panel-header"><span class="panel-title"><span class="icon">🗺️</span> Mapa de la Red Mesh</span>
            <div class="panel-legend"><span class="legend-dot" style="background:#10B981"></span>En linea <span class="legend-dot" style="background:#EF4444"></span>Fuera <span class="legend-line"></span>Enlace</div>
          </div>
          <div class="map-body">
            <div class="map-bg"></div>
            <svg id="mesh-svg" viewBox="0 0 500 280" xmlns="http://www.w3.org/2000/svg">
              <defs><filter id="glow"><feGaussianBlur stdDeviation="2" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>
              <?php
              $positions = [1=>[250,55],2=>[115,115],3=>[360,115],4=>[145,185],5=>[255,185],6=>[380,175]];
              $links = [[1,2],[1,3],[2,4],[3,5],[4,5],[5,6]];
              foreach($links as [$a,$b]):
                $p1=$positions[$a]; $p2=$positions[$b];
                $nA=null; $nB=null;
                foreach($nodos as $n){ if($n['id']==$a)$nA=$n; if($n['id']==$b)$nB=$n; }
                $col = ($nA&&$nA['estado']==='activo'&&$nB&&$nB['estado']==='activo') ? '#10B981' : '#EF4444';
              ?>
              <line x1="<?=$p1[0]?>" y1="<?=$p1[1]?>" x2="<?=$p2[0]?>" y2="<?=$p2[1]?>" stroke="<?=$col?>" stroke-width="1.5" stroke-dasharray="6,4" opacity="0.6"/>
              <?php endforeach; foreach($nodos as $n): $p=$positions[$n['id']]??[250,140]; $activo=$n['estado']==='activo'; $col=$activo?'#10B981':'#EF4444'; ?>
              <circle cx="<?=$p[0]?>" cy="<?=$p[1]?>" r="20" fill="<?=$activo?'rgba(16,185,129,.15)':'rgba(239,68,68,.15)'?>" stroke="<?=$col?>" stroke-width="2" filter="url(#glow)"/>
              <text x="<?=$p[0]?>" y="<?=$p[1]-4?>" text-anchor="middle" fill="<?=$col?>" font-size="12"><?=$activo?'📦':'⚠'?></text>
              <text x="<?=$p[0]?>" y="<?=$p[1]+9?>" text-anchor="middle" fill="white" font-size="8" font-weight="bold"><?=htmlspecialchars($n['nombre'])?></text>
              <text x="<?=$p[0]?>" y="<?=$p[1]+28?>" text-anchor="middle" fill="rgba(255,255,255,.7)" font-size="7"><?=$n['temperatura_actual']!==null?$n['temperatura_actual'].'°C | '.$n['humedad_actual'].'%':'Sin datos'?></text>
              <?php endforeach; ?>
            </svg>
            <div class="map-zoom"><div class="zoom-btn">+</div><div class="zoom-btn">−</div></div>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header"><span class="panel-title"><span class="icon">📈</span> Datos Climaticos</span>
            <select class="chart-select" id="chart-rango" onchange="recargarGrafica()"><option value="6h">Ultimas 6 horas</option><option value="24h">Ultimas 24 horas</option><option value="7d">7 dias</option></select>
          </div>
          <div class="chart-body"><div class="chart-canvas-wrap"><canvas id="climaChart"></canvas></div></div>
        </div>
      </div>
      <!-- Bottom row -->
      <div class="bottom-grid">
        <div class="panel">
          <div class="panel-header"><span class="panel-title"><span class="icon">🔔</span> Alertas Recientes</span><a href="index.php?pagina=alertas" class="ver-todas">Ver todas →</a></div>
          <div class="alert-list">
            <?php if(empty($alertas)): ?><div class="alert-item"><span class="alert-dot green"></span><div class="alert-body"><div class="alert-title">Sin alertas activas</div></div></div>
            <?php else: foreach($alertas as $al): $dot=Alerta::cssNivel($al['nivel']); ?>
            <div class="alert-item">
              <span class="alert-dot <?=$dot?>"></span>
              <div class="alert-body">
                <div class="alert-title"><?=htmlspecialchars(Alerta::etiquetaTipo($al['tipo']))?></div>
                <div class="alert-meta">Nodo: <span><?=htmlspecialchars($al['nodo_nombre']??'Sistema')?></span> · <?=date('H:i',strtotime($al['created_at']))?></div>
              </div>
            </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header"><span class="panel-title"><span class="icon">⚙️</span> Datos de Sensores</span></div>
          <div class="sensors-body"><table class="sensors-table">
            <thead><tr><th>Nodo</th><th>Temp</th><th>Humedad</th><th>Bateria</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach($nodos as $n): $css=Nodo::cssEstado($n['estado']); ?>
            <tr>
              <td><?=htmlspecialchars($n['nombre'])?></td>
              <td><?=$n['temperatura_actual']!==null?$n['temperatura_actual'].' °C':'—'?></td>
              <td><?=$n['humedad_actual']!==null?$n['humedad_actual'].' %':'—'?></td>
              <td><?=$n['bateria']?> %</td>
              <td><span class="status-badge <?=$css?>"><span class="dot"></span> <?=Nodo::etiquetaEstado($n['estado'])?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table></div>
        </div>
        <div class="panel">
          <div class="panel-header"><span class="panel-title"><span class="icon">🌿</span> Estado del Sistema</span></div>
          <div class="status-body">
            <div class="status-op">
              <div class="status-ring"><svg viewBox="0 0 70 70" width="70" height="70">
                <circle cx="35" cy="35" r="28" fill="none" stroke="rgba(16,185,129,.15)" stroke-width="6"/>
                <?php $pct=max(0,min(100,(int)(($d['nodos_activos']/$d['nodos_total'])*100))); $dash=round($pct*1.759); ?>
                <circle cx="35" cy="35" r="28" fill="none" stroke="<?=$estado['css']==='red'?'#EF4444':($estado['css']==='orange'?'#F59E0B':'#10B981')?>" stroke-width="6" stroke-dasharray="<?=$dash?> 176" stroke-linecap="round" transform="rotate(-90 35 35)"/>
                <text x="35" y="40" text-anchor="middle" fill="white" font-size="13" font-weight="bold"><?=$pct?>%</text>
              </svg></div>
              <div class="status-info">
                <div class="op-label" style="color:<?=$estado['css']==='red'?'#EF4444':($estado['css']==='orange'?'#F59E0B':'#00D4AA')?>"><?=$estado['mensaje']?></div>
                <div class="op-desc"><?=$d['nodos_activos']?> de <?=$d['nodos_total']?> nodos activos. <?=$d['alertas_activas']?> alerta(s) activa(s).</div>
              </div>
            </div>
            <div class="status-badges">
              <div class="status-feat"><span class="feat-icon">💻</span><div class="feat-label">Edge Computing</div></div>
              <div class="status-feat"><span class="feat-icon">🤖</span><div class="feat-label">Autonomia</div></div>
              <div class="status-feat"><span class="feat-icon">🌐</span><div class="feat-label">Green Networking</div></div>
              <div class="status-feat"><span class="feat-icon">🔋</span><div class="feat-label">Bajo Consumo</div></div>
            </div>
            <button class="status-btn" onclick="window.location='index.php?pagina=alertas'">🌿 Ver estado completo</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="/red-mesh-climatica/assets/js/dashboard.js"></script>
</body></html>