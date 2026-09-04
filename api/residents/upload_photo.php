<?php
require_once __DIR__ . '/../common.php';
session_start();

if (empty($_SESSION['resident_id']) || !empty($_SESSION['official_id'])) {
  json_error('Resident access required.', 403);
}
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

$residentId = (int)$_SESSION['resident_id'];
$uploadDir = __DIR__ . '/../../uploads/resident_photos';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
  json_error('Unable to create photo storage directory.', 500);
}

$extension = $extensions[$mime];
$filename = $residentId . '.' . $extension;
$absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
foreach (['jpg', 'png'] as $oldExtension) {
  $oldPath = $uploadDir . DIRECTORY_SEPARATOR . $residentId . '.' . $oldExtension;
  if ($oldPath !== $absolutePath && file_exists($oldPath)) {
    unlink($oldPath);
  }
}
if (!move_uploaded_file($upload['tmp_name'], $absolutePath)) {
  json_error('Unable to save profile photo.', 500);
}

$relativePath = 'uploads/resident_photos/' . $filename;
$db = get_db();
$stmt = $db->prepare('UPDATE residents SET profile_photo = ? WHERE id = ?');
if (!$stmt) json_error('Unable to update profile photo.', 500);
$stmt->bind_param('si', $relativePath, $residentId);
if (!$stmt->execute()) json_error('Unable to update profile photo.', 500);
$stmt->close();

json_success(['message' => 'Profile photo updated.', 'profile_photo' => $relativePath]);
?>
