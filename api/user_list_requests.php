<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) json_response(['ok' => false, 'error' => 'Not signed in'], 401);

$status = (string)($_GET['status'] ?? '');
$limit = (int)($_GET['limit'] ?? 50);
if ($limit <= 0) $limit = 50;
if ($limit > 200) $limit = 200;

$where = 'WHERE r.user_id = ?';
$types = 'i';
$params = [$uid];

if ($status !== '') {
  $allowed = ['pending', 'accepted', 'in_progress', 'completed', 'cancelled', 'active'];
  if (!in_array($status, $allowed, true)) json_response(['ok' => false, 'error' => 'Invalid status filter'], 400);
  if ($status === 'active') {
    $where .= " AND r.status IN ('pending','accepted','in_progress')";
  } else {
    $where .= ' AND r.status = ?';
    $types .= 's';
    $params[] = $status;
  }
}

$db = db();
$sql = "
  SELECT
    r.request_id,
    r.service_type,
    r.status,
    r.location_text,
    r.created_at,
    r.updated_at,
    resp.name AS responder_name
  FROM requests r
  LEFT JOIN assignments a ON a.request_id = r.request_id AND a.status IN ('assigned','accepted')
  LEFT JOIN responders resp ON resp.responder_id = a.responder_id
  $where
  ORDER BY r.created_at DESC
  LIMIT ?
";

$types .= 'i';
$params[] = $limit;

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
  $rows[] = [
    'requestId' => (int)$row['request_id'],
    'serviceType' => (string)$row['service_type'],
    'status' => (string)$row['status'],
    'locationText' => (string)($row['location_text'] ?? ''),
    'responderName' => (string)($row['responder_name'] ?? ''),
    'createdAt' => (string)$row['created_at'],
    'updatedAt' => (string)$row['updated_at'],
  ];
}

json_response(['ok' => true, 'requests' => $rows]);

