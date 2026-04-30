<?php
session_start();

// Disable error reporting for image output
error_reporting(0);

// Generate random captcha string (Only uppercase and numbers for clarity)
$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; 
$captcha_string = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_string .= $characters[rand(0, strlen($characters) - 1)];
}

// Store in session
$_SESSION['captcha_text'] = $captcha_string;

// Create image - Smaller box size
$width = 160;
$height = 45;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 0, 0, 0); // Black background
$text_color = imagecolorallocate($image, 255, 255, 255); // White text
$noise_color = imagecolorallocate($image, 50, 50, 50); // Darker noise

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add some noise
for ($i = 0; $i < 3; $i++) {
    imageline($image, 0, rand(0, $height), $width, rand(0, $height), $noise_color);
}

// Add text using TrueType Font
$font_file = 'C:\Windows\Fonts\arial.ttf';
$font_size = 18; // Adjusted font size to fit smaller box

if (file_exists($font_file)) {
    // Calculate bounding box to center text
    $bbox = imagettfbbox($font_size, 0, $font_file, $captcha_string);
    $text_width = $bbox[2] - $bbox[0];
    $text_height = $bbox[1] - $bbox[7];
    
    $x = ($width - $text_width) / 2;
    $y = ($height + $text_height) / 2 - 2; // Baseline adjustment
    
    // Draw text
    $current_x = $x;
    for ($i = 0; $i < strlen($captcha_string); $i++) {
        $char = $captcha_string[$i];
        $angle = rand(-5, 5);
        imagettftext($image, $font_size, $angle, $current_x, $y, $text_color, $font_file, $char);
        $current_x += 22; // Tight spacing
    }
} else {
    // Fallback to built-in font
    $font = 5;
    $text_width = imagefontwidth($font) * strlen($captcha_string);
    $text_height = imagefontheight($font);
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    imagestring($image, $font, $x, $y, $captcha_string, $text_color);
}

// Output image
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

ob_clean();
imagepng($image);
imagedestroy($image);
?>
