<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_post();
$body = read_json_body();

session_start();
$rid = (int)($_SESSION['responder_id'] ?? 0);
if ($rid <= 0) json_response(['ok' => false, 'error' => 'Not signed in'], 401);

$requestId = (int)($body['requestId'] ?? 0);
if ($requestId <= 0) json_response(['ok' => false, 'error' => 'Valid requestId required'], 400);

$db = db();
$db->begin_transaction();

try {
  // Ensure request exists and is still pending.
  $stmt = $db->prepare('SELECT request_id, service_type, status FROM requests WHERE request_id = ? FOR UPDATE');
  $stmt->bind_param('i', $requestId);
  $stmt->execute();
  $req = $stmt->get_result()->fetch_assoc();
  if (!$req) throw new RuntimeException('Request not found');
  if ((string)$req['status'] !== 'pending') throw new RuntimeException('Request is not available');

  // Ensure no active assignment already exists.
  $stmt = $db->prepare("SELECT assignment_id FROM assignments WHERE request_id = ? AND status IN ('assigned','accepted') LIMIT 1 FOR UPDATE");
  $stmt->bind_param('i', $requestId);
  $stmt->execute();
  $existing = $stmt->get_result()->fetch_assoc();
  if ($existing) throw new RuntimeException('Request already assigned');

  // Ensure volunteer is allowed for this service type.
  $serviceType = (string)$req['service_type'];
  $stmt = $db->prepare('SELECT 1 FROM responder_services WHERE responder_id = ? AND service_type = ? LIMIT 1');
  $stmt->bind_param('is', $rid, $serviceType);
  $stmt->execute();
  if (!$stmt->get_result()->fetch_assoc()) throw new RuntimeException('You are not enabled for this service type');

  // Create assignment + mark request accepted.
  $stmt = $db->prepare("INSERT INTO assignments (request_id, responder_id, status) VALUES (?, ?, 'accepted')");
  $stmt->bind_param('ii', $requestId, $rid);
  $stmt->execute();

  $stmt = $db->prepare("UPDATE requests SET status='accepted' WHERE request_id = ?");
  $stmt->bind_param('i', $requestId);
  $stmt->execute();

  $db->commit();
  json_response(['ok' => true]);
} catch (Throwable $e) {
  $db->rollback();
  $msg = $e->getMessage();
  $code = 400;
  if ($msg === 'Request not found') $code = 404;
  json_response(['ok' => false, 'error' => $msg], $code);
}

