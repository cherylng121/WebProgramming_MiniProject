<?php
// Function to get default profile picture based on role
function getDefaultProfilePicture($role) {
    $default_images = [
        'admin' => 'images/admin.png',
        'organizer' => 'images/organizer.png',
        'student' => 'images/student.png'
    ];
    
    return $default_images[$role] ?? 'images/default.png';
}

// Function to ensure default images exist
function ensureDefaultImages() {
    $default_images = [
        'admin' => 'images/admin.png',
        'organizer' => 'images/organizer.png',
        'student' => 'images/student.png',
        'default' => 'images/default.png'
    ];
    
    // Create images directory if it doesn't exist
    if (!file_exists('images')) {
        mkdir('images', 0777, true);
    }
    
    // Check if default images exist, if not create them
    foreach ($default_images as $role => $path) {
        if (!file_exists($path)) {
            // Create a simple colored square with role initial
            $image = imagecreatetruecolor(200, 200);
            $bg_color = match($role) {
                'admin' => imagecolorallocate($image, 41, 128, 185),    // Blue
                'organizer' => imagecolorallocate($image, 46, 204, 113), // Green
                'student' => imagecolorallocate($image, 155, 89, 182),   // Purple
                default => imagecolorallocate($image, 149, 165, 166)     // Gray
            };
            $text_color = imagecolorallocate($image, 255, 255, 255);
            
            // Fill background
            imagefilledrectangle($image, 0, 0, 200, 200, $bg_color);
            
            // Add text
            $text = strtoupper(substr($role, 0, 1));
            $font_size = 100;
            $font = 5; // Built-in font
            
            // Center text
            $text_width = imagefontwidth($font) * strlen($text);
            $text_height = imagefontheight($font);
            $x = (200 - $text_width) / 2;
            $y = (200 - $text_height) / 2;
            
            imagestring($image, $font, $x, $y, $text, $text_color);
            
            // Save image
            imagepng($image, $path);
            imagedestroy($image);
        }
    }
}

// Call the function to ensure default images exist
ensureDefaultImages();
?> 