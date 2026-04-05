<?php
// index.php — NetProbe frontend
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NetProbe — Network Diagnostics</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:       #0a0b0d;
    --panel:    #111318;
    --border:   #1e2230;
    --accent:   #00e5ff;
    --accent2:  #7c3aed;
    --warn:     #f59e0b;
    --danger:   #ef4444;
    --success:  #22c55e;
    --text:     #e2e8f0;
    --muted:    #64748b;
    --mono:     'Share Tech Mono', monospace;
    --sans:     'Syne', sans-serif;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--sans);
    min-height: 100vh;
    overflow-x: hidden;
  }

  /* Grid background */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(rgba(0,229,255,.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,229,255,.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 0;
  }

  /* Glow blob */
  body::after {
    content: '';
    position: fixed;
    top: -200px;
    left: -200px;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(124,58,237,.12) 0%, transparent 70%);
    pointer-events: none;
    z-index: 0;
  }

  .wrap {
    position: relative;
    z-index: 1;
    max-width: 860px;
    margin: 0 auto;
    padding: 48px 24px 80px;
  }

  /* ── Header ── */
  header {
    margin-bottom: 48px;
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 8px;
  }

  .logo-icon {
    width: 44px;
    height: 44px;
    border: 2px solid var(--accent);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 16px rgba(0,229,255,.3);
  }

  .logo-icon svg { display: block; }

  h1 {
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -.02em;
    color: #fff;
  }

  .tagline {
    font-family: var(--mono);
    font-size: .78rem;
    color: var(--accent);
    letter-spacing: .12em;
    text-transform: uppercase;
    margin-top: 4px;
  }

  /* ── Tabs ── */
  .tabs {
    display: flex;
    gap: 4px;
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 4px;
    margin-bottom: 28px;
  }

  .tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: var(--muted);
    font-family: var(--sans);
    font-size: .88rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .2s;
  }

  .tab:hover { color: var(--text); background: rgba(255,255,255,.04); }

  .tab.active {
    background: linear-gradient(135deg, rgba(0,229,255,.15), rgba(124,58,237,.15));
    color: var(--accent);
    border: 1px solid rgba(0,229,255,.25);
  }

  .tab svg { flex-shrink: 0; }

  /* ── Card / Panel ── */
  .card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 20px;
  }

  .card-title {
    font-size: .7rem;
    font-family: var(--mono);
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 18px;
  }

  /* ── Input Row ── */
  .input-row {
    display: flex;
    gap: 10px;
    align-items: stretch;
  }

  .input-wrap {
    flex: 1;
    position: relative;
  }

  .input-prefix {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-family: var(--mono);
    font-size: .82rem;
    pointer-events: none;
  }

  input[type=text] {
    width: 100%;
    background: #0d0f14;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 13px 14px 13px 60px;
    color: var(--text);
    font-family: var(--mono);
    font-size: .95rem;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
  }

  input[type=text]::placeholder { color: var(--muted); }

  input[type=text]:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0,229,255,.1);
  }

  .btn-run {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 24px;
    background: linear-gradient(135deg, var(--accent), #0099bb);
    color: #000;
    border: none;
    border-radius: 8px;
    font-family: var(--sans);
    font-weight: 800;
    font-size: .9rem;
    cursor: pointer;
    transition: opacity .2s, transform .1s;
    white-space: nowrap;
  }

  .btn-run:hover { opacity: .88; }
  .btn-run:active { transform: scale(.97); }
  .btn-run:disabled { opacity: .4; cursor: not-allowed; }

  /* Port scan extra options */
  .port-options {
    margin-top: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .port-options label {
    font-size: .82rem;
    color: var(--muted);
    font-family: var(--mono);
  }

  .btn-chip {
    padding: 5px 14px;
    border-radius: 20px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--muted);
    font-family: var(--mono);
    font-size: .78rem;
    cursor: pointer;
    transition: all .15s;
  }

  .btn-chip:hover, .btn-chip.active {
    border-color: var(--accent);
    color: var(--accent);
  }

  input.port-custom {
    padding: 5px 12px 5px 12px;
    font-size: .82rem;
    flex: 1;
    min-width: 160px;
  }

  /* ── Status / Loader ── */
  .status-bar {
    display: none;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 8px;
    background: rgba(0,229,255,.06);
    border: 1px solid rgba(0,229,255,.2);
    font-family: var(--mono);
    font-size: .82rem;
    color: var(--accent);
    margin-top: 14px;
  }

  .status-bar.visible { display: flex; }

  .spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(0,229,255,.25);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin .7s linear infinite;
    flex-shrink: 0;
  }

  @keyframes spin { to { transform: rotate(360deg); } }

  /* ── Results ── */
  #results { margin-top: 24px; }

  .result-section {
    animation: fadeUp .35s ease both;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  /* Speed result */
  .metric-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
    margin-top: 4px;
  }

  .metric {
    background: #0d0f14;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 14px;
    transition: border-color .2s;
  }

  .metric:hover { border-color: rgba(0,229,255,.3); }

  .metric-val {
    font-family: var(--mono);
    font-size: 1.6rem;
    color: var(--accent);
    line-height: 1;
    margin-bottom: 6px;
  }

  .metric-val.good { color: var(--success); }
  .metric-val.warn { color: var(--warn); }
  .metric-val.bad  { color: var(--danger); }
  .metric-val.null-val { color: var(--muted); font-size: 1rem; }

  .metric-label {
    font-size: .7rem;
    font-family: var(--mono);
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--muted);
  }

  /* Port scan table */
  .port-table {
    width: 100%;
    border-collapse: collapse;
    font-family: var(--mono);
    font-size: .85rem;
    margin-top: 4px;
  }

  .port-table th {
    text-align: left;
    padding: 8px 12px;
    color: var(--muted);
    font-size: .7rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    border-bottom: 1px solid var(--border);
  }

  .port-table td {
    padding: 8px 12px;
    border-bottom: 1px solid rgba(30,34,48,.6);
  }

  .port-table tr:hover td { background: rgba(255,255,255,.02); }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .06em;
  }

  .badge.open  { background: rgba(34,197,94,.15); color: var(--success); }
  .badge.closed{ background: rgba(239,68,68,.1);  color: var(--danger); }

  .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

  /* Email checks */
  .email-section { margin-bottom: 20px; }

  .email-section h3 {
    font-size: .75rem;
    font-family: var(--mono);
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
  }

  .check-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(30,34,48,.5);
  }

  .check-row:last-child { border-bottom: none; }

  .check-icon {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
    font-size: .75rem;
  }

  .check-icon.ok   { background: rgba(34,197,94,.15); color: var(--success); }
  .check-icon.fail { background: rgba(239,68,68,.12); color: var(--danger); }
  .check-icon.warn { background: rgba(245,158,11,.12); color: var(--warn); }

  .check-label {
    font-weight: 700;
    font-size: .88rem;
    margin-bottom: 2px;
  }

  .check-detail {
    font-family: var(--mono);
    font-size: .75rem;
    color: var(--muted);
    word-break: break-all;
  }

  .check-detail.highlight { color: var(--text); }

  .rec-tag {
    font-size: .65rem;
    font-family: var(--mono);
    padding: 1px 7px;
    border-radius: 4px;
    background: rgba(124,58,237,.2);
    color: #a78bfa;
    margin-left: 8px;
    vertical-align: middle;
  }

  /* Summary bar */
  .summary {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background: #0d0f14;
    border: 1px solid var(--border);
    border-radius: 8px;
    margin-bottom: 20px;
    font-family: var(--mono);
    font-size: .82rem;
    flex-wrap: wrap;
  }

  .summary-item { display: flex; align-items: center; gap: 6px; }
  .summary-val  { color: var(--text); font-weight: 700; }
  .summary-key  { color: var(--muted); }

  /* Error box */
  .error-box {
    padding: 14px 18px;
    background: rgba(239,68,68,.08);
    border: 1px solid rgba(239,68,68,.25);
    border-radius: 8px;
    font-family: var(--mono);
    font-size: .85rem;
    color: var(--danger);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  /* Info tooltip row */
  .info-note {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 14px;
    background: rgba(124,58,237,.07);
    border: 1px solid rgba(124,58,237,.2);
    border-radius: 8px;
    font-size: .8rem;
    color: #a78bfa;
    margin-top: 10px;
  }

  @media (max-width: 540px) {
    .input-row { flex-direction: column; }
    .tab span  { display: none; }
    .metric-val { font-size: 1.3rem; }
  }
</style>
</head>
<body>
<div class="wrap">

  <header>
    <div class="logo">
      <div class="logo-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="3" stroke="#00e5ff" stroke-width="1.5"/>
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" stroke="#00e5ff" stroke-width="1.5" stroke-dasharray="3 2"/>
          <path d="M2 12h20M12 2v20" stroke="#00e5ff" stroke-width="1" opacity=".4"/>
        </svg>
      </div>
      <h1>NetProbe</h1>
    </div>
    <div class="tagline">// Network Diagnostics Console</div>
  </header>

  <!-- Tabs -->
  <div class="tabs" role="tablist">
    <button class="tab active" data-tab="speed" role="tab">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
      <span>Speed Test</span>
    </button>
    <button class="tab" data-tab="ports" role="tab">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
      <span>Port Scanner</span>
    </button>
    <button class="tab" data-tab="email" role="tab">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      <span>Email Check</span>
    </button>
  </div>

  <!-- Speed Panel -->
  <div class="tab-panel" id="panel-speed">
    <div class="card">
      <div class="card-title">// Target Host</div>
      <div class="input-row">
        <div class="input-wrap">
          <span class="input-prefix">HOST›</span>
          <input type="text" id="speed-host" placeholder="example.com or 1.2.3.4">
        </div>
        <button class="btn-run" id="speed-run">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          Run Test
        </button>
      </div>
      <div class="status-bar" id="speed-status">
        <div class="spinner"></div>
        <span id="speed-status-text">Probing target…</span>
      </div>
    </div>
    <div id="results-speed"></div>
  </div>

  <!-- Ports Panel -->
  <div class="tab-panel" id="panel-ports" style="display:none">
    <div class="card">
      <div class="card-title">// Target Host</div>
      <div class="input-row">
        <div class="input-wrap">
          <span class="input-prefix">HOST›</span>
          <input type="text" id="ports-host" placeholder="example.com or 1.2.3.4">
        </div>
        <button class="btn-run" id="ports-run">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Scan
        </button>
      </div>
      <div class="port-options">
        <span class="port-options label">Ports:</span>
        <button class="btn-chip active" data-preset="common">Common (20)</button>
        <button class="btn-chip" data-preset="custom">Custom:</button>
        <input type="text" class="input-wrap port-custom" id="ports-custom" placeholder="80,443,8080,…" style="display:none">
      </div>
      <div class="status-bar" id="ports-status">
        <div class="spinner"></div>
        <span>Scanning ports — this may take a moment…</span>
      </div>
    </div>
    <div id="results-ports"></div>
  </div>

  <!-- Email Panel -->
  <div class="tab-panel" id="panel-email" style="display:none">
    <div class="card">
      <div class="card-title">// Domain to Check</div>
      <div class="input-row">
        <div class="input-wrap">
          <span class="input-prefix">HOST›</span>
          <input type="text" id="email-host" placeholder="example.com">
        </div>
        <button class="btn-run" id="email-run">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          Check
        </button>
      </div>
      <div class="status-bar" id="email-status">
        <div class="spinner"></div>
        <span>Checking DNS records and connecting to mail ports…</span>
      </div>
    </div>
    <div id="results-email"></div>
  </div>

</div><!-- /wrap -->

<script>
// ── Tab switching ──────────────────────────────────────────────
document.querySelectorAll('.tab').forEach(t => {
  t.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(x => x.style.display = 'none');
    t.classList.add('active');
    document.getElementById('panel-' + t.dataset.tab).style.display = '';
  });
});

