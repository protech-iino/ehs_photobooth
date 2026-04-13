<?php

// admin_template.php (NO PASSWORD) + VISUAL SLOT BOX EDITOR + OVERLAY UPLOAD + ROUNDED PHOTO SETTINGS + FULLSCREEN PREVIEW
session_start();
require_once __DIR__ . '/../auth.php';
require_once __DIR__.'/config.php';

$cfg = load_config();
$msg = '';
$err = '';

/* ---- ensure slots exist ---- */
if (empty($cfg['slots']) || !is_array($cfg['slots']) || count($cfg['slots']) < 4) {
  $cfg['slots'] = [
    ['x'=>60,'y'=>80,'w'=>520,'h'=>360],
    ['x'=>60,'y'=>470,'w'=>520,'h'=>360],
    ['x'=>620,'y'=>470,'w'=>520,'h'=>360],
    ['x'=>620,'y'=>80,'w'=>520,'h'=>360],
  ];
}

/* ---- defaults for overlay editor ---- */
if (empty($cfg['slot_overlay_color'])) $cfg['slot_overlay_color'] = '#22d3ee';
if (!isset($cfg['slot_overlay_opacity'])) $cfg['slot_overlay_opacity'] = 0.35;
if (!isset($cfg['slot_overlay_show_labels'])) $cfg['slot_overlay_show_labels'] = true;

