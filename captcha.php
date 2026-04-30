<?php
session_start();

// Disable error reporting for image output to avoid corruption
error_reporting(0);

// Generate random captcha string
$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed similar characters like 0, O, 1, I, l
$captcha_string = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_string .= $characters[rand(0, strlen($characters) - 1)];
}

// Store in session
$_SESSION['captcha_text'] = $captcha_string;

// Check if GD library is available
if (!extension_loaded('gd')) {
    // If GD is missing, we can't generate an image. 
    // This will likely show a broken image, but we can't do much in captcha.php.
    // We will handle the fallback in the main page if needed.
}

// Create image
$width = 180;
$height = 50;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 0, 0, 0); // Black background
$text_color = imagecolorallocate($image, 255, 255, 255); // White text
$noise_color = imagecolorallocate($image, 80, 80, 80); // Dark grey noise

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add some noise (lines)
for ($i = 0; $i < 4; $i++) {
    imageline($image, 0, rand(0, $height), $width, rand(0, $height), $noise_color);
}

// Add some noise (dots)
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// Add text
// Use built-in font size 5 (largest built-in)
$font = 5;
$text_width = imagefontwidth($font) * strlen($captcha_string);
$text_height = imagefontheight($font);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

// Draw text with a slight shadow/offset for better look
imagestring($image, $font, $x+1, $y+1, $captcha_string, $noise_color);
imagestring($image, $font, $x, $y, $captcha_string, $text_color);

// Output image
ob_clean();
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

imagepng($image);
imagedestroy($image);
// No closing PHP tag to avoid whitespace issues
