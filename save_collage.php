<?php
// save_collage.php
header('Content-Type: application/json');

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__.'/photobooth_error.log');

require_once __DIR__.'/config.php';
$cfg = load_config();

$templateBgPath = __DIR__ . '/' . ($cfg['template_path'] ?? '');
$overlayPath    = __DIR__ . '/' . ($cfg['overlay_path'] ?? ''); // optional overlay png
$outputDir      = __DIR__ . '/output';
$slots          = $cfg['slots'] ?? [];

// ---- checks ----
if (!function_exists('imagecreatefrompng')) {
  echo json_encode(['success'=>false,'message'=>'PHP GD extension is not enabled (imagecreatefrompng missing).']);
  exit;
}
if (!file_exists($templateBgPath)) {
  echo json_encode(['success'=>false,'message'=>'Template BG not found: '.($cfg['template_path'] ?? '')]);
  exit;
}
if (!is_dir($outputDir)) {
  if (!mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
    echo json_encode(['success'=>false,'message'=>'Cannot create output directory.']);
    exit;
  }
}

// ---- load background template ----
$base = @imagecreatefrompng($templateBgPath);
if (!$base) { echo json_encode(['success'=>false,'message'=>'Cannot load template BG.']); exit; }

imagealphablending($base, true);
imagesavealpha($base, true);

$W = imagesx($base);
$H = imagesy($base);

// ---- load overlay template (optional) ----
$overlay = null;
if (!empty($cfg['overlay_path']) && file_exists($overlayPath)) {
  $overlay = @imagecreatefrompng($overlayPath);
  if ($overlay) {
    imagealphablending($overlay, true);
    imagesavealpha($overlay, true);
  }
}

// Fallback slots
if (empty($slots) || !is_array($slots)) {
  $slots = [
    ['x'=>30,'y'=>60,'w'=>360,'h'=>240],
    ['x'=>30,'y'=>290,'w'=>200,'h'=>140],
    ['x'=>250,'y'=>290,'w'=>200,'h'=>140],
    ['x'=>470,'y'=>290,'w'=>200,'h'=>140],
  ];
}

/* ---------------- helpers ---------------- */

function centerCropToSlot($src, int $dstW, int $dstH) {
  $srcW = imagesx($src);
  $srcH = imagesy($src);

  $srcRatio  = $srcW / max(1,$srcH);
  $dstRatio  = $dstW / max(1,$dstH);

  if ($srcRatio > $dstRatio) {
    $newW = (int)($srcH * $dstRatio);
    $newH = $srcH;
    $srcX = (int)(($srcW - $newW)/2);
    $srcY = 0;
  } else {
    $newW = $srcW;
    $newH = (int)($srcW / $dstRatio);
    $srcX = 0;
    $srcY = (int)(($srcH - $newH)/2);
  }
  return [$srcX,$srcY,$newW,$newH];
}

function createTrueColorAlpha(int $w, int $h) {
  $im = imagecreatetruecolor($w,$h);
  imagealphablending($im, false);
  imagesavealpha($im, true);
  $transparent = imagecolorallocatealpha($im, 0,0,0,127);
  imagefilledrectangle($im, 0,0, $w,$h, $transparent);
  return $im;
}

/**
 * Rounded corners with soft edge (feather).
 * $radius: corner radius in px
 * $feather: softness in px (2-10 ok)
 */
