// Red Mesh Climatica — Dashboard JS (Tiempo Real Mejorado)
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

    if (climaChart) {
        climaChart.data.labels = labels;
        climaChart.data.datasets[0].data = temps;
        climaChart.data.datasets[1].data = hums;
        climaChart.update('none');
        return;
    }

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
            animation: false,
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
    fetch(API + 'historial.php?rango=' + encodeURIComponent(rango))
        .then(r => r.json())
        .then(d => {
            const rows = d.datos ?? [];
            initChart(rows.map(r=>r.hora), rows.map(r=>r.temperatura), rows.map(r=>r.humedad));
        })
        .catch(e => console.warn('[Chart] Error:', e.message));
}

// ── Actualizacion en Tiempo Real ──────────────────
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function etiquetaEstado(estado) {
    switch (estado) {
        case 'activo': return 'En línea';
        case 'inactivo': return 'Desconectado';
        case 'alerta': return 'Con Alerta';
        case 'bateria_baja': return 'Batería Baja';
        default: return 'Desconocido';
    }
}

function cssEstado(estado) {
    switch (estado) {
        case 'activo': return 'online';
        case 'inactivo': return 'offline';
        case 'alerta': return 'alert';
        case 'bateria_baja': return 'battery';
        default: return 'offline';
    }
}

function actualizarKPI(d) {
    const set = (id, val) => { const el = document.getElementById(id); if(el) el.innerHTML = val; };
    if (d.temp_promedio !== null && d.temp_promedio !== undefined) {
        set('kpi-temp', d.temp_promedio + ' <span style="font-size:16px;color:var(--txt2)">°C</span>');
    }
    if (d.hum_promedio !== null && d.hum_promedio !== undefined) {
        set('kpi-hum', d.hum_promedio + ' <span style="font-size:16px;color:var(--txt2)">%</span>');
    }
    if (d.bat_promedio !== null && d.bat_promedio !== undefined) {
        set('kpi-bat', d.bat_promedio + ' <span style="font-size:16px;color:var(--txt2)">%</span>');
    }
    if (d.nodos_en_linea !== undefined) {
        set('kpi-nodos', d.nodos_en_linea + ' <span style="font-size:16px;color:var(--txt2)">/ ' + d.nodos_total + '</span>');
        const offline = document.getElementById('kpi-nodos-delta');
        if (offline) {
            offline.textContent = (d.nodos_fuera_linea || 0) + ' fuera de linea';
            offline.className = 'kpi-delta ' + (d.nodos_fuera_linea > 0 ? 'warn' : 'up');
        }
    }
    const badge = document.getElementById('badge-alertas');
    if (badge && d.alertas_activas !== undefined) {
        badge.textContent = d.alertas_activas;
    }
}

function actualizarTablaSensores(nodos) {
    const tbody = document.getElementById('sensors-table-body');
    if (!tbody || !Array.isArray(nodos)) return;

    let html = '';
    for (const n of nodos) {
        const css = cssEstado(n.estado);
        const label = etiquetaEstado(n.estado);
        const temp = n.temperatura_actual !== null ? `${n.temperatura_actual} °C` : '—';
        const hum = n.humedad_actual !== null ? `${n.humedad_actual} %` : '—';
        const bat = n.bateria !== null ? `${n.bateria} %` : '—';

        html += `<tr>
            <td>${escapeHtml(n.nombre)}</td>
            <td>${temp}</td>
            <td>${hum}</td>
            <td>${bat}</td>
            <td><span class="status-badge ${css}"><span class="dot"></span> ${label}</span></td>
        </tr>`;
    }
    tbody.innerHTML = html;
}

