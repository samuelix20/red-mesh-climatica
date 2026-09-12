// Red Mesh Climatica — Dashboard JS
const API = 'api/';
let climaChart = null;

// ── Reloj ─────────────────────────────────────────
function tick() {
    const t = new Date();
    const hm = String(t.getHours()).padStart(2,'0')+':'+String(t.getMinutes()).padStart(2,'0');
    const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    const fecha = t.getDate()+' '+meses[t.getMonth()]+' '+t.getFullYear();
    const et = document.getElementById('top-time');  if(et) et.textContent = hm;
    const ed = document.getElementById('top-date');  if(ed) ed.textContent = fecha;
}
tick(); setInterval(tick, 1000);

// ── Grafica climatica ─────────────────────────────
function initChart(labels, temps, hums) {
    const ctx = document.getElementById('climaChart');
    if (!ctx) return;
    if (climaChart) climaChart.destroy();
    climaChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Temperatura (°C)', data: temps, borderColor: '#EC4899', backgroundColor: 'rgba(236,72,153,.08)', fill: true, tension: .4, pointRadius: 2, borderWidth: 2, yAxisID: 'y' },
                { label: 'Humedad (%)',       data: hums,  borderColor: '#00D4AA', backgroundColor: 'rgba(0,212,170,.08)',  fill: true, tension: .4, pointRadius: 2, borderWidth: 2, yAxisID: 'y2' },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: 'rgba(255,255,255,.6)', font: { size: 11 }, usePointStyle: true } },
                tooltip: { backgroundColor: 'rgba(13,13,40,.95)', titleColor: '#fff', bodyColor: 'rgba(255,255,255,.7)', borderColor: 'rgba(123,47,190,.4)', borderWidth: 1 }
            },
            scales: {
                x:  { ticks: { color: 'rgba(255,255,255,.4)', font:{size:10} }, grid: { color: 'rgba(255,255,255,.04)' } },
                y:  { position:'left',  ticks: { color:'rgba(255,255,255,.4)',font:{size:10} }, grid:{color:'rgba(255,255,255,.04)'}, title:{display:true,text:'°C',color:'rgba(255,255,255,.3)',font:{size:10}} },
                y2: { position:'right', ticks: { color:'rgba(255,255,255,.4)',font:{size:10} }, grid:{drawOnChartArea:false}, title:{display:true,text:'%',color:'rgba(255,255,255,.3)',font:{size:10}} }
            }
        }
    });
}

function recargarGrafica() {
    const rango = document.getElementById('chart-rango')?.value ?? '6h';
    fetch(API + 'historial.php?rango=' + rango)
        .then(r => r.json())
        .then(d => {
            const rows = d.datos ?? [];
            initChart(rows.map(r=>r.hora), rows.map(r=>r.temperatura), rows.map(r=>r.humedad));
        })
        .catch(e => console.warn('[Chart] Error:', e.message));
}

// ── Polling del dashboard ─────────────────────────
function actualizarKPI(d) {
    const set = (id, val) => { const el = document.getElementById(id); if(el) el.innerHTML = val; };
    if (d.temp_promedio) set('kpi-temp', (d.temp_promedio ?? '--') + ' <span style="font-size:16px;color:var(--txt2)">°C</span>');
    if (d.hum_promedio)  set('kpi-hum',  (d.hum_promedio  ?? '--') + ' <span style="font-size:16px;color:var(--txt2)">%</span>');
    if (d.bat_promedio)  set('kpi-bat',  (d.bat_promedio  ?? '--') + ' <span style="font-size:16px;color:var(--txt2)">%</span>');
    if (d.nodos_activos !== undefined)
        set('kpi-nodos', d.nodos_activos + ' <span style="font-size:16px;color:var(--txt2)">/ ' + d.nodos_total + '</span>');
    const badge = document.getElementById('badge-alertas');
    if (badge && d.alertas_activas !== undefined) badge.textContent = d.alertas_activas;
}

function actualizarDashboard() {
    fetch(API + 'datos_dashboard.php')
        .then(r => r.json())
        .then(d => { actualizarKPI(d); })
        .catch(e => console.warn('[Dashboard] API no disponible:', e.message));
}

// ── Simular datos ─────────────────────────────────
function simularDatos() {
    const btn = event.target;
    btn.textContent = '⏳';
    btn.disabled = true;
    fetch(API + 'simular.php?rondas=3')
        .then(r => r.json())
        .then(d => {
            console.log('[Simular]', d.mensaje);
            btn.textContent = '✅';
            setTimeout(() => { btn.textContent = '▶'; btn.disabled = false; }, 2000);
            actualizarDashboard();
            recargarGrafica();
        })
        .catch(e => { btn.textContent = '▶'; btn.disabled = false; });
}

// ── Init ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    recargarGrafica();
    actualizarDashboard();
    const cfg = window._CONFIG ?? {};
    const intervalo = (cfg.intervalo ?? 30) * 1000;
    setInterval(actualizarDashboard, intervalo);
    setInterval(recargarGrafica, intervalo * 2);
});