function applyRoundedMask(&$img, int $radius=22, int $feather=6) {
  $w = imagesx($img);
  $h = imagesy($img);

  $radius  = max(0, min($radius, (int)(min($w,$h)/2)));
  $feather = max(0, min($feather, 30));

  if ($radius <= 0 && $feather <= 0) return;

  // Oversample mask for smoother edges
  $scale = 4;
  $mw = $w * $scale;
  $mh = $h * $scale;
  $r  = $radius * $scale;

  $maskBig = imagecreatetruecolor($mw,$mh);
  imagealphablending($maskBig, false);
  imagesavealpha($maskBig, true);
  $t = imagecolorallocatealpha($maskBig, 0,0,0,127);
  imagefilledrectangle($maskBig, 0,0, $mw,$mh, $t);

  // White = visible
  $white = imagecolorallocatealpha($maskBig, 255,255,255,0);

  // center rect
  imagefilledrectangle($maskBig, $r, 0, $mw-$r, $mh, $white);
  imagefilledrectangle($maskBig, 0, $r, $mw, $mh-$r, $white);

  // 4 corners (circles)
  imagefilledellipse($maskBig, $r, $r, $r*2, $r*2, $white);
  imagefilledellipse($maskBig, $mw-$r, $r, $r*2, $r*2, $white);
  imagefilledellipse($maskBig, $r, $mh-$r, $r*2, $r*2, $white);
  imagefilledellipse($maskBig, $mw-$r, $mh-$r, $r*2, $r*2, $white);

  // Downsample mask
  $mask = imagecreatetruecolor($w,$h);
  imagealphablending($mask, false);
  imagesavealpha($mask, true);
  imagecopyresampled($mask, $maskBig, 0,0, 0,0, $w,$h, $mw,$mh);
  imagedestroy($maskBig);

  // Optional feather: blur by resampling a few times (cheap blur)
  if ($feather > 0) {
    for ($i=0; $i<2; $i++) {
      $tmp = imagecreatetruecolor($w,$h);
      imagealphablending($tmp, false);
      imagesavealpha($tmp, true);
      imagecopyresampled($tmp, $mask, 0,0, 0,0, $w,$h, $w-2, $h-2);
      imagecopyresampled($mask, $tmp, 0,0, 0,0, $w,$h, $w,$h);
      imagedestroy($tmp);
    }
  }

  // Apply mask to image alpha
  imagealphablending($img, false);
  imagesavealpha($img, true);

  for ($y=0; $y<$h; $y++) {
    for ($x=0; $x<$w; $x++) {
      $rgb  = imagecolorat($img, $x,$y);
      $aOld = ($rgb >> 24) & 0x7F;

      $mcol = imagecolorat($mask, $x,$y);
      $mr   = ($mcol >> 16) & 0xFF; // 0..255 (white=255)

      // convert mask brightness to alpha: white=>0 alpha, black=>127 alpha
      $aMask = 127 - (int)round($mr * (127/255));

      // combine: keep the more transparent of both
      $aNew = max($aOld, $aMask);

      $r2 = ($rgb >> 16) & 0xFF;
      $g2 = ($rgb >> 8) & 0xFF;
      $b2 = $rgb & 0xFF;

      $color = imagecolorallocatealpha($img, $r2,$g2,$b2, $aNew);
      imagesetpixel($img, $x,$y, $color);
    }
  }

  imagedestroy($mask);
  imagealphablending($img, true);
}

/* --- NEW: Border helpers --- */
function hexToRgb(string $hex): array {
  $hex = ltrim(trim($hex), '#');
  if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
  if (strlen($hex) !== 6) return [56, 189, 248]; // fallback
  return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
}

/**
 * Draw rounded border INSIDE the image (respects radius).
 * $thickness = 0 disables.
 */
function drawRoundedBorder(&$img, int $radius, int $thickness, string $hexColor) {
  $thickness = max(0, (int)$thickness);
  if ($thickness <= 0) return;

  $w = imagesx($img);
  $h = imagesy($img);
  $radius = max(0, min($radius, (int)(min($w,$h)/2)));

  [$r,$g,$b] = hexToRgb($hexColor);

  imagealphablending($img, true);
  imagesavealpha($img, true);

  // alpha 0=opaque, 127=transparent
  $col = imagecolorallocatealpha($img, $r, $g, $b, 10);

  for ($t=0; $t<$thickness; $t++) {
    $x1 = $t; $y1 = $t;
    $x2 = $w-1-$t; $y2 = $h-1-$t;
    $rr = max(0, $radius - $t);

    // lines
    imageline($img, $x1+$rr, $y1, $x2-$rr, $y1, $col); // top
    imageline($img, $x1+$rr, $y2, $x2-$rr, $y2, $col); // bottom
    imageline($img, $x1, $y1+$rr, $x1, $y2-$rr, $col); // left
    imageline($img, $x2, $y1+$rr, $x2, $y2-$rr, $col); // right

    // arcs
    if ($rr > 0) {
      imagearc($img, $x1+$rr, $y1+$rr, $rr*2, $rr*2, 180, 270, $col); // TL
      imagearc($img, $x2-$rr, $y1+$rr, $rr*2, $rr*2, 270, 360, $col); // TR
      imagearc($img, $x1+$rr, $y2-$rr, $rr*2, $rr*2, 90, 180,  $col); // BL
      imagearc($img, $x2-$rr, $y2-$rr, $rr*2, $rr*2, 0, 90,   $col); // BR
    }
  }
}

