<?php
require_once __DIR__ . '/../common.php';
session_start();
require_official_session();
require_post();

if (!isset($_FILES['photo']) || !is_array($_FILES['photo'])) {
  json_error('Please select a photo.');
}

$upload = $_FILES['photo'];
if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
  json_error('Photo upload failed.');
}
if (($upload['size'] ?? 0) > 2 * 1024 * 1024) {
  json_error('Photo must be 2MB or smaller.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $upload['tmp_name']) : false;
if ($finfo) finfo_close($finfo);
$extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
if (!isset($extensions[$mime])) {
  json_error('Only JPEG and PNG images are allowed.');
}
if (@getimagesize($upload['tmp_name']) === false) {
  json_error('Uploaded file is not a valid image.');
}

$officialId = (int)$_SESSION['official_id'];
$uploadDir = __DIR__ . '/../../profile_img/b_official';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
  json_error('Unable to create photo storage directory.', 500);
}

$extension = $extensions[$mime];
$filename = $officialId . '.' . $extension;
$absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
foreach (['jpg', 'png'] as $oldExtension) {
  $oldPath = $uploadDir . DIRECTORY_SEPARATOR . $officialId . '.' . $oldExtension;
  if ($oldPath !== $absolutePath && file_exists($oldPath) && !unlink($oldPath)) {
    json_error('Unable to replace previous profile photo.', 500);
  }
}
if (!move_uploaded_file($upload['tmp_name'], $absolutePath)) {
  json_error('Unable to save profile photo.', 500);
}

$relativePath = 'profile_img/b_official/' . $filename;
$db = get_db();
$stmt = $db->prepare('UPDATE barangay_officials SET profile_photo = ? WHERE id = ?');
if (!$stmt) json_error('Unable to update profile photo.', 500);
$stmt->bind_param('si', $relativePath, $officialId);
if (!$stmt->execute()) json_error('Unable to update profile photo.', 500);
$stmt->close();

json_success(['message' => 'Profile photo updated.', 'profile_photo' => $relativePath]);
?>