// ── Port preset chips ──────────────────────────────────────────
let portPreset = 'common';
document.querySelectorAll('[data-preset]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('[data-preset]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    portPreset = btn.dataset.preset;
    document.getElementById('ports-custom').style.display = portPreset === 'custom' ? '' : 'none';
  });
});

// ── Enter key support ──────────────────────────────────────────
document.getElementById('speed-host').addEventListener('keydown', e => e.key === 'Enter' && runSpeed());
document.getElementById('ports-host').addEventListener('keydown', e => e.key === 'Enter' && runPorts());
document.getElementById('email-host').addEventListener('keydown', e => e.key === 'Enter' && runEmail());

document.getElementById('speed-run').addEventListener('click', runSpeed);
document.getElementById('ports-run').addEventListener('click', runPorts);
document.getElementById('email-run').addEventListener('click', runEmail);

// ── Helpers ────────────────────────────────────────────────────
function apiUrl(action, params) {
  const u = new URLSearchParams({ action, ...params });
  return 'api.php?' + u.toString();
}

function setStatus(id, visible, text) {
  const el = document.getElementById(id);
  el.classList.toggle('visible', visible);
  if (text) el.querySelector('span:last-child').textContent = text;
}

function colorMs(ms, low, high) {
  if (ms === null || ms === undefined) return 'null-val';
  if (ms <= low) return 'good';
  if (ms <= high) return 'warn';
  return 'bad';
}

