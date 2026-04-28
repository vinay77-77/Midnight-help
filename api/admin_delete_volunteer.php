<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_post();
session_start();

$aid = (int)($_SESSION['admin_id'] ?? 0);
if ($aid <= 0) json_response(['ok' => false, 'error' => 'Not signed in as admin'], 401);

$body = read_json_body();
$responderId = (int)($body['responderId'] ?? 0);

if ($responderId <= 0) json_response(['ok' => false, 'error' => 'Valid responder ID required'], 400);

$db = db();

// Check if volunteer exists
$stmt = $db->prepare('SELECT responder_id FROM responders WHERE responder_id = ? LIMIT 1');
$stmt->bind_param('i', $responderId);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
  json_response(['ok' => false, 'error' => 'Volunteer not found'], 404);
}

// Delete volunteer (responder_services will be cascade-deleted)
$stmt = $db->prepare('DELETE FROM responders WHERE responder_id = ?');
$stmt->bind_param('i', $responderId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
  json_response(['ok' => true, 'message' => 'Volunteer deleted'], 200);
}

json_response(['ok' => false, 'error' => 'Failed to delete volunteer'], 500);