function actualizarMapa(nodos) {
    if (!Array.isArray(nodos)) return;
    const nodeMap = {};
    for (const n of nodos) {
        nodeMap[n.id] = n;
        const circle = document.getElementById(`node-c-${n.id}`);
        const icon = document.getElementById(`node-icon-${n.id}`);
        const val = document.getElementById(`node-val-${n.id}`);
        const activo = n.estado === 'activo';
        const col = activo ? '#10B981' : (n.estado === 'alerta' ? '#F59E0B' : '#EF4444');
        const fill = activo ? 'rgba(16,185,129,.15)' : (n.estado === 'alerta' ? 'rgba(245,158,11,.15)' : 'rgba(239,68,68,.15)');

        if (circle) {
            circle.setAttribute('stroke', col);
            circle.setAttribute('fill', fill);
        }
        if (icon) {
            icon.setAttribute('fill', col);
            icon.textContent = activo ? '📦' : '⚠';
        }
        if (val) {
            val.textContent = (n.temperatura_actual !== null)
                ? `${n.temperatura_actual}°C | ${n.humedad_actual}%`
                : 'Sin datos';
        }
    }

    const links = [[1,2],[1,3],[2,4],[3,5],[4,5],[5,6],[2,7]];
    for (const [a, b] of links) {
        const line = document.getElementById(`mesh-link-${a}-${b}`);
        if (line) {
            const nA = nodeMap[a];
            const nB = nodeMap[b];
            const activeLink = (nA && nA.estado === 'activo' && nB && nB.estado === 'activo');
            line.setAttribute('stroke', activeLink ? '#10B981' : '#EF4444');
        }
    }
}

function actualizarEstadoSistema(d) {
    const total = d.nodos_total || 1;
    const enLinea = d.nodos_en_linea ?? d.nodos_activos ?? 0;
    const pct = Math.max(0, Math.min(100, Math.round((enLinea / total) * 100)));
    const dash = Math.round(pct * 1.759);

    const ringCircle = document.getElementById('status-ring-circle');
    const ringPct = document.getElementById('status-ring-pct');
    const opLabel = document.getElementById('status-op-label');
    const opDesc = document.getElementById('status-op-desc');
    const topDot = document.getElementById('top-status-dot');
    const topText = document.getElementById('top-status-text');

    const col = d.estado_general?.css === 'red' ? '#EF4444' : (d.estado_general?.css === 'orange' ? '#F59E0B' : '#10B981');

    if (ringCircle) {
        ringCircle.setAttribute('stroke', col);
        ringCircle.setAttribute('stroke-dasharray', `${dash} 176`);
    }
    if (ringPct) ringPct.textContent = `${pct}%`;
    if (opLabel) {
        opLabel.textContent = d.estado_general?.mensaje || 'Operacion Normal';
        opLabel.style.color = col;
    }
    if (opDesc) {
        opDesc.textContent = `${enLinea} de ${total} nodos reportando. ${d.alertas_activas || 0} alerta(s) activa(s).`;
    }
    if (topDot) {
        topDot.className = 'status-dot ' + (d.estado_general?.css === 'red' ? 'red' : '');
    }
    if (topText) {
        topText.textContent = d.estado_general?.mensaje || 'Operacion Normal';
    }
}

function actualizarDashboard() {
    fetch(API + 'datos_dashboard.php?t=' + Date.now())
        .then(r => r.json())
        .then(d => {
            if (!d) return;
            actualizarKPI(d);
            actualizarTablaSensores(d.nodos);
            actualizarMapa(d.nodos);
            actualizarEstadoSistema(d);
        })
        .catch(e => console.warn('[Dashboard] API no disponible:', e.message));
}

// ── Simular datos ─────────────────────────────────
function simularDatos() {
    const btn = event.target;
    btn.textContent = '⏳';
    btn.disabled = true;
    fetch(API + 'simular.php?rondas=1')
        .then(r => r.json())
        .then(d => {
            btn.textContent = '✅';
            setTimeout(() => { btn.textContent = '▶'; btn.disabled = false; }, 1500);
            actualizarDashboard();
            recargarGrafica();
        })
        .catch(e => { btn.textContent = '▶'; btn.disabled = false; });
}

// ── Inicializacion ────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    recargarGrafica();
    actualizarDashboard();

    // Actualiza datos cada 5 segundos para tiempo real continuo
    setInterval(actualizarDashboard, 5000);
    // Actualiza la grafica cada 15 segundos
    setInterval(recargarGrafica, 15000);
});