function fmt(val, unit, decimals = 0) {
  if (val === null || val === undefined) return { val: '—', cls: 'null-val' };
  return { val: Number(val).toFixed(decimals) + unit, cls: '' };
}

// ── Speed Test ────────────────────────────────────────────────
async function runSpeed() {
  const host = document.getElementById('speed-host').value.trim();
  if (!host) return;
  const btn = document.getElementById('speed-run');
  btn.disabled = true;
  setStatus('speed-status', true, 'Resolving DNS…');
  document.getElementById('results-speed').innerHTML = '';

  try {
    setStatus('speed-status', true, 'Probing target — connecting…');
    const res = await fetch(apiUrl('speed', { target: host }));
    const data = await res.json();
    setStatus('speed-status', false);

    if (data.error) {
      showError('results-speed', data.error);
      return;
    }

    const r = data.results;

    const metrics = [
      { label: 'DNS Lookup',    v: r.dns_ms,         unit: 'ms', low: 20,   high: 100 },
      { label: 'TCP Connect',   v: r.tcp_connect_ms, unit: 'ms', low: 50,   high: 200 },
      { label: 'TTFB',          v: r.ttfb_ms,        unit: 'ms', low: 200,  high: 800 },
      { label: 'Total Load',    v: r.total_time_ms,  unit: 'ms', low: 500,  high: 2000 },
      { label: 'Ping (avg)',    v: r.ping_ms,        unit: 'ms', low: 30,   high: 100 },
      { label: 'Throughput',    v: r.throughput_kbps, unit: ' KB/s', low: -1, high: -1 },
    ];

    const cards = metrics.map(m => {
      let cls, display;
      if (m.v === null || m.v === undefined) {
        cls = 'null-val'; display = '—';
      } else {
        display = Number(m.v).toFixed(m.unit === ' KB/s' ? 1 : 0) + m.unit;
        cls = m.low < 0 ? '' : colorMs(m.v, m.low, m.high);
      }
      return `<div class="metric">
        <div class="metric-val ${cls}">${display}</div>
        <div class="metric-label">${m.label}</div>
      </div>`;
    }).join('');

    const httpBadge = r.http_status
      ? `<span class="badge ${r.http_status < 400 ? 'open' : 'closed'}">${r.http_status}</span>`
      : '';

    const dlSize = r.download_bytes
      ? (r.download_bytes < 1024 ? r.download_bytes + ' B'
       : r.download_bytes < 1048576 ? (r.download_bytes/1024).toFixed(1) + ' KB'
       : (r.download_bytes/1048576).toFixed(2) + ' MB')
      : '—';

    document.getElementById('results-speed').innerHTML = `
      <div class="result-section">
        <div class="card">
          <div class="card-title">// Connection Metrics — ${escHtml(data.target)}</div>
          <div class="summary">
            <div class="summary-item"><span class="summary-key">IP</span> <span class="summary-val">${escHtml(r.resolved_ip)}</span></div>
            <div class="summary-item"><span class="summary-key">HTTP</span> ${httpBadge || '<span class="summary-val">—</span>'}</div>
            <div class="summary-item"><span class="summary-key">Downloaded</span> <span class="summary-val">${dlSize}</span></div>
            ${r.redirect_url ? `<div class="summary-item"><span class="summary-key">Redirect→</span> <span class="summary-val">${escHtml(r.redirect_url)}</span></div>` : ''}
          </div>
          <div class="metric-grid">${cards}</div>
          ${!r.ping_available ? `<div class="info-note" style="margin-top:12px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            ICMP ping unavailable — blocked by the server firewall or host restrictions.
          </div>` : ''}
        </div>
      </div>`;
  } catch(e) {
    showError('results-speed', 'Request failed: ' + e.message);
    setStatus('speed-status', false);
  }
  btn.disabled = false;
}

