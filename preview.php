<?php
// Renders a 1200x630 OpenGraph image for a title: centered, word-wrapped,
// in Viridian Pro with the first letter in the green Drop Cap font.
// Usage: preview.php?t=<title>&s=<hmac>  (see og_preview_url() in functions.php)

$config = require __DIR__ . '/../../anchor/config/app.php';

$title = isset($_GET['t']) && is_string($_GET['t']) ? $_GET['t'] : '';
$sig = isset($_GET['s']) && is_string($_GET['s']) ? $_GET['s'] : '';
$expected = substr(hash_hmac('sha256', $title, $config['key'] . 'x'), 0, 16);
if ($title === '' || !hash_equals($expected, $sig)) {
	http_response_code(400);
	exit;
}

$W = 1200;
$H = 630;
$PAD_X = 110;
$PAD_Y = 90;
$TEXT_FONT = __DIR__ . '/fonts/Crimson_Pro/ViridianPro-400.ttf';
$CAP_FONT = __DIR__ . '/fonts/dropcap.ttf';

$img = imagecreatetruecolor($W, $H);
imagealphablending($img, true);
imagefill($img, 0, 0, imagecolorallocate($img, 248, 251, 243));
$green = imagecolorallocate($img, 68, 121, 68);

function text_width($size, $font, $text) {
	if ($text === '') return 0;
	$b = imagettfbbox($size, 0, $font, $text);
	return max($b[2], $b[4]) - min($b[0], $b[6]);
}

// [width, height, vertical center offset from baseline] of the inked glyph.
function glyph_box($size, $font, $text) {
	$b = imagettfbbox($size, 0, $font, $text);
	$top = min($b[5], $b[7]);
	$bottom = max($b[1], $b[3]);
	return array(max($b[2], $b[4]) - min($b[0], $b[6]), $bottom - $top, ($top + $bottom) / 2);
}

$chars = preg_split('//u', $title, -1, PREG_SPLIT_NO_EMPTY);
$first = $chars[0];
$rest = implode('', array_slice($chars, 1));

// Lay out the title, shrinking the font until it fits.
// Short titles that fit on one line get to be larger (up to 112); others start at 80.
for ($size = 112; $size >= 28; $size -= 4) {
	// Size the drop cap so its box is a bit taller than the text's capitals.
	$text_cap_h = glyph_box($size, $TEXT_FONT, 'H')[1];
	$cap_size = $size * ($text_cap_h * 1.6) / glyph_box($size, $CAP_FONT, $first)[1];
	$cap_w = text_width($cap_size, $CAP_FONT, $first) + $size * 0.12;
	// If the first letter is its own word (e.g. "A plan"), leave a word gap after the cap.
	if (preg_match('/^\s/u', $rest)) $cap_w += $size * 0.25;
	$line_h = $size * 1.3;
	$max_w = $W - 2 * $PAD_X;

	$words = preg_split('/\s+/u', $rest, -1, PREG_SPLIT_NO_EMPTY);
	$lines = array();
	$current = '';
	$offset = $cap_w; // first line carries the drop cap
	$too_wide = false;
	foreach ($words as $word) {
		$candidate = $current === '' ? $word : $current . ' ' . $word;
		if ($offset + text_width($size, $TEXT_FONT, $candidate) <= $max_w) {
			$current = $candidate;
		} else {
			$lines[] = $current;
			$current = $word;
			$offset = 0;
		}
	}
	$lines[] = $current;
	foreach ($lines as $i => $line) {
		if (($i === 0 ? $cap_w : 0) + text_width($size, $TEXT_FONT, $line) > $max_w) $too_wide = true;
	}
	if ($size > 80 && count($lines) > 1) continue;
	if (!$too_wide && count($lines) * $line_h <= $H - 2 * $PAD_Y) break;
}

// Vertically center the block, using cap height for optical balance.
$cap_height = $text_cap_h;
$block_h = (count($lines) - 1) * $line_h + $cap_height;
$baseline = ($H - $block_h) / 2 + $cap_height;

foreach ($lines as $i => $line) {
	$line_w = text_width($size, $TEXT_FONT, $line) + ($i === 0 ? $cap_w : 0);
	$x = ($W - $line_w) / 2;
	$y = $baseline + $i * $line_h;
	if ($i === 0) {
		// Center the drop cap on the middle of the text's capitals.
		$cap_y = $y - $text_cap_h / 2 - glyph_box($cap_size, $CAP_FONT, $first)[2];
		imagettftext($img, $cap_size, 0, (int)round($x), (int)round($cap_y), $green, $CAP_FONT, $first);
		$x += $cap_w;
	}
	imagettftext($img, $size, 0, (int)round($x), (int)round($y), $green, $TEXT_FONT, $line);
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000, immutable');
imagepng($img);
