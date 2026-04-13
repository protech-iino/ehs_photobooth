<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__.'/config.php';
$cfg = load_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?=htmlspecialchars($cfg['event_title'])?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  :root{
    --acc:#38bdf8; --accent-soft:rgba(56,189,248,.12);
    --danger:#ef4444; --ok:#22c55e; --warn:#facc15;
    --text:#f9fafb; --muted:#9ca3af; --line:rgba(148,163,184,.25);
    --panel:rgba(15,23,42,.96);
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  body{
    min-height:100vh;
    background:
      radial-gradient(circle at top,rgba(56,189,248,.25),transparent 55%),
      radial-gradient(circle at bottom,rgba(56,189,248,.18),transparent 60%),
      #020617;
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
    color:var(--text);
    display:flex;justify-content:center;align-items:center;
    padding:clamp(.75rem,2vw,1.5rem);
  }

  /* BIG SHELL: auto-fit all monitors */
  .shell{
    width:min(1680px, 100%);
    height:calc(100vh - clamp(1.5rem,4vw,3rem));
    max-height:1100px;
    min-height:720px;
    background:var(--panel);
    border-radius:1.6rem;
    border:1px solid rgba(148,163,184,.35);
    box-shadow:0 24px 80px rgba(15,23,42,.9);
    padding:clamp(1rem,2.2vw,1.6rem);
    backdrop-filter:blur(16px);
    overflow:hidden;
    display:grid;
    grid-template-columns: minmax(0, 2.25fr) minmax(0, .95fr);
    gap:clamp(.9rem,2vw,1.35rem);
  }
  @media(max-width:980px){
    body{align-items:flex-start;}
    .shell{
      height:auto;max-height:none;min-height:auto;
      grid-template-columns: 1fr;
    }
  }

  .panel{
    height:100%;
    background:radial-gradient(circle at top left,#1f2937,#020617);
    border-radius:1.25rem;
    border:1px solid rgba(148,163,184,.35);
    padding:clamp(.85rem,1.6vw,1rem);
    position:relative; overflow:hidden;
  }
  .panel::before{
    content:"";position:absolute;inset:-40%;
    background:
      radial-gradient(circle at 0 0,rgba(56,189,248,.18),transparent 55%),
      radial-gradient(circle at 100% 0,rgba(56,189,248,.18),transparent 55%);
    opacity:.35;pointer-events:none;
  }
  .panel-inner{position:relative;z-index:1;height:100%;display:flex;flex-direction:column;gap:.75rem;}

  .header{display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem;}
  .title-main{font-size:clamp(1.05rem,1.2vw + .7rem,1.6rem);font-weight:900;letter-spacing:.08em;text-transform:uppercase;}
  .title-sub{font-size:.86rem;color:var(--muted);margin-top:.15rem;}
  .badge{
    padding:.25rem .7rem;border-radius:999px;
    background:var(--accent-soft);color:var(--acc);
    font-size:.75rem;border:1px solid rgba(56,189,248,.4);
    white-space:nowrap;
  }
  .headerRight{display:flex;gap:.55rem;align-items:center;flex-wrap:wrap;justify-content:flex-end;}
  .miniBtn{
    appearance:none;border:1px solid rgba(148,163,184,.30);
    background:rgba(2,6,23,.55);
    color:#cbd5e1;
    padding:.45rem .7rem;
    border-radius:999px;
    font-size:.82rem;
    font-weight:900;
    cursor:pointer;
    display:inline-flex;align-items:center;gap:.4rem;
    transition:transform .12s ease, background .12s ease;
    user-select:none;
  }
  .miniBtn:hover{background:rgba(2,6,23,.80);transform:translateY(-1px);}
  .miniBtn:active{transform:translateY(0);}

  /* VIDEO: preview should reflect capture framing */
  .videoBox{
    position:relative;
    flex:1;
    min-height:0;
    border-radius:1.15rem;
    overflow:hidden;
    border:1px solid rgba(148,163,184,.35);
    background:#000;
    display:flex;
    align-items:center;
    justify-content:center;
  }
  /* Use dynamic aspect ratio (set by JS) */
  .videoBox::before{
    content:"";
    float:left;
    padding-top: calc(100% / var(--v-ar, 1.7777778));
  }
  .videoBox::after{content:"";display:block;clear:both;}
  .videoStage{
    position:absolute; inset:0;
    display:flex; align-items:center; justify-content:center;
    background:#000;
  }

  #video{
    width:100%;
    height:100%;
    display:block;
    object-fit:contain; /* default: no cut */
    background:#000;
  }
  #video.fill{ object-fit:cover; } /* optional fill mode */

  /* Big center overlay */
  .centerOverlay{
    position:absolute; inset:0;
    display:none;
    align-items:center; justify-content:center;
    pointer-events:none;
    text-align:center;
    background:radial-gradient(circle at center, rgba(2,6,23,.35), rgba(2,6,23,.60));
    backdrop-filter: blur(2px);
    z-index:5;
  }
  .overlayCard{
    padding:clamp(1rem,2.2vw,1.4rem) clamp(1.2rem,2.6vw,1.8rem);
    border-radius:1.25rem;
    border:1px solid rgba(148,163,184,.35);
    background:rgba(2,6,23,.55);
    box-shadow:0 18px 60px rgba(0,0,0,.45);
    min-width:min(560px, 92%);
  }
  .overlayTitle{
    font-size:clamp(1.8rem,2.6vw,2.9rem);
    font-weight:1000;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:#e5e7eb;
  }
  .overlayNum{
    margin-top:.35rem;
    font-size:clamp(3.6rem,6.5vw,7.2rem);
    font-weight:1100;
    line-height:1;
    color:var(--warn);
    text-shadow:0 10px 30px rgba(0,0,0,.6);
  }
  .overlaySub{
    margin-top:.55rem;
    font-size:clamp(1rem,1.25vw,1.15rem);
    color:rgba(229,231,235,.88);
  }

  /* Flash effect */
  .flash{
    position:absolute; inset:0;
    background:#fff;
    opacity:0;
    pointer-events:none;
    z-index:8;
  }
  .flash.on{ animation: flashBlink 180ms ease-out; }
  @keyframes flashBlink{
    0%{opacity:0;}
    25%{opacity:.95;}
    100%{opacity:0;}
  }

  .controls{
    display:flex;flex-wrap:wrap;gap:.65rem;align-items:center;justify-content:space-between;
  }
  .btn{
    appearance:none;border:none;cursor:pointer;
    padding:.78rem 1.35rem;border-radius:999px;
    font-weight:950;font-size:1rem;
    display:inline-flex;align-items:center;gap:.5rem;
    transition:transform .12s ease,box-shadow .12s ease,background .12s ease,opacity .12s ease;
    user-select:none;
  }
  .btn-primary{background:linear-gradient(135deg,#22d3ee,#0ea5e9);color:#0b1120;box-shadow:0 10px 30px rgba(56,189,248,.45);}
  .btn-primary:hover{transform:translateY(-1px);box-shadow:0 16px 45px rgba(56,189,248,.55);}
  .btn-primary:disabled{opacity:.55;cursor:not-allowed;box-shadow:none;transform:none;}

  .shot-indicators{display:flex;gap:.45rem;align-items:center;font-size:.82rem;color:var(--muted);}
  .dot{width:.62rem;height:.62rem;border-radius:999px;border:1px solid rgba(148,163,184,.7);background:rgba(15,23,42,1);}
  .dot.done{background:var(--ok);border-color:#4ade80;box-shadow:0 0 0 3px rgba(34,197,94,.22);}
  .dot.current{background:var(--warn);border-color:#fbbf24;box-shadow:0 0 0 3px rgba(250,204,21,.22);}

  .statusBar{
    border:1px solid var(--line);
    background:rgba(2,6,23,.55);
    border-radius:1rem;
    padding:.75rem .85rem;
    display:flex;justify-content:space-between;gap:.75rem;align-items:center;
  }
  .statusText{font-size:.85rem;color:var(--muted);line-height:1.35;}
  .statusText .ok{color:#86efac;font-weight:900;}
  .statusText .err{color:#fca5a5;font-weight:900;}
  .statusText .warn{color:#fde68a;font-weight:900;}
  .pill{
    font-size:.78rem;color:#c7d2fe;
    padding:.35rem .6rem;border-radius:999px;
    border:1px solid rgba(148,163,184,.25);
    background:rgba(15,23,42,.75);
    white-space:nowrap;
  }

  /* RIGHT PANEL: latest shots in its own container */
  .rightTitle{
    font-size:.82rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);
    display:flex;justify-content:space-between;align-items:center;
    margin-bottom:.6rem;
  }
  .thumbRow{
  display:grid;
  grid-template-columns: 1fr;
  gap:.7rem;
  flex:1;
  min-height:0;
  overflow:auto;                 /* ✅ scroll if kulang height */
  padding-right:.25rem;          /* small space for scrollbar */
}
  @media(min-width:981px){
    .thumbRow{ grid-template-columns: 1fr; }
  }
  .thumb{
  border-radius:1.05rem;
  border:1px solid rgba(148,163,184,.35);
  background:rgba(2,6,23,.65);
  overflow:hidden;
  position:relative;

  aspect-ratio:auto !important;  /* ✅ remove 16/9 lock */
  height:clamp(120px, 18vh, 185px); /* ✅ consistent size, no patong */
  display:flex;
  align-items:center;
  justify-content:center;
}
  /* IMPORTANT: same framing as preview by default */
  .thumb img{
  width:100%;
  height:100%;
  object-fit:contain;            /* same framing */
  background:#000;
  display:none;
}

  .thumb .label{
    position:absolute;left:.6rem;top:.6rem;
    font-size:.78rem;font-weight:1000;padding:.22rem .5rem;border-radius:.6rem;
    background:rgba(2,6,23,.72);
    border:1px solid rgba(148,163,184,.25);
    color:#e5e7eb;
  }
  .thumb .empty{color:rgba(148,163,184,.7);font-size:.9rem;}
  .rightHint{
    margin-top:.6rem;
    color:var(--muted);
    font-size:.85rem;
    line-height:1.45;
  }

  /* --- MODALS (Layout preview + Final output) --- */
  .modal{
    position:fixed; inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
    background:rgba(2,6,23,.88);
    backdrop-filter: blur(8px);
    padding:18px;
  }
  .modal.show{display:flex;}
  .modalCard{
    width:min(980px, 100%);
    max-height:calc(100vh - 36px);
    background:rgba(15,23,42,.96);
    border:1px solid rgba(148,163,184,.35);
    border-radius:18px;
    box-shadow:0 30px 120px rgba(0,0,0,.65);
    overflow:hidden;
    display:flex;
    flex-direction:column;
  }
  .modalHead{
    padding:14px 14px 12px;
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    border-bottom:1px solid rgba(148,163,184,.22);
    background:linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.15));
  }
  .modalTitle{
    font-weight:1000;
    letter-spacing:.08em;
    text-transform:uppercase;
    font-size:.95rem;
    color:#e5e7eb;
  }
  .modalActions{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;justify-content:flex-end;}
  .modalBtn{
    appearance:none;
    border:1px solid rgba(148,163,184,.28);
    background:rgba(2,6,23,.55);
    color:#e5e7eb;
    padding:.48rem .78rem;
    border-radius:999px;
    font-weight:950;
    font-size:.85rem;
    cursor:pointer;
  }
  .modalBtn:hover{background:rgba(2,6,23,.85);}
  .modalBtn.primary{
    border:none;
    background:linear-gradient(135deg,#22d3ee,#0ea5e9);
    color:#0b1120;
  }
  .modalBody{
    padding:14px;
    overflow:auto;
  }

  .layoutWrap{
    border-radius:16px;
    border:1px solid rgba(148,163,184,.28);
    background:#020617;
    overflow:hidden;
  }
  .layoutWrap img{display:block;width:100%;height:auto;}
  .layoutInfo{
    margin-top:12px;
    color:var(--muted);
    font-size:.9rem;
    line-height:1.6;
  }
  .layoutInfo li{margin-left:1.1rem;margin-bottom:.25rem;}

  #finalImage{
    width:100%;
    border-radius:16px;
    border:1px solid rgba(148,163,184,.35);
    display:none;
    cursor: zoom-in;
    background:#0b1220;
  }
  .finalMeta{
    margin-top:10px;
    color:var(--muted);
    font-size:.88rem;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
  }
  a.download-link{
    display:none;
    font-size:.92rem;
    color:var(--acc);
    text-decoration:none;
    font-weight:900;
  }

  /* FINAL OUTPUT FULLSCREEN VIEWER */
  .fsViewer{
    position:fixed; inset:0;
    display:none;
    background:rgba(2,6,23,.98);
    z-index:10050;
    padding:56px 56px 36px;
  }
  .fsHud{
    position:absolute; top:14px; left:14px; right:14px;
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    pointer-events:none;
  }
  .fsHint{
    pointer-events:none;
    font-size:.82rem;
    color:#cbd5e1;
    padding:.5rem .75rem;
    border-radius:999px;
    background:rgba(2,6,23,.70);
    border:1px solid rgba(148,163,184,.25);
    backdrop-filter: blur(8px);
  }
  .fsExit{
    pointer-events:auto;
    appearance:none;
    border:1px solid rgba(148,163,184,.25);
    background:rgba(2,6,23,.70);
    color:#e5e7eb;
    padding:.5rem .9rem;
    border-radius:999px;
    font-weight:950;
    cursor:pointer;
    backdrop-filter: blur(8px);
  }
  .fsExit:hover{ background:rgba(2,6,23,.90); }

  .fsFrame{
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
  }
  .fsFrame img{
    max-width:100%;
    max-height:100%;
    width:auto;
    height:auto;
    border-radius:18px;
    box-shadow:0 30px 120px rgba(0,0,0,.65);
    border:1px solid rgba(148,163,184,.20);
    background:#0b1220;
  }

  @media(max-width:980px){
    .fsViewer{ padding:48px 14px 14px; }
    .fsFrame img{ border-radius:14px; }
    .modalBody{padding:12px;}
    .thumbRow{ grid-template-columns: repeat(2, minmax(0,1fr)); }
  }
</style>
</head>
<body>

<div class="shell">
  <!-- LEFT: CAMERA ONLY -->
  <section class="panel">
    <div class="panel-inner">
      <div class="header">
        <div>
          <div class="title-main"><?=htmlspecialchars($cfg['event_title'])?></div>
          <div class="title-sub">4-shot strip • <?= (int)$cfg['countdown_seconds'] ?>-second countdown • auto layout</div>
        </div>

        <div class="headerRight">
          <button class="miniBtn" id="layoutBtn" type="button">🖼️ Layout Preview</button>
          <button class="miniBtn" id="fitBtn" type="button" title="Toggle camera fit">🔍 Fit: Contain</button>
          <div class="badge" id="camBadge">Initializing…</div>
        </div>
      </div>

      <div class="videoBox" id="videoBox">
        <div class="videoStage" id="videoStage">
          <video id="video" autoplay playsinline></video>

          <div class="flash" id="flash"></div>

          <div class="centerOverlay" id="centerOverlay">
            <div class="overlayCard">
              <div class="overlayTitle" id="overlayTitle">READY</div>
              <div class="overlayNum" id="overlayNum">5</div>
              <div class="overlaySub" id="overlaySub">Please stay in frame</div>
            </div>
          </div>
        </div>
      </div>

      <div class="controls">
        <button id="startBtn" class="btn btn-primary" type="button"><span>Start Photobooth</span></button>

        <div class="shot-indicators">
          <span>Shots:</span>
          <div id="dot0" class="dot current"></div>
          <div id="dot1" class="dot"></div>
          <div id="dot2" class="dot"></div>
          <div id="dot3" class="dot"></div>
        </div>
      </div>

      <div class="statusBar">
        <div class="statusText" id="statusText">Allow camera access, then click <strong>Start Photobooth</strong>.</div>
        <div class="pill" id="phasePill">READY</div>
      </div>
    </div>
  </section>

  <!-- RIGHT: LATEST SHOTS -->
  <section class="panel">
    <div class="panel-inner">
      <div class="rightTitle">
        <span>Latest Shots</span>
        <span class="pill" id="shotCountPill">0 / 4</span>
      </div>

      <div class="thumbRow">
        <div class="thumb"><span class="label">1</span><img id="thumb0"><span class="empty">Empty</span></div>
        <div class="thumb"><span class="label">2</span><img id="thumb1"><span class="empty">Empty</span></div>
        <div class="thumb"><span class="label">3</span><img id="thumb2"><span class="empty">Empty</span></div>
        <div class="thumb"><span class="label">4</span><img id="thumb3"><span class="empty">Empty</span></div>
      </div>

      <div class="rightHint">
        
      </div>
    </div>
  </section>
</div>

<!-- LAYOUT PREVIEW MODAL -->
<div class="modal" id="layoutModal" aria-hidden="true">
  <div class="modalCard" role="dialog" aria-modal="true" aria-label="Layout Preview">
    <div class="modalHead">
      <div class="modalTitle">Layout Preview</div>
      <div class="modalActions">
        <button class="modalBtn" id="layoutFsBtn" type="button">⛶ Fullscreen</button>
        <button class="modalBtn" id="layoutCloseBtn" type="button">Close</button>
      </div>
    </div>
    <div class="modalBody">
      <div class="layoutWrap">
        <img id="layoutImg" src="<?=htmlspecialchars($cfg['template_path'])?>" alt="Photobooth template">
      </div>
      <ul class="layoutInfo">
        <li>Template should be a PNG with transparent windows for the 4 photos.</li>
        <li>After 4 shots, PHP will merge them into the template using GD.</li>
        <li>Output image is ready for print or sharing.</li>
      </ul>
    </div>
  </div>
</div>

<!-- FINAL OUTPUT MODAL (auto after generate) -->
<div class="modal" id="outputModal" aria-hidden="true">
  <div class="modalCard" role="dialog" aria-modal="true" aria-label="Final Output">
    <div class="modalHead">
      <div class="modalTitle">Final Output</div>
      <div class="modalActions">
        <button class="modalBtn" id="outputFsBtn" type="button">⛶ Fullscreen</button>
        <button class="modalBtn primary" id="shootAgainBtn" type="button">Shoot Again</button>
        <button class="modalBtn" id="outputCloseBtn" type="button">Close</button>
      </div>
    </div>
    <div class="modalBody">
      <img id="finalImage" alt="Final photobooth output">
      <div class="finalMeta">
        <a id="downloadLink" class="download-link" href="#" download="edustria_photobooth.png">⬇ Download finished strip</a>
        <span class="pill">Tip: click image to fullscreen</span>
      </div>
    </div>
  </div>
</div>

<!-- Final Output Fullscreen Viewer -->
<div class="fsViewer" id="fsViewer" aria-hidden="true">
  <div class="fsHud">
    <div class="fsHint">Final output preview • press <b>Esc</b> to exit</div>
    <button class="fsExit" id="fsExit" type="button">Exit</button>
  </div>
  <div class="fsFrame">
    <img id="fsImg" alt="Fullscreen final output">
  </div>
</div>

<script>
const BOOTH_CONFIG = <?= json_encode([
  'countdown_seconds' => (int)$cfg['countdown_seconds'],
  'template_path'     => $cfg['template_path'],
]); ?>;
</script>

<script>
const video         = document.getElementById('video');
const videoBox      = document.getElementById('videoBox');
const videoStage    = document.getElementById('videoStage');
const startBtn      = document.getElementById('startBtn');

const statusText    = document.getElementById('statusText');
const phasePill     = document.getElementById('phasePill');
const camBadge      = document.getElementById('camBadge');

const dots          = [0,1,2,3].map(i=>document.getElementById('dot'+i));
const finalImage    = document.getElementById('finalImage');
const downloadLink  = document.getElementById('downloadLink');

const shotCountPill = document.getElementById('shotCountPill');
const thumbs        = [0,1,2,3].map(i=>document.getElementById('thumb'+i));
const empties       = Array.from(document.querySelectorAll('.thumb .empty'));

const flashEl       = document.getElementById('flash');
const centerOverlay = document.getElementById('centerOverlay');
const overlayTitle  = document.getElementById('overlayTitle');
const overlayNum    = document.getElementById('overlayNum');
const overlaySub    = document.getElementById('overlaySub');

/* Layout modal */
const layoutBtn      = document.getElementById('layoutBtn');
const layoutModal    = document.getElementById('layoutModal');
const layoutCloseBtn = document.getElementById('layoutCloseBtn');
const layoutFsBtn    = document.getElementById('layoutFsBtn');

/* Output modal */
const outputModal    = document.getElementById('outputModal');
const outputCloseBtn = document.getElementById('outputCloseBtn');
const outputFsBtn    = document.getElementById('outputFsBtn');
const shootAgainBtn  = document.getElementById('shootAgainBtn');

/* Fit toggle */
const fitBtn = document.getElementById('fitBtn');
let fitMode = 'contain';

/* Final output fullscreen viewer elements */
const fsViewer = document.getElementById('fsViewer');
const fsExit   = document.getElementById('fsExit');
const fsImg    = document.getElementById('fsImg');

function showOverlay(title, num, sub=''){
  overlayTitle.textContent = title;
  overlayNum.textContent = String(num ?? '');
  overlaySub.textContent = sub || '';
  centerOverlay.style.display = 'flex';
}
function hideOverlay(){ centerOverlay.style.display = 'none'; }

function flash(){
  flashEl.classList.remove('on');
  void flashEl.offsetWidth;
  flashEl.classList.add('on');
}

/* ---- capture canvas ---- */
const snapCanvas = document.createElement('canvas');
const snapCtx    = snapCanvas.getContext('2d', { alpha:false });

let stream = null;
let isRunning = false;
let photos = [];
let currentShot = 0;

/* --- Sound: shutter + fallback beep --- */
const shutterAudio = new Audio('assets/shutter.wav'); // ensure file exists
shutterAudio.preload = 'auto';

function beepFallback(){
  try{
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const o = ctx.createOscillator();
    const g = ctx.createGain();
    o.type = 'square';
    o.frequency.value = 880;
    g.gain.value = 0.05;
    o.connect(g); g.connect(ctx.destination);
    o.start();
    setTimeout(()=>{ o.stop(); ctx.close(); }, 90);
  }catch(e){}
}
async function playShutter(){
  try{
    shutterAudio.currentTime = 0;
    await shutterAudio.play();
  }catch(e){ beepFallback(); }
}

/* ---- helpers ---- */
function ordinal(n){ return ['FIRST','SECOND','THIRD','FOURTH'][n-1] || (n+'th'); }
function setPhase(text, kind='READY'){ phasePill.textContent = kind; statusText.innerHTML = text; }
function updateShotCount(){ shotCountPill.textContent = `${photos.length} / 4`; }
function updateDots(){
  dots.forEach((d,i)=>{
    d.classList.remove('done','current');
    if(i < currentShot) d.classList.add('done');
    else if(i === currentShot && isRunning) d.classList.add('current');
    else if(!isRunning && i === currentShot && currentShot < 4) d.classList.add('current');
  });
}
function sleep(ms){ return new Promise(res=>setTimeout(res,ms)); }

/* ---- IMPORTANT: make preview aspect ratio match the real stream ---- */
function setVideoAspectFromStream(){
  const vw = video.videoWidth || 1280;
  const vh = video.videoHeight || 720;
  const ar = vw / vh;
  videoBox.style.setProperty('--v-ar', String(ar || 1.7777778));
}

/* ---- Object-fit math so CAPTURE matches PREVIEW ---- */
function drawVideoLikePreview(ctx, canvasW, canvasH){
  const vw = video.videoWidth || 1280;
  const vh = video.videoHeight || 720;

  // clear bg black (like preview)
  ctx.fillStyle = '#000';
  ctx.fillRect(0,0,canvasW,canvasH);

  const scaleContain = Math.min(canvasW/vw, canvasH/vh);
  const scaleCover   = Math.max(canvasW/vw, canvasH/vh);
  const scale = (fitMode === 'fill') ? scaleCover : scaleContain;

  const drawW = vw * scale;
  const drawH = vh * scale;
  const dx = (canvasW - drawW)/2;
  const dy = (canvasH - drawH)/2;

  ctx.drawImage(video, dx, dy, drawW, drawH);
}

function captureFrameToDataURL(){
  // Capture at the SAME aspect box as preview (videoBox area)
  const rect = videoStage.getBoundingClientRect();
  const dpr = Math.min(2, window.devicePixelRatio || 1); // cap to keep fast
  const cw = Math.max(1, Math.round(rect.width * dpr));
  const ch = Math.max(1, Math.round(rect.height * dpr));

  snapCanvas.width = cw;
  snapCanvas.height = ch;

  drawVideoLikePreview(snapCtx, cw, ch);
  return snapCanvas.toDataURL('image/png');
}

function updateLatestThumb(idx, dataUrl){
  const img = thumbs[idx];
  img.src = dataUrl;
  img.style.display = 'block';
  const empty = img.parentElement.querySelector('.empty');
  if (empty) empty.style.display = 'none';
}

/* ---- camera init ---- */
async function initCamera(){
  try{
    camBadge.textContent = "Requesting camera…";

    stream = await navigator.mediaDevices.getUserMedia({
      video:{
        width:{ideal:1920},
        height:{ideal:1080},
        aspectRatio:{ideal:16/9}
      },
      audio:false
    });

    video.srcObject = stream;

    video.addEventListener('loadedmetadata', () => {
      setVideoAspectFromStream();
      camBadge.textContent = "Webcam Ready";
      setPhase("Camera ready. Click <strong>Start Photobooth</strong> to begin.", "READY");
    });

  }catch(err){
    console.error(err);
    camBadge.textContent = "No Camera";
    setPhase("<span class='err'>Cannot access camera.</span> Please allow webcam permission.", "ERROR");
  }
}

async function runBigCountdown(seconds){
  const shotLabel = ordinal(currentShot+1);
  for(let i = seconds; i >= 1; i--){
    showOverlay("READY", i, `${shotLabel} SHOT IN ${i}…`);
    setPhase(`<span class='warn'>Ready</span> for <strong>${shotLabel}</strong> shot…`, "READY");
    await sleep(1000);
  }
  hideOverlay();
}

/* --- modal helpers --- */
function openModal(el){
  el.classList.add('show');
  el.setAttribute('aria-hidden','false');
}
function closeModal(el){
  el.classList.remove('show');
  el.setAttribute('aria-hidden','true');
}
function closeAllModals(){
  closeModal(layoutModal);
  closeModal(outputModal);
}
function isAnyModalOpen(){
  return layoutModal.classList.contains('show') || outputModal.classList.contains('show') || (fsViewer.style.display === 'block');
}

/* ---- main sequence ---- */
async function takeShotSequence(){
  isRunning = true;
  startBtn.disabled = true;
  updateDots();

  closeAllModals();

  photos = [];
  currentShot = 0;
  updateShotCount();

  thumbs.forEach(t=>{t.style.display='none'; t.src='';});
  empties.forEach(e=>e.style.display='block');

  finalImage.style.display = 'none';
  downloadLink.style.display = 'none';

  const cd = BOOTH_CONFIG.countdown_seconds || 5;

  // unlock audio on first user gesture
  try{ await shutterAudio.play(); shutterAudio.pause(); shutterAudio.currentTime = 0; }catch(e){}

  while(currentShot < 4){
    updateDots();
    await runBigCountdown(cd);

    flash();
    await playShutter();

    const dataUrl = captureFrameToDataURL(); // NOW matches preview framing
    photos.push(dataUrl);
    updateLatestThumb(currentShot, dataUrl);
    updateShotCount();

    showOverlay("CAPTURED", "✓", `${ordinal(currentShot+1)} SHOT CAPTURED!`);
    setPhase(`<span class='ok'>${ordinal(currentShot+1)} SHOT CAPTURED!</span> Hold still…`, "CAPTURED");
    await sleep(650);
    hideOverlay();

    currentShot++;
    updateDots();
    await sleep(260);
  }

  isRunning = false;
  updateDots();
  hideOverlay();

  setPhase("Uploading to server & composing layout…", "UPLOAD");
  await sendToServer();
  startBtn.disabled = false;
}

/* keep function name; auto-open output modal */
async function sendToServer(){
  try{
    const formData = new FormData();
    photos.forEach((p,idx)=>formData.append('photo'+idx,p));

    const response = await fetch('save_collage.php',{method:'POST',body:formData});
    const text = await response.text();

    let data;
    try { data = JSON.parse(text); }
    catch (e){
      console.error('Raw server response (not JSON):', text);
      setPhase("<span class='err'>Server error:</span><br>"+text.replace(/</g,"&lt;"), "ERROR");
      showOverlay("ERROR", "!", "Server returned invalid response");
      await sleep(1200); hideOverlay();
      return;
    }

    if(data.success){
      const url = data.url + "?v=" + Date.now();

      finalImage.src = url;
      finalImage.style.display = 'block';

      downloadLink.href = data.url;
      downloadLink.style.display = 'inline-flex';

      setPhase("<span class='ok'>Done!</span> Final output is ready.", "DONE");
      showOverlay("DONE", "✓", "Photobooth strip is ready!");
      await sleep(650);
      hideOverlay();

      openModal(outputModal);
    }else{
      setPhase("<span class='err'>Error:</span> "+(data.message || "unable to generate image"), "ERROR");
      showOverlay("ERROR", "!", data.message || "Unable to generate image");
      await sleep(1200); hideOverlay();
    }
  }catch(e){
    console.error(e);
    setPhase("<span class='err'>Network error</span> while uploading photos.", "ERROR");
    showOverlay("ERROR", "!", "Network error while uploading photos");
    await sleep(1200); hideOverlay();
  }
}

/* Final output fullscreen viewer */
function openFinalFullscreen(){
  if(!finalImage.src) return;
  fsImg.src = finalImage.src;
  fsViewer.style.display = 'block';
  fsViewer.setAttribute('aria-hidden','false');
}
function closeFinalFullscreen(){
  fsViewer.style.display = 'none';
  fsViewer.setAttribute('aria-hidden','true');
}

/* ---- layout modal actions ---- */
layoutBtn.addEventListener('click', ()=>openModal(layoutModal));
layoutCloseBtn.addEventListener('click', ()=>closeModal(layoutModal));
layoutModal.addEventListener('click', (e)=>{ if(e.target === layoutModal) closeModal(layoutModal); });

/* Fullscreen for layout modal card */
async function toggleFullscreen(el){
  const doc = document;
  const isFs = doc.fullscreenElement || doc.webkitFullscreenElement;
  try{
    if(!isFs){
      if(el.requestFullscreen) await el.requestFullscreen();
      else if(el.webkitRequestFullscreen) el.webkitRequestFullscreen();
    }else{
      if(doc.exitFullscreen) await doc.exitFullscreen();
      else if(doc.webkitExitFullscreen) doc.webkitExitFullscreen();
    }
  }catch(e){
    console.error(e);
    alert('Fullscreen blocked by browser. Try clicking inside the page first.');
  }
}
layoutFsBtn.addEventListener('click', ()=>toggleFullscreen(document.querySelector('#layoutModal .modalCard')));

/* ---- output modal actions ---- */
outputCloseBtn.addEventListener('click', ()=>closeModal(outputModal));
outputModal.addEventListener('click', (e)=>{ if(e.target === outputModal) closeModal(outputModal); });

outputFsBtn.addEventListener('click', openFinalFullscreen);
shootAgainBtn.addEventListener('click', ()=>{
  closeModal(outputModal);
  setPhase("Ready. Click <strong>Start Photobooth</strong> to shoot again.", "READY");
});

/* image click -> fullscreen */
finalImage.addEventListener('click', openFinalFullscreen);
fsExit.addEventListener('click', closeFinalFullscreen);
fsViewer.addEventListener('click', (e)=>{ if(e.target === fsViewer) closeFinalFullscreen(); });

/* ESC handling */
document.addEventListener('keydown', (e)=>{
  if(e.key === 'Escape'){
    if(fsViewer.style.display === 'block') closeFinalFullscreen();
    else if(layoutModal.classList.contains('show')) closeModal(layoutModal);
    else if(outputModal.classList.contains('show')) closeModal(outputModal);
  }
});

/* Fit toggle: Contain (no cut) vs Fill (cover) */
fitBtn.addEventListener('click', ()=>{
  if(fitMode === 'contain'){
    fitMode = 'fill';
    video.classList.add('fill');
    fitBtn.textContent = '🔍 Fit: Fill';
  }else{
    fitMode = 'contain';
    video.classList.remove('fill');
    fitBtn.textContent = '🔍 Fit: Contain';
  }
});

/* Keep aspect ratio updated if stream changes */
window.addEventListener('resize', ()=>{ /* preview is responsive; capture reads live size */ });

startBtn.addEventListener('click',()=>{
  if(isRunning) return;
  if(isAnyModalOpen()) closeAllModals();
  takeShotSequence();
});

updateDots();
updateShotCount();
initCamera();
</script>
</body>
</html>