// ── Port Scan ─────────────────────────────────────────────────
async function runPorts() {
  const host = document.getElementById('ports-host').value.trim();
  if (!host) return;
  const btn = document.getElementById('ports-run');
  btn.disabled = true;
  const customPorts = document.getElementById('ports-custom').value.trim();
  const portsParam = portPreset === 'custom' ? customPorts : 'common';
  setStatus('ports-status', true);
  document.getElementById('results-ports').innerHTML = '';

  try {
    const res = await fetch(apiUrl('ports', { target: host, ports: portsParam }));
    const data = await res.json();
    setStatus('ports-status', false);

    if (data.error) { showError('results-ports', data.error); return; }

    const rows = data.ports.map(p => `
      <tr>
        <td>${p.port}</td>
        <td>${escHtml(p.service)}</td>
        <td><span class="badge ${p.open ? 'open' : 'closed'}"><span class="dot"></span>${p.open ? 'OPEN' : 'CLOSED'}</span></td>
        <td style="color:var(--muted)">${p.open ? p.ms + ' ms' : '—'}</td>
      </tr>`).join('');

    document.getElementById('results-ports').innerHTML = `
      <div class="result-section">
        <div class="card">
          <div class="card-title">// Scan Results — ${escHtml(data.target)}</div>
          <div class="summary">
            <div class="summary-item"><span class="summary-key">IP</span> <span class="summary-val">${escHtml(data.resolved_ip)}</span></div>
            <div class="summary-item"><span class="summary-key">Scanned</span> <span class="summary-val">${data.scanned}</span></div>
            <div class="summary-item"><span class="summary-key">Open</span> <span class="summary-val" style="color:var(--success)">${data.open_count}</span></div>
            <div class="summary-item"><span class="summary-key">Closed</span> <span class="summary-val" style="color:var(--danger)">${data.scanned - data.open_count}</span></div>
          </div>
          <table class="port-table">
            <thead><tr><th>Port</th><th>Service</th><th>Status</th><th>Latency</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>`;
  } catch(e) {
    showError('results-ports', 'Request failed: ' + e.message);
    setStatus('ports-status', false);
  }
  btn.disabled = false;
}

