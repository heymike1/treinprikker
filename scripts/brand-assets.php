<?php

/**
 * Regenerates favicon, app icons and the Open Graph image from resources/brand/.
 *
 *   php scripts/brand-assets.php
 */
$logo = new Imagick(__DIR__.'/../resources/brand/logo.png');   // 1254x1254, white background
$og = new Imagick(__DIR__.'/../resources/brand/og-source.png'); // 1731x909
$public = __DIR__.'/../public';

// App icons (iOS rounds the corners itself).
foreach (['apple-touch-icon.png' => 180, 'icon-512.png' => 512] as $file => $size) {
    $icon = clone $logo;
    $icon->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
    $icon->setImageFormat('png');
    $icon->stripImage();
    $icon->writeImage("$public/$file");
}

// favicon.ico with 16/32/48 px entries, logo cropped tighter so it stays legible.
$entries = [];
foreach ([16, 32, 48] as $size) {
    $small = clone $logo;
    $small->cropImage(900, 900, 177, 177);
    $small->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
    $small->setImageFormat('png');
    $entries[] = [$size, $small->getImageBlob()];
}
$ico = pack('vvv', 0, 1, count($entries));
$offset = 6 + 16 * count($entries);
$dir = '';
$data = '';
foreach ($entries as [$size, $png]) {
    $dir .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($png), $offset);
    $data .= $png;
    $offset += strlen($png);
}
file_put_contents("$public/favicon.ico", $ico.$dir.$data);

// Open Graph image: 1200x630, centre-cropped, JPEG to keep it small.
$og->cropThumbnailImage(1200, 630);
$og->setImageFormat('jpeg');
$og->setImageCompressionQuality(85);
$og->stripImage();
$og->writeImage("$public/images/og.jpg");
@unlink("$public/images/og.png");

echo "brand assets ok\n";
