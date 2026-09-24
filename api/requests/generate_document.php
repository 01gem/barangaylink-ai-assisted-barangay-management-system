<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

require_once __DIR__ . '/../common.php';
session_start();

if (empty($_SESSION['official_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

require_post();

// Defense-in-depth: official identity placeholders are fixed for generated documents.
$fixedOfficialName = 'Hon. Erwin Astronomo';
$fixedOfficialPosition = 'Barangay Captain';

function xml_safe_text(string $value): string {
  return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function remove_dir_tree(string $path): void {
  if (!is_dir($path)) return;
  $entries = scandir($path);
  if ($entries === false) return;
  foreach ($entries as $entry) {
    if ($entry === '.' || $entry === '..') continue;
    $full = $path . DIRECTORY_SEPARATOR . $entry;
    if (is_dir($full)) {
      remove_dir_tree($full);
    } elseif (file_exists($full)) {
      @unlink($full);
    }
  }
  @rmdir($path);
}

$db = get_db();
$input = read_json_input();
$requestId = (int)($input['id'] ?? $input['request_id'] ?? 0);
$submittedFields = $input['fields'] ?? [];
if ($requestId <= 0) {
  json_error('Valid request id is required.');
}
if (!is_array($submittedFields)) {
  json_error('Invalid fields payload.');
}

$templateMap = [
  'Barangay Clearance' => [
    'file' => 'barangay_clearance.docx',
    'placeholders' => ['full_name', 'age', 'civil_status', 'address', 'purpose', 'date_issued', 'reference_no', 'official_name', 'official_position'],
  ],
  'Certificate of Residency' => [
    'file' => 'certificate_of_residency.docx',
    'placeholders' => ['full_name', 'address', 'years_of_residency', 'purpose', 'date_issued', 'reference_no', 'official_name', 'official_position'],
  ],
  'Certificate of Indigency' => [
    'file' => 'certificate_of_indigency.docx',
    'placeholders' => ['full_name', 'address', 'purpose', 'date_issued', 'reference_no', 'official_name', 'official_position'],
  ],
  'Certificate of Good Moral Character' => [
    'file' => 'certificate_of_good_moral_character.docx',
    'placeholders' => ['full_name', 'address', 'purpose', 'date_issued', 'reference_no', 'official_name', 'official_position'],
  ],
  'Business Permit Endorsement' => [
    'file' => 'business_permit_endorsement.docx',
    'placeholders' => ['owner_name', 'business_name', 'business_type', 'business_address', 'purpose', 'date_issued', 'reference_no', 'official_name', 'official_position'],
  ],
];

$reqStmt = $db->prepare('SELECT dr.id, dr.reference_no, dr.document_type, dr.purpose, dr.resident_id, dr.resident_name, r.fname, r.lname, r.address FROM document_requests dr LEFT JOIN residents r ON r.id = dr.resident_id WHERE dr.id = ? LIMIT 1');
if (!$reqStmt) json_error('Failed to prepare request lookup.', 500);
$reqStmt->bind_param('i', $requestId);
$requestRows = db_query_all($reqStmt);
$reqStmt->close();
if (count($requestRows) === 0) {
  json_error('Document request not found.', 404);
}
$request = $requestRows[0];

$documentType = (string)($request['document_type'] ?? '');
if (!isset($templateMap[$documentType])) {
  json_error("No Word template mapping found for document type: {$documentType}", 400);
}

$templateFile = $templateMap[$documentType]['file'];
$placeholderKeys = $templateMap[$documentType]['placeholders'];
$templatePath = __DIR__ . '/../../document_templates/' . $templateFile;
if (!file_exists($templatePath)) {
  json_error("Template file not found: {$templateFile}", 500);
}

$generatedFields = [];
foreach ($submittedFields as $key => $value) {
  if (!is_string($key)) continue;
  if (is_scalar($value) || $value === null) {
    $generatedFields[$key] = trim((string)$value);
  }
}

$residentFullName = trim((string)($request['resident_name'] ?? ''));
if ($residentFullName === '') {
  $residentFullName = trim(((string)($request['fname'] ?? '')) . ' ' . ((string)($request['lname'] ?? '')));
}
$residentAddress = trim((string)($request['address'] ?? ''));

if (($generatedFields['full_name'] ?? '') === '' && $residentFullName !== '') {
  $generatedFields['full_name'] = $residentFullName;
}
if (($generatedFields['owner_name'] ?? '') === '' && $residentFullName !== '') {
  $generatedFields['owner_name'] = $residentFullName;
}
if (($generatedFields['address'] ?? '') === '' && $residentAddress !== '') {
  $generatedFields['address'] = $residentAddress;
}
if (($generatedFields['business_address'] ?? '') === '' && $residentAddress !== '') {
  $generatedFields['business_address'] = $residentAddress;
}
if (($generatedFields['purpose'] ?? '') === '') {
  $generatedFields['purpose'] = trim((string)($request['purpose'] ?? ''));
}
$generatedFields['reference_no'] = (string)($request['reference_no'] ?? '');
if (($generatedFields['date_issued'] ?? '') === '') {
  $generatedFields['date_issued'] = date('F d, Y');
}
$generatedFields['official_name'] = $fixedOfficialName;
$generatedFields['official_position'] = $fixedOfficialPosition;

$referenceNo = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)($request['reference_no'] ?? ''));
if ($referenceNo === null || $referenceNo === '') {
  json_error('Invalid request reference number for file generation.', 500);
}

$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'docgen_' . uniqid('', true);
if (!mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
  json_error('Failed to create temporary document workspace.', 500);
}

$workingDocx = $tempDir . DIRECTORY_SEPARATOR . $referenceNo . '.docx';
if (!copy($templatePath, $workingDocx)) {
  remove_dir_tree($tempDir);
  json_error('Failed to create working document from template.', 500);
}

if (!class_exists('ZipArchive')) {
  remove_dir_tree($tempDir);
  json_error('PHP ZipArchive extension is not enabled.', 500);
}

$zip = new ZipArchive();
if ($zip->open($workingDocx) !== true) {
  remove_dir_tree($tempDir);
  json_error('Failed to open template document archive.', 500);
}

$documentXml = $zip->getFromName('word/document.xml');
if ($documentXml === false) {
  $zip->close();
  remove_dir_tree($tempDir);
  json_error('Template is missing word/document.xml.', 500);
}

foreach ($placeholderKeys as $key) {
  $value = $generatedFields[$key] ?? '';
  $documentXml = str_replace('{{' . $key . '}}', xml_safe_text((string)$value), $documentXml);
}

if (!$zip->addFromString('word/document.xml', $documentXml)) {
  $zip->close();
  remove_dir_tree($tempDir);
  json_error('Failed to write updated document XML.', 500);
}
$zip->close();

$outputDir = __DIR__ . '/../../generated_documents';
if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true)) {
  remove_dir_tree($tempDir);
  json_error('Failed to create generated_documents directory.', 500);
}