/* ---------------- read 4 photos ---------------- */

$photos = [];
for ($i=0; $i<4; $i++) {
  if (empty($_POST['photo'.$i])) {
    imagedestroy($base);
    if ($overlay) imagedestroy($overlay);
    echo json_encode(['success'=>false,'message'=>'Missing photo '.$i]);
    exit;
  }
  $dataUrl = $_POST['photo'.$i];

  if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl)) {
    $data = substr($dataUrl, strpos($dataUrl, ',') + 1);
    $bin  = base64_decode($data);
    if ($bin === false) { echo json_encode(['success'=>false,'message'=>'Base64 decode failed.']); exit; }
    $img = imagecreatefromstring($bin);
    if (!$img) { echo json_encode(['success'=>false,'message'=>'Cannot create image from data.']); exit; }
    $photos[] = $img;
  } else {
    echo json_encode(['success'=>false,'message'=>'Invalid data URL format.']);
    exit;
  }
}

/* ---------------- compose: photos -> base -> overlay ---------------- */

// Rounded corner settings
$cornerRadius = (int)($cfg['corner_radius'] ?? 22);
$cornerSoft   = (int)($cfg['corner_soft'] ?? 6);

// NEW: Border settings (editable in admin_template.php)
$borderColor  = (string)($cfg['photo_border_color'] ?? '#38bdf8');
$borderThick  = (int)($cfg['photo_border_thickness'] ?? 0);

foreach ($photos as $i => $src) {
  if (!isset($slots[$i])) { imagedestroy($src); continue; }
  $slot = $slots[$i];

  $dstW = (int)$slot['w'];
  $dstH = (int)$slot['h'];

  // Make resized/cropped temp image with alpha
  $dst = createTrueColorAlpha($dstW, $dstH);

  [$srcX,$srcY,$newW,$newH] = centerCropToSlot($src, $dstW, $dstH);

  imagecopyresampled(
    $dst, $src,
    0, 0,
    $srcX, $srcY,
    $dstW, $dstH,
    $newW, $newH
  );

  // Rounded corners + soft edge
  applyRoundedMask($dst, $cornerRadius, $cornerSoft);

  // NEW: draw border AFTER rounding (so it follows the rounded corners)
  drawRoundedBorder($dst, $cornerRadius, $borderThick, $borderColor);

  // Copy photo onto base
  imagealphablending($base, true);
  imagecopy($base, $dst, (int)$slot['x'], (int)$slot['y'], 0,0, $dstW, $dstH);

  imagedestroy($dst);
  imagedestroy($src);
}

// Overlay on top
if ($overlay) {
  $ow = imagesx($overlay);
  $oh = imagesy($overlay);

  if ($ow === $W && $oh === $H) {
    imagecopy($base, $overlay, 0,0, 0,0, $W,$H);
  } else {
    $ov = createTrueColorAlpha($W,$H);
    imagecopyresampled($ov, $overlay, 0,0, 0,0, $W,$H, $ow,$oh);
    imagecopy($base, $ov, 0,0, 0,0, $W,$H);
    imagedestroy($ov);
  }
  imagedestroy($overlay);
}

// Save
$filename = 'edustria_booth_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.png';
$fullPath = $outputDir . '/' . $filename;

imagesavealpha($base, true);
if (!imagepng($base, $fullPath, 6)) {
  imagedestroy($base);
  echo json_encode(['success'=>false,'message'=>'Failed to save output image (check folder permissions).']);
  exit;
}
imagedestroy($base);

$baseUrl = rtrim(dirname($_SERVER['PHP_SELF']), '/');
$url = $baseUrl . '/output/' . $filename;

echo json_encode(['success'=>true,'url'=>$url]);
