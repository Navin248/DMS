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

// Create image
$width = 220;
$height = 70;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 0, 0, 0); // Black background
$text_color = imagecolorallocate($image, 255, 255, 255); // White text
$noise_color = imagecolorallocate($image, 60, 60, 60); // Dark grey noise

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add some noise
for ($i = 0; $i < 6; $i++) {
    imageline($image, 0, rand(0, $height), $width, rand(0, $height), $noise_color);
}
for ($i = 0; $i < 150; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// Add text using TrueType Font for much larger size
$font_file = 'C:\Windows\Fonts\arial.ttf';
$font_size = 28; // Large font size

if (file_exists($font_file)) {
    // Center the text
    $bbox = imagettfbbox($font_size, 0, $font_file, $captcha_string);
    $x = ($width - ($bbox[2] - $bbox[0])) / 2;
    $y = ($height - ($bbox[5] - $bbox[1])) / 2;
    
    // Draw text with slight random rotation for each letter for security
    $current_x = $x;
    for ($i = 0; $i < strlen($captcha_string); $i++) {
        $char = $captcha_string[$i];
        $angle = rand(-10, 10);
        imagettftext($image, $font_size, $angle, $current_x, $y, $text_color, $font_file, $char);
        $current_x += 30; // Spacing
    }
} else {
    // Fallback to built-in font if TTF fails
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

imagepng($image);
imagedestroy($image);
?>