$finalDocxPath = $outputDir . DIRECTORY_SEPARATOR . $referenceNo . '.docx';
if (!copy($workingDocx, $finalDocxPath)) {
  remove_dir_tree($tempDir);
  json_error('Failed to save generated DOCX file.', 500);
}

$sofficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
if (!file_exists($sofficePath)) {
  remove_dir_tree($tempDir);
  json_error("LibreOffice executable not found at {$sofficePath}", 500);
}

$convertCmd = '"' . $sofficePath . '" --headless --convert-to pdf --outdir "' . $outputDir . '" "' . $finalDocxPath . '" 2>&1';
$convertOutput = [];
$convertExitCode = 0;
exec($convertCmd, $convertOutput, $convertExitCode);

$finalPdfPath = $outputDir . DIRECTORY_SEPARATOR . $referenceNo . '.pdf';
if ($convertExitCode !== 0 || !file_exists($finalPdfPath)) {
  remove_dir_tree($tempDir);
  $details = trim(implode("\n", $convertOutput));
  json_error('Failed to convert DOCX to PDF via LibreOffice.' . ($details !== '' ? ' ' . $details : ''), 500);
}

remove_dir_tree($tempDir);

$pdfRelativePath = '../generated_documents/' . rawurlencode($referenceNo) . '.pdf';
$docxRelativePath = '../generated_documents/' . rawurlencode($referenceNo) . '.docx';

$storedPath = 'generated_documents/' . $referenceNo . '.pdf';
$pathStmt = $db->prepare('UPDATE document_requests SET generated_document_path = ? WHERE id = ?');
if (!$pathStmt) json_error('Failed to persist generated document path.', 500);
$pathStmt->bind_param('si', $storedPath, $requestId);
if (!$pathStmt->execute()) {
  $error = $pathStmt->error;
  $pathStmt->close();
  json_error('Failed to persist generated document path: ' . $error, 500);
}
$pathStmt->close();

json_success([
  'pdf_path' => $pdfRelativePath,
  'docx_path' => $docxRelativePath,
  'generated_document_path' => 'generated_documents/' . $referenceNo . '.pdf',
  'reference_no' => (string)$request['reference_no'],
]);
?>
