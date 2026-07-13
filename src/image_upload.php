<?php

const MENU_IMAGE_MAX_BYTES = 2 * 1024 * 1024;
const MENU_IMAGE_MAX_WIDTH = 800;

/**
 * Validates and stores an uploaded menu image.
 * Re-encodes through GD (strips metadata, normalizes to jpg) and resizes to max 800px wide.
 * Returns ['path' => 'assets/menu/<hex>.jpg'] on success, ['error' => message] on failure,
 * or ['path' => null] when no file was submitted.
 */
function save_menu_image(array $file): array
{
  if ($file['error'] === UPLOAD_ERR_NO_FILE) {
    return ['path' => null];
  }
  if ($file['error'] !== UPLOAD_ERR_OK) {
    return ['error' => 'Image upload failed — please try again.'];
  }
  if ($file['size'] > MENU_IMAGE_MAX_BYTES) {
    return ['error' => 'Image is too large — maximum size is 2 MB.'];
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($file['tmp_name']);
  if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    return ['error' => 'Only JPEG, PNG or WebP images are allowed.'];
  }

  $source = @imagecreatefromstring(file_get_contents($file['tmp_name']));
  if ($source === false) {
    return ['error' => 'That file is not a valid image.'];
  }

  $width = imagesx($source);
  $height = imagesy($source);
  if ($width > MENU_IMAGE_MAX_WIDTH) {
    $newWidth = MENU_IMAGE_MAX_WIDTH;
    $newHeight = (int)round($height * $newWidth / $width);
    $resized = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagedestroy($source);
    $source = $resized;
  }

  $dir = __DIR__ . '/../public/assets/menu';
  if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    imagedestroy($source);
    return ['error' => 'Could not save the image — upload folder is missing.'];
  }

  $filename = bin2hex(random_bytes(16)) . '.jpg';
  if (!imagejpeg($source, "$dir/$filename", 85)) {
    imagedestroy($source);
    return ['error' => 'Could not save the image — please try again.'];
  }
  imagedestroy($source);

  return ['path' => "assets/menu/$filename"];
}