// ── Email Check ───────────────────────────────────────────────
async function runEmail() {
  const host = document.getElementById('email-host').value.trim();
  if (!host) return;
  const btn = document.getElementById('email-run');
  btn.disabled = true;
  setStatus('email-status', true);
  document.getElementById('results-email').innerHTML = '';

  try {
    const res = await fetch(apiUrl('email', { target: host }));
    const data = await res.json();
    setStatus('email-status', false);

    if (data.error) { showError('results-email', data.error); return; }

    const c = data.checks;

    // MX records
    const mxRows = c.mx_records.length
      ? c.mx_records.map(m => `<div class="check-detail">Priority ${m.priority} — ${escHtml(m.host)}</div>`).join('')
      : '<div class="check-detail">No MX records found</div>';

    // DNS section
    const dnsSection = `
      <div class="email-section">
        <h3>DNS Records</h3>
        ${checkRow(c.mx_ok, 'MX Records', mxRows)}
        ${checkRow(c.spf_ok, 'SPF Record', c.spf_record ? `<div class="check-detail highlight">${escHtml(c.spf_record)}</div>` : '<div class="check-detail">Missing — email may be marked as spam</div>', !c.spf_ok ? 'warn' : null)}
        ${checkRow(c.dmarc_ok, 'DMARC Record', c.dmarc_record ? `<div class="check-detail highlight">${escHtml(c.dmarc_record)}</div>` : '<div class="check-detail">Missing — no DMARC policy found</div>', !c.dmarc_ok ? 'warn' : null)}
      </div>`;

    // Port section
    const portRows = c.ports.map(p => {
      const details = buildPortDetails(p);
      const statusOk = p.open;
      const icon = statusOk ? 'ok' : 'fail';
      const label = `Port ${p.port} — ${escHtml(p.label)}${p.recommended ? '<span class="rec-tag">RECOMMENDED</span>' : ''}`;
      return checkRow(statusOk, label, details, p.open ? null : 'fail');
    }).join('');

    const portSection = `
      <div class="email-section">
        <h3>Mail Ports — ${escHtml(c.tested_mail_host)}</h3>
        ${portRows}
      </div>`;

    // Encryption summary
    const encSummary = buildEncSummary(c.ports);

    document.getElementById('results-email').innerHTML = `
      <div class="result-section">
        <div class="card">
          <div class="card-title">// Email Configuration — ${escHtml(data.target)}</div>
          ${dnsSection}
          ${portSection}
          ${encSummary}
        </div>
      </div>`;
  } catch(e) {
    showError('results-email', 'Request failed: ' + e.message);
    setStatus('email-status', false);
  }
  btn.disabled = false;
}

