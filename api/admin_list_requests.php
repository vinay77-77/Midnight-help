<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
$aid = (int)($_SESSION['admin_id'] ?? 0);
if ($aid <= 0) json_response(['ok' => false, 'error' => 'Not signed in as admin'], 401);

$statusFilter = $_GET['status'] ?? 'active';
$limit = (int)($_GET['limit'] ?? 50);
if ($limit <= 0) $limit = 50;
if ($limit > 200) $limit = 200;

$db = db();

$where = '';
if ($statusFilter === 'active') {
  $where = "WHERE r.status IN ('pending','accepted','in_progress')";
}

$sql = "
  SELECT
    r.request_id,
    r.service_type,
    r.status,
    r.location_text,
    r.created_at,
    u.name AS user_name,
    u.phone AS user_phone,
    resp.name AS responder_name
  FROM requests r
  JOIN users u ON u.user_id = r.user_id
  LEFT JOIN assignments a ON a.request_id = r.request_id AND a.status IN ('assigned','accepted')
  LEFT JOIN responders resp ON resp.responder_id = a.responder_id
  $where
  ORDER BY r.created_at DESC
  LIMIT ?
";

$stmt = $db->prepare($sql);
$stmt->bind_param('i', $limit);
$stmt->execute();
$res = $stmt->get_result();

$requests = [];
while ($row = $res->fetch_assoc()) {
  $requests[] = [
    'requestId' => (int)$row['request_id'],
    'serviceType' => (string)$row['service_type'],
    'status' => (string)$row['status'],
    'locationText' => (string)($row['location_text'] ?? ''),
    'userName' => (string)$row['user_name'],
    'userPhone' => (string)$row['user_phone'],
    'responderName' => (string)($row['responder_name'] ?? ''),
    'createdAt' => (string)$row['created_at'],
  ];
}

json_response(['ok' => true, 'requests' => $requests]);

