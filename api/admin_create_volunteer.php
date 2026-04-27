<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_post();
session_start();

$aid = (int)($_SESSION['admin_id'] ?? 0);
if ($aid <= 0) json_response(['ok' => false, 'error' => 'Not signed in as admin'], 401);

$body = read_json_body();

$name = trim((string)($body['name'] ?? ''));
$phone = trim((string)($body['phone'] ?? ''));
$services = $body['services'] ?? [];
$verified = (int)($body['verified'] ?? 0);

if (strlen($name) < 2) json_response(['ok' => false, 'error' => 'Name is required'], 400);
if (strlen(preg_replace('/[\\s\\-()]/', '', $phone)) < 7) json_response(['ok' => false, 'error' => 'Valid phone required'], 400);
if (!is_array($services)) json_response(['ok' => false, 'error' => 'Services must be an array'], 400);
if ($verified !== 0 && $verified !== 1) json_response(['ok' => false, 'error' => 'Verified must be 0 or 1'], 400);

$allowedServices = ['elderly' => true, 'vehicle' => true, 'driving' => true, 'sos' => true];
$cleanServices = [];
foreach ($services as $s) {
  $sv = strtolower(trim((string)$s));
  if ($sv === '') continue;
  if (!isset($allowedServices[$sv])) {
    json_response(['ok' => false, 'error' => 'Invalid service type: ' . $sv], 400);
  }
  $cleanServices[$sv] = true;
}

$conn = db();
$conn->begin_transaction();

try {
  $stmt = $conn->prepare('INSERT INTO responders (name, phone, verified, created_by_admin_id) VALUES (?,?,?,?)');
  $stmt->bind_param('ssii', $name, $phone, $verified, $aid);
  $stmt->execute();
  $rid = (int)$conn->insert_id;

  if (!empty($cleanServices)) {
    $stmt2 = $conn->prepare('INSERT INTO responder_services (responder_id, service_type) VALUES (?,?)');
    foreach (array_keys($cleanServices) as $sv) {
      $stmt2->bind_param('is', $rid, $sv);
      $stmt2->execute();
    }
  }

  $conn->commit();
  json_response(['ok' => true, 'responderId' => $rid], 201);
} catch (mysqli_sql_exception $e) {
  $conn->rollback();
  if (($e->getCode() ?? 0) === 1062) {
    json_response(['ok' => false, 'error' => 'Volunteer phone already exists'], 409);
  }
  json_response(['ok' => false, 'error' => 'Server error'], 500);
}

