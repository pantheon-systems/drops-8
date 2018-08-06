<?php

/**
 * @file
 * Contains functions used in the backend forms.
 */

/**
 * Menu handler for font preview request.
 *
 * @param string $font_token
 *   Name of font to generate image from.
 */
function image_captcha_font_preview($font_token) {
  // Get the font from the given font token.
  if ($font_token == 'BUILTIN') {
    $font = 'BUILTIN';
  }
  else {
    // Get the mapping of font tokens to font file objects.
    $fonts = \Drupal::config('image_captcha.settings')
      ->get('image_captcha_fonts_preview_map_cache');
    if (!isset($fonts[$font_token])) {
      print 'Bad token';
      exit();
    }
    // Get the font path.
    $font = $fonts[$font_token]->uri;
    // Some sanity checks if the given font is valid.
    if (!is_file($font) || !is_readable($font)) {
      print 'Bad font';
      exit();
    }
  }

  // Settings of the font preview.
  $width = 120;
  $text = 'AaBbCc123';
  $font_size = 14;
  $height = 2 * $font_size;

  // Allocate image resource.
  $image = imagecreatetruecolor($width, $height);
  if (!$image) {
    exit();
  }
  // White background and black foreground.
  $background_color = imagecolorallocate($image, 255, 255, 255);
  $color = imagecolorallocate($image, 0, 0, 0);
  imagefilledrectangle($image, 0, 0, $width, $height, $background_color);

  // Draw preview text.
  if ($font == 'BUILTIN') {
    imagestring($image, 5, 1, .5 * $height - 10, $text, $color);
  }
  else {
    imagettftext($image, $font_size, 0, 1, 1.5 * $font_size, $color, realpath($font), $text);
  }

  // Set content type.
  drupal_add_http_header('Content-Type', 'image/png');
  // Dump image data to client.
  imagepng($image);
  // Release image memory.
  imagedestroy($image);

  // Close connection.
  exit();
}