function buildPortDetails(p) {
  let lines = [];
  if (!p.open) {
    lines.push(`<div class="check-detail">Port closed or filtered${p.error ? ' — ' + escHtml(p.error) : ''}</div>`);
    return lines.join('');
  }
  lines.push(`<div class="check-detail"><b style="color:var(--text)">Encryption:</b> ${tlsLabel(p.tls)}</div>`);
  if (p.details) {
    if (p.details.banner)   lines.push(`<div class="check-detail">Banner: ${escHtml(p.details.banner.substring(0,120))}</div>`);
    if (p.details.cert_subject) {
      const exp = p.details.cert_expired
        ? '<span style="color:var(--danger)"> ⚠ EXPIRED</span>'
        : '<span style="color:var(--success)"> ✓ valid</span>';
      lines.push(`<div class="check-detail">Cert: ${escHtml(p.details.cert_subject)} (${escHtml(p.details.cert_issuer)})${exp} — expires ${escHtml(p.details.cert_valid_to)}</div>`);
    }
    if (p.details.starttls_advertised !== undefined) {
      const stls = p.details.starttls_advertised;
      lines.push(`<div class="check-detail">STARTTLS: ${stls ? '<span style="color:var(--success)">supported</span>' : '<span style="color:var(--danger)">NOT advertised</span>'}</div>`);
    }
    if (p.details.tls_upgrade) {
      lines.push(`<div class="check-detail">TLS upgrade: <span style="color:var(--success)">successful</span></div>`);
    }
  }
  return lines.join('');
}

function buildEncSummary(ports) {
  const open = ports.filter(p => p.open);
  if (!open.length) return '';

  const smtpOk  = open.find(p => p.port === 587 || p.port === 465);
  const imapOk  = open.find(p => p.port === 993 || p.port === 143);
  const pop3Ok  = open.find(p => p.port === 995 || p.port === 110);

  return `<div class="info-note" style="margin-top:4px; flex-direction:column; align-items:flex-start; gap:6px">
    <div style="display:flex;gap:6px;align-items:center"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> <strong>Recommended settings</strong></div>
    <div style="font-family:var(--mono);font-size:.78rem;line-height:1.7">
      ${smtpOk ? `<b>SMTP:</b> ${smtpOk.port === 587 ? 'Port 587 with STARTTLS' : 'Port 465 with SSL/TLS (implicit)'}<br>` : '<b>SMTP:</b> No secure port detected<br>'}
      ${imapOk ? `<b>IMAP:</b> ${imapOk.port === 993 ? 'Port 993 with SSL/TLS (implicit)' : 'Port 143 with STARTTLS'}<br>` : '<b>IMAP:</b> No port detected<br>'}
      ${pop3Ok ? `<b>POP3:</b> ${pop3Ok.port === 995 ? 'Port 995 with SSL/TLS (implicit)' : 'Port 110 with STARTTLS'}<br>` : '<b>POP3:</b> Not available<br>'}
    </div>
  </div>`;
}

function tlsLabel(tls) {
  if (tls === 'implicit')  return '<span style="color:var(--success)">SSL/TLS (implicit) — ✓ encrypted</span>';
  if (tls === 'starttls')  return '<span style="color:var(--warn)">STARTTLS — upgrades to encrypted</span>';
  return '<span style="color:var(--danger)">None — plaintext</span>';
}

function checkRow(ok, label, detail, forceIcon) {
  const iconClass = forceIcon || (ok ? 'ok' : 'fail');
  const iconChar  = iconClass === 'ok' ? '✓' : iconClass === 'warn' ? '!' : '✗';
  return `<div class="check-row">
    <div class="check-icon ${iconClass}">${iconChar}</div>
    <div style="flex:1">
      <div class="check-label">${label}</div>
      ${detail}
    </div>
  </div>`;
}

function showError(targetId, msg) {
  document.getElementById(targetId).innerHTML = `
    <div class="error-box">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      ${escHtml(msg)}
    </div>`;
}

function escHtml(s) {
  if (s === null || s === undefined) return '';
  return String(s)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}
</script>
</body>
</html>
