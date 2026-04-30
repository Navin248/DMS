<?php
session_start();

// Generate random captcha string
$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
$captcha_string = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_string .= $characters[rand(0, strlen($characters) - 1)];
}

// Store in session
$_SESSION['captcha_text'] = $captcha_string;

// Create image
$width = 200;
$height = 60;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 0, 0, 0); // Black background
$text_color = imagecolorallocate($image, 255, 255, 255); // White text
$noise_color = imagecolorallocate($image, 100, 100, 100); // Grey noise

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add some noise (lines)
for ($i = 0; $i < 5; $i++) {
    imageline($image, 0, rand(0, $height), $width, rand(0, $height), $noise_color);
}

// Add some noise (dots)
for ($i = 0; $i < 50; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// Add text
// Use a built-in font (1-5)
$font = 5;
$text_width = imagefontwidth($font) * strlen($captcha_string);
$text_height = imagefontheight($font);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font, $x, $y, $captcha_string, $text_color);

// Output image
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>
