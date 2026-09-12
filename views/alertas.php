<?php
require_once __DIR__ . '/../controllers/AlertaController.php';
require_once __DIR__ . '/../models/Alerta.php';
$filtro       = $_GET['filtro'] ?? 'activas';
$alertas      = $filtro==='historial' ? Alerta::historial(100) : Alerta::activas(100);
$alertasCount = Alerta::conteoActivas();
$estado       = AlertaController::estadoGeneral();
?><!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Alertas – Red Mesh</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<div class="app">
  <aside class="sidebar">
    <div class="sidebar-logo"><div class="logo-cloud">☁</div><div class="logo-text"><span class="logo-title">Red Mesh Climatica</span><span class="logo-sub">Monitoreo Autonomo</span></div></div>
    <nav class="sidebar-nav">
      <a href="index.php" class="nav-item"><span class="nav-icon">🏠</span><span>Inicio</span></a>
      <a href="index.php?pagina=nodos" class="nav-item"><span class="nav-icon">📡</span><span>Nodos</span></a>
      <a href="index.php?pagina=historial" class="nav-item"><span class="nav-icon">📊</span><span>Historial</span></a>
      <a href="index.php?pagina=alertas" class="nav-item active"><span class="nav-icon">🔔</span><span>Alertas</span><span class="nav-badge"><?=$alertasCount?></span></a>
      <a href="index.php?pagina=configuracion" class="nav-item"><span class="nav-icon">⚙️</span><span>Configuracion</span></a>
    </nav>
    <div class="sidebar-bottom"><div class="sidebar-tagline">Sistema<br><strong>de Alertas</strong></div></div>
  </aside>
  <div class="main">
    <header class="topbar">
      <div class="topbar-item">🔔 <strong>Alertas del Sistema</strong></div>
      <div class="topbar-item" style="color:<?=$estado['css']==='red'?'#EF4444':($estado['css']==='orange'?'#F59E0B':'#10B981')?>"><?=$estado['mensaje']?></div>
      <div class="topbar-item">🕐 <span id="top-time"></span></div>
    </header>
    <main class="content">
      <div class="kpi-grid">
        <div class="kpi-card" style="--accent:#EF4444;--accent-bg:rgba(239,68,68,.2)"><div class="kpi-icon-wrap">🔴</div><div class="kpi-label">Criticas</div><div class="kpi-value"><?=$estado['criticas']?></div></div>
        <div class="kpi-card" style="--accent:#F59E0B;--accent-bg:rgba(245,158,11,.2)"><div class="kpi-icon-wrap">🟡</div><div class="kpi-label">Advertencias</div><div class="kpi-value"><?=$estado['advertencias']?></div></div>
        <div class="kpi-card" style="--accent:#10B981;--accent-bg:rgba(16,185,129,.2)"><div class="kpi-icon-wrap">🟢</div><div class="kpi-label">Informativas</div><div class="kpi-value"><?=max(0,$estado['total']-$estado['criticas']-$estado['advertencias'])?></div></div>
        <div class="kpi-card" style="--accent:#7B2FBE;--accent-bg:rgba(123,47,190,.2)"><div class="kpi-icon-wrap">📋</div><div class="kpi-label">Total Activas</div><div class="kpi-value"><?=$estado['total']?></div></div>
      </div>
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">🔔 Lista de Alertas</span>
          <div style="display:flex;gap:8px">
            <a href="?pagina=alertas&filtro=activas" class="btn-tab <?=$filtro==='activas'?'active':''?>">Activas</a>
            <a href="?pagina=alertas&filtro=historial" class="btn-tab <?=$filtro==='historial'?'active':''?>">Historial</a>
          </div>
        </div>
        <div class="alert-list" style="max-height:none">
          <?php if(empty($alertas)): ?>
          <div class="alert-item"><span class="alert-dot green"></span><div class="alert-body"><div class="alert-title">✅ No hay alertas activas</div></div></div>
          <?php else: foreach($alertas as $al): $dot=Alerta::cssNivel($al['nivel']); ?>
          <div class="alert-item" id="alerta-<?=$al['id']?>">
            <span class="alert-dot <?=$dot?>"></span>
            <div class="alert-body" style="flex:1">
              <div style="display:flex;justify-content:space-between;align-items:start">
                <div><div class="alert-title"><?=Alerta::iconoNivel($al['nivel'])?> <?=htmlspecialchars(Alerta::etiquetaTipo($al['tipo']))?></div>
                <div class="alert-meta">Nodo: <span><?=htmlspecialchars($al['nodo_nombre']??'Sistema')?></span> · <?=htmlspecialchars($al['identificador']??'')?></div>
                <div style="font-size:11px;color:var(--txt2);margin-top:2px"><?=htmlspecialchars($al['descripcion'])?></div>
                <div class="alert-meta"><?=date('d/m/Y H:i',strtotime($al['created_at']))?> · <span>Estado: <?=$al['estado']?></span></div></div>
                <?php if($al['estado']==='activa'): ?>
                <div style="display:flex;gap:6px;flex-shrink:0">
                  <button class="btn-sm" onclick="reconocerAlerta(<?=$al['id']?>)">✓ Reconocer</button>
                  <button class="btn-sm" style="background:rgba(239,68,68,.2);color:#EF4444" onclick="resolverAlerta(<?=$al['id']?>)">✗ Resolver</button>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </main>
  </div>
</div>
<script>
function tick(){const t=new Date();const el=document.getElementById('top-time');if(el)el.textContent=String(t.getHours()).padStart(2,'0')+':'+String(t.getMinutes()).padStart(2,'0');}
tick(); setInterval(tick,1000);
function reconocerAlerta(id){fetch('api/alertas.php?accion=reconocer&id='+id).then(r=>r.json()).then(d=>{if(d.ok){const el=document.getElementById('alerta-'+id);if(el)el.style.opacity='0.5';}});}
function resolverAlerta(id){fetch('api/alertas.php?accion=resolver&id='+id).then(r=>r.json()).then(d=>{if(d.ok){const el=document.getElementById('alerta-'+id);if(el)el.remove();}});}
</script>
</body></html>