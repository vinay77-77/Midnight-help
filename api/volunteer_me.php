<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
$rid = (int)($_SESSION['responder_id'] ?? 0);
if ($rid <= 0) json_response(['ok' => false, 'error' => 'Not signed in'], 401);

$stmt = db()->prepare(
  'SELECT responder_id, name, email, phone, availability_status, verified FROM responders WHERE responder_id = ? LIMIT 1'
);
$stmt->bind_param('i', $rid);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row) json_response(['ok' => false, 'error' => 'Volunteer not found'], 404);

json_response([
  'ok' => true,
  'volunteer' => [
    'responderId' => (int)$row['responder_id'],
    'name' => (string)$row['name'],
    'email' => (string)$row['email'],
    'phone' => (string)$row['phone'],
    'availabilityStatus' => (string)$row['availability_status'],
    'verified' => (int)$row['verified'] === 1,
  ],
]);