/* ---- new defaults for overlay png + photo edge style ---- */
if (!isset($cfg['overlay_path'])) $cfg['overlay_path'] = '';     // PNG effects (snow) on top
if (!isset($cfg['corner_radius'])) $cfg['corner_radius'] = 22;   // px
if (!isset($cfg['corner_soft']))   $cfg['corner_soft']   = 6;    // px feather

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {

  $cfg['event_title']       = trim((string)($_POST['event_title'] ?? $cfg['event_title']));
  $cfg['countdown_seconds'] = max(1, (int)($_POST['countdown_seconds'] ?? 5));

  // Overlay settings
  $cfg['slot_overlay_color'] = (string)($_POST['slot_overlay_color'] ?? $cfg['slot_overlay_color']);
  $cfg['slot_overlay_opacity'] = max(0, min(1, (float)($_POST['slot_overlay_opacity'] ?? $cfg['slot_overlay_opacity'])));
  $cfg['slot_overlay_show_labels'] = isset($_POST['slot_overlay_show_labels']) ? true : false;

  // Photo edge style (for final output)
  $cfg['corner_radius'] = max(0, (int)($_POST['corner_radius'] ?? $cfg['corner_radius']));
  $cfg['corner_soft']   = max(0, (int)($_POST['corner_soft'] ?? $cfg['corner_soft']));

  // Slots
  $slots = [];
  for ($i=0; $i<4; $i++) {
    $slots[$i] = [
      'x' => (int)($_POST["slot{$i}_x"] ?? 0),
      'y' => (int)($_POST["slot{$i}_y"] ?? 0),
      'w' => max(1,(int)($_POST["slot{$i}_w"] ?? 1)),
      'h' => max(1,(int)($_POST["slot{$i}_h"] ?? 1)),
    ];
  }
  $cfg['slots'] = $slots;

  // Template upload (optional) - background template (may contain text/graphics)
  if (!empty($_FILES['template_file']['name']) && ($_FILES['template_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $tmp  = $_FILES['template_file']['tmp_name'];
    $info = @getimagesize($tmp);
    if ($info === false || ($info[2] ?? 0) !== IMAGETYPE_PNG) {
      $err = 'Template must be a PNG image.';
    } else {
      $name = 'template_'.date('Ymd_His').'.png';
      $destDir  = __DIR__.'/assets';
      if (!is_dir($destDir)) @mkdir($destDir,0775,true);
      $destPath = $destDir.'/'.$name;
      if (@move_uploaded_file($tmp, $destPath)) {
        $cfg['template_path'] = 'assets/'.$name;
      } else {
        $err = 'Failed to move uploaded template file.';
      }
    }
  }

  // Overlay upload (optional) - snow/effects ON TOP
  if (!$err && !empty($_FILES['overlay_file']['name']) && ($_FILES['overlay_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $tmp  = $_FILES['overlay_file']['tmp_name'];
    $info = @getimagesize($tmp);
    if ($info === false || ($info[2] ?? 0) !== IMAGETYPE_PNG) {
      $err = 'Overlay must be a PNG image.';
    } else {
      $name = 'overlay_'.date('Ymd_His').'.png';
      $destDir  = __DIR__.'/assets';
      if (!is_dir($destDir)) @mkdir($destDir,0775,true);
      $destPath = $destDir.'/'.$name;
      if (@move_uploaded_file($tmp, $destPath)) {
        $cfg['overlay_path'] = 'assets/'.$name;
      } else {
        $err = 'Failed to move uploaded overlay file.';
      }
    }
  }

  if (!$err) {
    if (save_config($cfg)) $msg = 'Settings updated successfully.';
    else $err = 'Failed to save config.json (check file permissions).';
  }
}

// helper: escape
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Photobooth Template Settings</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root{
    --bg:#020617;--border:#1f2937;
    --accent:#22d3ee;--accent2:#0ea5e9;
    --accent-soft:rgba(34,211,238,.12);
    --text:#f9fafb;--muted:#9ca3af;--danger:#f97373;
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  body{min-height:100vh;background:#020617;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--text);padding:2rem;}
  .wrap{max-width:1180px;margin:0 auto;background:radial-gradient(circle at top,#1f2937,#020617);border-radius:1.4rem;padding:1.8rem;border:1px solid rgba(148,163,184,.35);box-shadow:0 24px 80px rgba(15,23,42,.9);}
  h1{font-size:1.5rem;margin-bottom:.25rem;letter-spacing:.05em;text-transform:uppercase;}
  .subtitle{font-size:.9rem;color:var(--muted);margin-bottom:1.3rem;}
  form{display:grid;grid-template-columns:2.2fr 1.4fr;gap:1.5rem;}
  @media(max-width:980px){body{padding:1rem;} form{grid-template-columns:1fr;}}

  .card{background:rgba(15,23,42,.96);border-radius:1.1rem;padding:1.1rem 1.3rem;border:1px solid var(--border);}
  .card h2{font-size:1rem;margin-bottom:.6rem;display:flex;align-items:center;justify-content:space-between;gap:.75rem;}
  .field{margin-bottom:.7rem;}
  label{display:block;font-size:.8rem;color:var(--muted);margin-bottom:.15rem;text-transform:uppercase;letter-spacing:.06em;}
  input[type=text],input[type=number],input[type=range]{
    width:100%;padding:.5rem .6rem;border-radius:.5rem;border:1px solid #4b5563;background:#020617;color:var(--text);font-size:.9rem;
  }
  input[type=file]{font-size:.8rem;color:var(--muted);}
  .grid-small{display:grid;grid-template-columns:repeat(4,1fr);gap:.4rem;}
  .grid-small .field label{text-transform:none;font-size:.7rem;letter-spacing:0;}
  button[type=submit]{
    margin-top:.8rem;padding:.7rem 1.3rem;border-radius:.8rem;border:none;
    background:linear-gradient(135deg,var(--accent),var(--accent2));color:#0b1120;
    font-weight:800;cursor:pointer;box-shadow:0 12px 30px rgba(34,211,238,.5);
  }
  .msg{margin-bottom:.6rem;font-size:.83rem;}
  .msg.ok{color:#bbf7d0;}
  .msg.err{color:var(--danger);}
  pre{margin-top:.8rem;background:#020617;border-radius:.7rem;padding:.6rem;font-size:.7rem;color:#9ca3af;overflow-x:auto;}

  /* Preview + overlay editor */
  .preview-stage{
    position:relative;
    border-radius:.95rem;
    border:1px solid #4b5563;
    overflow:hidden;
    background:#020617;
  }

  /* IMPORTANT: frame is the "contain box" reference */
  .fs-frame{
    position:relative;
    width:100%;
    /* keep a nice editor ratio; will be overridden in fullscreen */
    aspect-ratio: 4 / 5;
    background:#0b1220;
  }

  /* both template and overlay always contain */
  .preview-stage img.bg,
  .preview-stage img.ov{
    position:absolute; inset:0;
    width:100%; height:100%;
    object-fit:contain;
    user-select:none; pointer-events:none;
  }
  .preview-stage img.ov{opacity:.98;}

  /* overlay container will be resized to visible image area via JS */
  .overlay{
    position:absolute;
    left:0; top:0;
    width:100%; height:100%;
    pointer-events:auto;
  }

  .slotBox{
    position:absolute;
    border:2px solid rgba(34,211,238,.95);
    background:rgba(34,211,238,.20);
    border-radius:.35rem;
    cursor:move;
    touch-action:none;
  }
  .slotLabel{
    position:absolute;left:.35rem;top:.35rem;
    font-size:.75rem;font-weight:800;
    padding:.15rem .4rem;border-radius:.4rem;
    background:rgba(2,6,23,.75);
    border:1px solid rgba(148,163,184,.35);
    color:#e5e7eb;
    user-select:none;
    pointer-events:none;
  }
  .handle{
    position:absolute;
    width:14px;height:14px;border-radius:4px;
    border:1px solid rgba(148,163,184,.6);
    background:rgba(2,6,23,.85);
    right:-7px;bottom:-7px;
    cursor:nwse-resize;
    touch-action:none;
  }

  .hint{margin-top:.75rem;color:var(--muted);font-size:.82rem;line-height:1.5;}
  .row{display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;}
  .chip{
    font-size:.8rem;color:var(--muted);
    padding:.35rem .6rem;border-radius:999px;
    border:1px solid rgba(148,163,184,.3);
    background:rgba(2,6,23,.55);
  }
  input[type=color]{
    width:46px;height:36px;border-radius:.6rem;
    border:1px solid #4b5563;background:#020617;padding:0;
  }
  .toggle{display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:var(--muted);}
  .toggle input{transform:scale(1.15);}

  /* Buttons */
  .fsBtn{
    appearance:none;border:1px solid rgba(148,163,184,.35);
    background:rgba(2,6,23,.55);
    color:#cbd5e1;
    padding:.42rem .65rem;
    border-radius:.75rem;
    font-size:.8rem;
    font-weight:800;
    cursor:pointer;
    display:inline-flex;align-items:center;gap:.4rem;
  }
  .fsBtn:hover{background:rgba(2,6,23,.8);}

  /* ---------- FULLSCREEN ---------- */
  :fullscreen body,
  :-webkit-full-screen body{ padding:0 !important; }

  :fullscreen .preview-stage,
  :-webkit-full-screen .preview-stage{
    width:100vw;
    height:100vh;
    border-radius:0;
    border:none;
    background:rgba(2,6,23,.98);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:56px 56px 36px;
    position:relative;
  }

  :fullscreen .fs-frame,
  :-webkit-full-screen .fs-frame{
    width:100%;
    height:100%;
    aspect-ratio:auto;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 30px 120px rgba(0,0,0,.65);
    border:1px solid rgba(148,163,184,.20);
    background:#0b1220;
  }

  .fs-hud{
    position:absolute;
    top:14px;
    left:14px;
    right:14px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    pointer-events:none;
    z-index:50;
  }
  .fs-hint{
    pointer-events:none;
    font-size:.82rem;
    color:#cbd5e1;
    padding:.5rem .75rem;
    border-radius:999px;
    background:rgba(2,6,23,.70);
    border:1px solid rgba(148,163,184,.25);
    backdrop-filter: blur(8px);
  }
  .fs-exit{
    pointer-events:auto;
    appearance:none;
    border:1px solid rgba(148,163,184,.25);
    background:rgba(2,6,23,.70);
    color:#e5e7eb;
    padding:.5rem .9rem;
    border-radius:999px;
    font-weight:900;
    cursor:pointer;
    backdrop-filter: blur(8px);
  }
  .fs-exit:hover{ background:rgba(2,6,23,.90); }
  .fs-hud{ display:none; }
  :fullscreen .fs-hud,
  :-webkit-full-screen .fs-hud{ display:flex; }
</style>
</head>
<body>
<div class="wrap">
  <h1>Photobooth Template Settings</h1>
  <p class="subtitle">Adjust slots visually (drag + resize), pick overlay color, upload overlay PNG, then Save Settings.</p>

  <?php if($msg): ?><div class="msg ok"><?=e($msg)?></div><?php endif; ?>
  <?php if($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div class="card">
      <h2>General Settings</h2>

      <div class="field">
        <label for="event_title">Event title (UI text)</label>
        <input type="text" id="event_title" name="event_title" value="<?=e($cfg['event_title'])?>">
      </div>

      <div class="field">
        <label for="countdown_seconds">Countdown seconds per shot</label>
        <input type="number" id="countdown_seconds" name="countdown_seconds" min="1" max="30" value="<?= (int)$cfg['countdown_seconds'] ?>">
      </div>

      <div class="field">
        <label>Template PNG (Background)</label>
        <input type="file" name="template_file" accept="image/png">
        <div class="hint">Current: <code><?=e($cfg['template_path'] ?? '')?></code></div>
      </div>

      <div class="field">
        <label>Overlay PNG (Snow/Effects on top)</label>
        <input type="file" name="overlay_file" accept="image/png">
        <div class="hint">
          Current:
          <?php if(!empty($cfg['overlay_path'])): ?>
            <code><?=e($cfg['overlay_path'])?></code>
          <?php else: ?>
            <span class="chip">none</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="margin-top:1rem;">
        <h2>Photo Edge Style (Final Output)</h2>
        <div class="row">
          <div class="field" style="min-width:220px;flex:1;">
            <label for="corner_radius">Corner Radius (px)</label>
            <input type="number" id="corner_radius" name="corner_radius" min="0" max="120" value="<?= (int)$cfg['corner_radius'] ?>">
          </div>
          <div class="field" style="min-width:220px;flex:1;">
            <label for="corner_soft">Soft Edge / Feather (px)</label>
            <input type="number" id="corner_soft" name="corner_soft" min="0" max="30" value="<?= (int)$cfg['corner_soft'] ?>">
          </div>
        </div>
        <p class="hint">Suggested: radius <strong>18–30</strong>, soft <strong>4–10</strong>.</p>
      </div>

      <div class="card" style="margin-top:1rem;">
        <h2>Overlay Visibility (for adjusting slots)</h2>

        <div class="row">
          <div class="field" style="min-width:220px;flex:1;">
            <label for="slot_overlay_color">Box Color</label>
            <div class="row">
              <input type="color" id="slot_overlay_color" name="slot_overlay_color" value="<?=e($cfg['slot_overlay_color'])?>">
              <div class="chip" id="colorHex"><?=e($cfg['slot_overlay_color'])?></div>
            </div>
          </div>

          <div class="field" style="min-width:240px;flex:1;">
            <label for="slot_overlay_opacity">Fill Opacity</label>
            <input type="range" id="slot_overlay_opacity" name="slot_overlay_opacity" min="0" max="1" step="0.01" value="<?=e($cfg['slot_overlay_opacity'])?>">
            <div class="chip" id="opVal"><?=e($cfg['slot_overlay_opacity'])?></div>
          </div>

          <div class="toggle">
            <input type="checkbox" id="slot_overlay_show_labels" name="slot_overlay_show_labels" <?= $cfg['slot_overlay_show_labels'] ? 'checked' : '' ?>>
            <label for="slot_overlay_show_labels" style="margin:0;text-transform:none;letter-spacing:0;">Show labels</label>
          </div>
        </div>

        <p class="hint">
          ✅ Drag the boxes to move (x,y). Grab the small corner handle to resize (w,h).<br>
          Changes auto-update the inputs below.
        </p>
      </div>

      <div class="card" style="margin-top:1rem;">
        <h2>Photo Slots (x, y, width, height)</h2>

        <?php for($i=0;$i<4;$i++): $s=$cfg['slots'][$i]; ?>
          <div class="field">
            <label>Slot <?=($i+1)?></label>
            <div class="grid-small">
              <div class="field">
                <label for="slot<?=$i?>_x">X</label>
                <input type="number" id="slot<?=$i?>_x" name="slot<?=$i?>_x" value="<?= (int)$s['x'] ?>">
              </div>
              <div class="field">
                <label for="slot<?=$i?>_y">Y</label>
                <input type="number" id="slot<?=$i?>_y" name="slot<?=$i?>_y" value="<?= (int)$s['y'] ?>">
              </div>
              <div class="field">
                <label for="slot<?=$i?>_w">W</label>
                <input type="number" id="slot<?=$i?>_w" name="slot<?=$i?>_w" value="<?= (int)$s['w'] ?>">
              </div>
              <div class="field">
                <label for="slot<?=$i?>_h">H</label>
                <input type="number" id="slot<?=$i?>_h" name="slot<?=$i?>_h" value="<?= (int)$s['h'] ?>">
              </div>
            </div>
          </div>
        <?php endfor; ?>

      </div>

      <button type="submit" name="save_settings">Save Settings</button>
    </div>

    <div class="card">
      <h2>
        <span>Template Preview + Slot Boxes</span>
        <button class="fsBtn" type="button" id="fsBtn" title="Fullscreen">⛶ Fullscreen</button>
      </h2>

      <div class="preview-stage" id="stage">
        <div class="fs-hud" id="fsHud">
          <div class="fs-hint">Press <b>Esc</b> to exit</div>
          <button class="fs-exit" type="button" id="fsExit">Exit</button>
        </div>

        <div class="fs-frame" id="fsFrame">
          <img class="bg" id="tplImg" src="<?=e($cfg['template_path'])?>" alt="Current template">
          <?php if(!empty($cfg['overlay_path'])): ?>
            <img class="ov" id="ovImg" src="<?=e($cfg['overlay_path'])?>" alt="Overlay">
          <?php else: ?>
            <img class="ov" id="ovImg" src="" alt="Overlay" style="display:none;">
          <?php endif; ?>
          <div class="overlay" id="overlay"></div>
        </div>
      </div>

      <pre><?=e(json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))?></pre>
    </div>
  </form>
</div>

<script>
/* ---- initial config from PHP ---- */
const CFG = {
  slots: <?=json_encode($cfg['slots'])?>,
  color: <?=json_encode($cfg['slot_overlay_color'])?>,
  opacity: <?=json_encode((float)$cfg['slot_overlay_opacity'])?>,
  showLabels: <?=json_encode((bool)$cfg['slot_overlay_show_labels'])?>
};

const stage   = document.getElementById('stage');
const frame   = document.getElementById('fsFrame');
const img     = document.getElementById('tplImg');
const overlay = document.getElementById('overlay');

const fsBtn   = document.getElementById('fsBtn');
const fsExit  = document.getElementById('fsExit');

const colorInput = document.getElementById('slot_overlay_color');
const colorHex   = document.getElementById('colorHex');
const opInput    = document.getElementById('slot_overlay_opacity');
const opVal      = document.getElementById('opVal');
const labelsToggle = document.getElementById('slot_overlay_show_labels');

const inputs = [...Array(4)].map((_,i)=>({
  x: document.getElementById(`slot${i}_x`),
  y: document.getElementById(`slot${i}_y`),
  w: document.getElementById(`slot${i}_w`),
  h: document.getElementById(`slot${i}_h`),
}));

/* ---- utils ---- */
function hexToRgba(hex, alpha){
  const h = String(hex || '#22d3ee').replace('#','').trim();
  const full = h.length===3 ? h.split('').map(c=>c+c).join('') : h;
  const n = parseInt(full,16);
  const r = (n>>16)&255, g = (n>>8)&255, b = n&255;
  return `rgba(${r},${g},${b},${alpha})`;
}
function clamp(v,min,max){ return Math.max(min, Math.min(max, v)); }

/* ---- contain rect math (THIS FIXES MISALIGN) ---- */
function getContainRect(containerW, containerH, naturalW, naturalH){
  const iw = naturalW || 1, ih = naturalH || 1;
  const scale = Math.min(containerW/iw, containerH/ih);
  const drawW = iw * scale;
  const drawH = ih * scale;
  const offsetX = (containerW - drawW)/2;
  const offsetY = (containerH - drawH)/2;
  return {scale, drawW, drawH, offsetX, offsetY};
}

function getScale(){
  const rect = frame.getBoundingClientRect();
  const naturalW = img.naturalWidth  || 1;
  const naturalH = img.naturalHeight || 1;
  const fit = getContainRect(rect.width, rect.height, naturalW, naturalH);
  return {
    scaleX: fit.scale,
    scaleY: fit.scale,
    offsetX: fit.offsetX,
    offsetY: fit.offsetY,
    drawW: fit.drawW,
    drawH: fit.drawH,
    naturalW,
    naturalH
  };
}

function setBoxStyle(box){
  const col = colorInput.value || CFG.color;
  const op  = parseFloat(opInput.value || CFG.opacity);
  box.style.borderColor = hexToRgba(col, 0.95);
  box.style.background  = hexToRgba(col, op);
}

/* ---- create boxes ---- */
const boxes = [];

function buildBoxes(){
  overlay.innerHTML = '';
  boxes.length = 0;

  for(let i=0;i<4;i++){
    const box = document.createElement('div');
    box.className = 'slotBox';
    box.dataset.i = String(i);

    const label = document.createElement('div');
    label.className = 'slotLabel';
    label.textContent = `Slot ${i+1}`;
    label.style.display = labelsToggle.checked ? 'block' : 'none';

    const handle = document.createElement('div');
    handle.className = 'handle';

    box.appendChild(label);
    box.appendChild(handle);
    overlay.appendChild(box);

    setBoxStyle(box);
    boxes.push({box, label, handle});
    attachDragResize(i, box, handle);
  }

  renderFromInputs();
}

function renderFromInputs(){
  if(!img.naturalWidth) return;
  const s = getScale();

  // overlay becomes exactly the "visible image rect" inside the frame
  overlay.style.left   = s.offsetX + 'px';
  overlay.style.top    = s.offsetY + 'px';
  overlay.style.width  = s.drawW + 'px';
  overlay.style.height = s.drawH + 'px';

  for(let i=0;i<4;i++){
    const b = boxes[i].box;

    const x = parseInt(inputs[i].x.value||0,10);
    const y = parseInt(inputs[i].y.value||0,10);
    const w = parseInt(inputs[i].w.value||1,10);
    const h = parseInt(inputs[i].h.value||1,10);

    b.style.left   = (x*s.scaleX) + 'px';
    b.style.top    = (y*s.scaleY) + 'px';
    b.style.width  = (w*s.scaleX) + 'px';
    b.style.height = (h*s.scaleY) + 'px';

    setBoxStyle(b);
    boxes[i].label.style.display = labelsToggle.checked ? 'block' : 'none';
  }
}

function writeToInputs(i, x, y, w, h){
  inputs[i].x.value = Math.round(x);
  inputs[i].y.value = Math.round(y);
  inputs[i].w.value = Math.max(1, Math.round(w));
  inputs[i].h.value = Math.max(1, Math.round(h));
}

/* ---- drag + resize ---- */
function attachDragResize(i, box, handle){
  let mode = null;
  let start = {px:0, py:0, x:0, y:0, w:0, h:0, scaleX:1, scaleY:1};

  function pointerDownMove(e){
    if(e.target === handle) return;
    mode = 'move';
    box.setPointerCapture(e.pointerId);
    const s = getScale();
    start.px = e.clientX; start.py = e.clientY;
    start.x = parseInt(inputs[i].x.value||0,10);
    start.y = parseInt(inputs[i].y.value||0,10);
    start.w = parseInt(inputs[i].w.value||1,10);
    start.h = parseInt(inputs[i].h.value||1,10);
    start.scaleX = s.scaleX; start.scaleY = s.scaleY;
  }

  function pointerDownResize(e){
    mode = 'resize';
    box.setPointerCapture(e.pointerId);
    const s = getScale();
    start.px = e.clientX; start.py = e.clientY;
    start.x = parseInt(inputs[i].x.value||0,10);
    start.y = parseInt(inputs[i].y.value||0,10);
    start.w = parseInt(inputs[i].w.value||1,10);
    start.h = parseInt(inputs[i].h.value||1,10);
    start.scaleX = s.scaleX; start.scaleY = s.scaleY;
    e.stopPropagation();
  }

  function pointerMove(e){
    if(!mode) return;

    const dx = (e.clientX - start.px) / start.scaleX;
    const dy = (e.clientY - start.py) / start.scaleY;

    let x = start.x, y = start.y, w = start.w, h = start.h;

    if(mode === 'move'){
      x = start.x + dx;
      y = start.y + dy;
    }else{
      w = Math.max(1, start.w + dx);
      h = Math.max(1, start.h + dy);
    }

    const maxW = img.naturalWidth || 1;
    const maxH = img.naturalHeight || 1;

    x = clamp(x, 0, maxW - 1);
    y = clamp(y, 0, maxH - 1);
    w = clamp(w, 1, maxW - x);
    h = clamp(h, 1, maxH - y);

    writeToInputs(i, x, y, w, h);
    renderFromInputs();
  }

  function pointerUp(e){
    if(!mode) return;
    mode = null;
    try{ box.releasePointerCapture(e.pointerId); }catch(_){}
  }

  box.addEventListener('pointerdown', pointerDownMove);
  handle.addEventListener('pointerdown', pointerDownResize);
  box.addEventListener('pointermove', pointerMove);
  box.addEventListener('pointerup', pointerUp);
  box.addEventListener('pointercancel', pointerUp);
}

/* ---- live sync ---- */
inputs.forEach((row)=> Object.values(row).forEach(inp=>inp.addEventListener('input', renderFromInputs)));

colorInput.addEventListener('input', ()=>{
  colorHex.textContent = colorInput.value;
  boxes.forEach(b=>setBoxStyle(b.box));
});
opInput.addEventListener('input', ()=>{
  opVal.textContent = opInput.value;
  boxes.forEach(b=>setBoxStyle(b.box));
});
labelsToggle.addEventListener('change', renderFromInputs);

window.addEventListener('resize', ()=>renderFromInputs());
document.addEventListener('fullscreenchange', ()=>setTimeout(renderFromInputs, 120));
document.addEventListener('webkitfullscreenchange', ()=>setTimeout(renderFromInputs, 120));

img.addEventListener('load', ()=>{
  colorHex.textContent = colorInput.value;
  opVal.textContent = opInput.value;
  buildBoxes();
});

/* cached */
if (img.complete && img.naturalWidth) buildBoxes();

/* ---- fullscreen button ---- */
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

fsBtn.addEventListener('click', ()=>toggleFullscreen(stage));
fsExit.addEventListener('click', ()=>toggleFullscreen(stage));

document.addEventListener('keydown', (e)=>{
  if(e.key === 'f' || e.key === 'F'){
    const t = e.target;
    const isTyping = t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA');
    if(!isTyping){
      e.preventDefault();
      toggleFullscreen(stage);
    }
  }
});
</script>
</body>
</html>
